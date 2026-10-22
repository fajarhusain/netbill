<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Payment;
use App\Services\ReceiptImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ReceiptStorageController extends Controller
{
    public function __construct(private ReceiptImageService $receiptImages) {}

    public function index()
    {
        return view('receipts.storage', $this->stats());
    }

    public function reset(Request $request)
    {
        $validated = $request->validate([
            'scope' => ['required', 'in:images,all'],
            'confirm' => ['accepted'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $scope = $validated['scope'];

        $deletedFiles = $this->deleteFiles();
        $clearedPayments = $this->clearPaymentColumns($scope === 'all');

        AuditLog::record(
            userId: $request->user()->id,
            action: 'receipt.storage_reset',
            entityType: 'receipt',
            entityId: null,
            old: null,
            new: [
                'scope' => $scope,
                'deleted_files' => $deletedFiles,
                'cleared_payments' => $clearedPayments,
            ],
            reason: $validated['reason'] ?? null,
        );

        $message = "Penyimpanan bukti direset: {$deletedFiles} file dihapus, {$clearedPayments} transaksi dibersihkan.";

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'deleted_files' => $deletedFiles,
                'cleared_payments' => $clearedPayments,
                'stats' => $this->stats(),
            ]);
        }

        return redirect()->route('receipts.storage')->with('success', $message);
    }

    public function resetPayment(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $path = $payment->receipt_image_path;

        if ($path && Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $payment->forceFill([
            'receipt_image_path' => null,
            'receipt_sent_at' => null,
            'receipt_sent_by' => null,
            'receipt_share_token' => null,
        ])->save();

        AuditLog::record(
            userId: $request->user()->id,
            action: 'receipt.reset',
            entityType: 'payment',
            entityId: $payment->id,
            old: ['receipt_image_path' => $path],
            new: ['receipt_image_path' => null],
            reason: $validated['reason'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Bukti pembayaran transaksi ini direset.']);
        }

        return back()->with('success', 'Bukti pembayaran transaksi ini direset.');
    }

    public function regenerate(Request $request)
    {
        $validated = $request->validate([
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
        ]);

        $limit = (int) ($validated['limit'] ?? 100);

        $payments = Payment::with(['customer.package', 'collector', 'allocations.invoice'])
            ->latest('paid_at')
            ->limit($limit)
            ->get();

        $generated = 0;
        $failed = 0;

        foreach ($payments as $payment) {
            try {
                if ($payment->receipt_image_path && Storage::disk('local')->exists($payment->receipt_image_path)) {
                    continue;
                }

                $payment->forceFill([
                    'receipt_sent_at' => $payment->receipt_sent_at ?? $payment->paid_at ?? now(),
                    'receipt_sent_by' => $payment->receipt_sent_by ?? $payment->collector_id,
                ]);

                $payment->receipt_image_path = $this->receiptImages->store($payment);
                $payment->save();
                $generated++;
            } catch (\Throwable $e) {
                report($e);
                $failed++;
            }
        }

        AuditLog::record(
            userId: $request->user()->id,
            action: 'receipt.regenerated',
            entityType: 'receipt',
            entityId: null,
            old: null,
            new: ['generated' => $generated, 'failed' => $failed],
        );

        $message = "Regenerasi selesai: {$generated} bukti dibuat".($failed > 0 ? ", {$failed} gagal." : '.');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'generated' => $generated,
                'failed' => $failed,
                'stats' => $this->stats(),
            ]);
        }

        return redirect()->route('receipts.storage')->with('success', $message);
    }

    private function deleteFiles(): int
    {
        $disk = Storage::disk('local');

        if (! $disk->exists('receipts')) {
            return 0;
        }

        $files = $disk->files('receipts');
        $count = count($files);

        if ($count > 0) {
            $disk->delete($files);
        }

        $directories = $disk->directories('receipts');
        foreach ($directories as $directory) {
            $disk->deleteDirectory($directory);
        }

        $disk->deleteDirectory('receipts');

        return $count;
    }

    private function clearPaymentColumns(bool $withTimestamps): int
    {
        $query = Payment::whereNotNull('receipt_image_path');

        $update = ['receipt_image_path' => null];

        if ($withTimestamps) {
            $update['receipt_sent_at'] = null;
            $update['receipt_sent_by'] = null;
            $update['receipt_share_token'] = null;
        }

        $affected = 0;

        DB::transaction(function () use ($query, $update, &$affected) {
            $affected = $query->update($update);
        });

        return $affected;
    }

    private function stats(): array
    {
        $disk = Storage::disk('local');
        $files = $disk->exists('receipts') ? $disk->files('receipts') : [];

        $bytes = 0;
        foreach ($files as $file) {
            $bytes += (int) $disk->size($file);
        }

        return [
            'file_count' => count($files),
            'total_bytes' => $bytes,
            'total_label' => $this->formatBytes($bytes),
            'payments_with_image' => Payment::whereNotNull('receipt_image_path')->count(),
            'payments_total' => Payment::count(),
            'missing_files' => Payment::whereNotNull('receipt_image_path')
                ->get()
                ->filter(fn (Payment $p) => ! $disk->exists((string) $p->receipt_image_path))
                ->count(),
            'oldest' => $files ? min(array_map(fn ($f) => $disk->lastModified($f), $files)) : null,
            'newest' => $files ? max(array_map(fn ($f) => $disk->lastModified($f), $files)) : null,
        ];
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $index = 0;
        $value = (float) $bytes;

        while ($value >= 1024 && $index < count($units) - 1) {
            $value /= 1024;
            $index++;
        }

        return number_format($value, $index === 0 ? 0 : 1, ',', '.').' '.$units[$index];
    }
}
