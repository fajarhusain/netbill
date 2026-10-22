<?php

namespace App\Http\Controllers;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\AuditLog;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use App\Services\ReceiptImageService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private ReceiptImageService $receiptImages,
    ) {}

    public function index(Request $request)
    {
        $query = Payment::query()->with(['customer', 'collector']);

        $user = $request->user();
        if ($user->isCollector()) {
            $query->where('collector_id', $user->id);
        }

        if ($search = $request->get('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('transaction_number', 'like', "%{$search}%")
                    ->orWhereHas('customer', function ($c) use ($search) {
                        $c->where('name', 'like', "%{$search}%")
                            ->orWhere('customer_code', 'like', "%{$search}%");
                    });
            });
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($method = $request->get('method')) {
            $query->where('payment_method', $method);
        }
        if ($collectorId = $request->get('collector_id')) {
            $query->where('collector_id', $collectorId);
        }
        if ($dateFrom = $request->get('date_from')) {
            $query->whereDate('paid_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->get('date_to')) {
            $query->whereDate('paid_at', '<=', $dateTo);
        }

        $payments = $query->latest('paid_at')->paginate(15)->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'statuses' => PaymentStatus::cases(),
            'methods' => PaymentMethod::cases(),
            'collectors' => $user->isAdmin() ? User::where('role', 'collector')->orderBy('name')->get() : collect(),
        ]);
    }

    public function show(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice', 'cancelledBy', 'receiptSentBy']);

        return view('payments.show', compact('payment'));
    }

    public function receipt(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice']);
        $payment->ensureReceiptShareToken();

        return view('payments.receipt', compact('payment'));
    }

    public function pdf(Payment $payment)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice']);

        $pdf = Pdf::loadView('payments.receipt', compact('payment'))
            ->setPaper('a6', 'portrait');

        return $pdf->download('bukti-'.$payment->transaction_number.'.pdf');
    }

    public function sendReceipt(Request $request, Payment $payment)
    {
        $this->ensureCanManageReceipt($request, $payment);

        if ($payment->isCancelled()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Transaksi dibatalkan — bukti pembayaran tidak dapat dikirim.'], 422);
            }

            return back()->with('error', 'Transaksi dibatalkan — bukti pembayaran tidak dapat dikirim.');
        }

        $payment->load(['customer.package', 'collector', 'allocations.invoice']);

        $payment->forceFill([
            'receipt_sent_at' => now(),
            'receipt_sent_by' => $request->user()->id,
        ]);

        $payment->receipt_share_token = $payment->receipt_share_token ?: Str::random(32);
        $payment->receipt_image_path = $this->receiptImages->store($payment);
        $payment->save();

        AuditLog::record(
            userId: $request->user()->id,
            action: 'payment.receipt_sent',
            entityType: 'payment',
            entityId: $payment->id,
            old: null,
            new: [
                'transaction_number' => $payment->transaction_number,
                'sent_at' => $payment->receipt_sent_at->toDateTimeString(),
                'sent_by' => $payment->collector->name,
            ],
        );

        if ($request->expectsJson()) {
            return response()->json($this->receiptPayload($payment));
        }

        return back()->with('success', 'Bukti pembayaran dibuat dan siap dikirim.');
    }

    public function receiptImage(Request $request, Payment $payment)
    {
        $this->ensureCanManageReceipt($request, $payment);

        return $this->receiptImageResponse($payment, $request->boolean('download'));
    }

    public function publicReceiptImage(string $token)
    {
        $payment = Payment::where('receipt_share_token', $token)->firstOrFail();

        return $this->receiptImageResponse($payment, false, true);
    }

    public function cancel(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:255'],
        ]);

        try {
            $this->paymentService->cancel($payment, $request->user(), $validated['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('payments.show', $payment)->with('success', 'Transaksi dibatalkan. Status tagihan dikembalikan.');
    }

    private function ensureCanManageReceipt(Request $request, Payment $payment): void
    {
        $user = $request->user();

        if (! $user->isAdmin() && $payment->collector_id !== $user->id) {
            abort(403, 'Anda hanya dapat mengirim bukti untuk transaksi milik Anda.');
        }
    }

    private function receiptPayload(Payment $payment): array
    {
        $publicUrl = $payment->publicReceiptUrl();

        return [
            'image_url' => route('payments.receipt.image', $payment),
            'public_url' => $publicUrl,
            'download_url' => route('payments.receipt.image', ['payment' => $payment, 'download' => 1]),
            'sent_at' => format_datetime($payment->receipt_sent_at),
            'sent_by' => $payment->collector->name,
            'filename' => 'bukti-'.$payment->transaction_number.'.png',
            'whatsapp_url' => $payment->whatsappReceiptUrl($publicUrl),
            'share_text' => $this->receiptShareText($payment, $publicUrl),
        ];
    }

    private function receiptShareText(Payment $payment, ?string $publicUrl): string
    {
        $text = "Bukti Pembayaran JRC Media\n"
            ."No: {$payment->transaction_number}\n"
            ."Nama: {$payment->customer->name}\n"
            .'Total: '.format_rupiah($payment->amount_received)."\n"
            .'Metode: '.$payment->payment_method->label()."\n"
            .'Tanggal: '.format_datetime($payment->paid_at);

        if ($publicUrl) {
            $text .= "\n\nLihat bukti: ".$publicUrl;
        }

        return $text;
    }

    private function receiptImageResponse(Payment $payment, bool $download = false, bool $public = false)
    {
        $payment->load(['customer.package', 'collector', 'allocations.invoice']);

        $path = $payment->receipt_image_path;
        if (! $path || ! Storage::disk('local')->exists($path)) {
            if (! $payment->receipt_sent_at) {
                $payment->forceFill([
                    'receipt_sent_at' => now(),
                    'receipt_sent_by' => $payment->collector_id,
                ]);
            }

            $path = $this->receiptImages->store($payment);
            $payment->forceFill(['receipt_image_path' => $path])->save();
        }

        $filename = 'bukti-'.$payment->transaction_number.'.png';

        return response(Storage::disk('local')->get($path), 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => ($download ? 'attachment' : 'inline').'; filename="'.$filename.'"',
            'Cache-Control' => $public ? 'public, max-age=86400' : 'private, max-age=0, must-revalidate',
        ]);
    }
}
