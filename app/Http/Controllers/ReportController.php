<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\CashSettlement;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $dateFrom = $request->get('date_from', $now->copy()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', $now->toDateString());

        $payments = Payment::where('status', PaymentStatus::Success)
            ->whereBetween('paid_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->with(['customer', 'collector']);

        $totalReceived = (clone $payments)->sum('amount_received');
        $totalTransactions = (clone $payments)->count();

        $byCollector = (clone $payments)
            ->selectRaw('collector_id, COUNT(*) as tx_count, SUM(amount_received) as total')
            ->groupBy('collector_id')
            ->with('collector')
            ->get();

        $byMethod = (clone $payments)
            ->selectRaw('payment_method, COUNT(*) as tx_count, SUM(amount_received) as total')
            ->groupBy('payment_method')
            ->get();

        $arrears = Invoice::with('customer')
            ->unsettled()
            ->orderBy('due_date')
            ->limit(50)
            ->get();
        $totalArrears = (int) Invoice::unsettled()->sum('amount') - (int) Invoice::unsettled()->sum('paid_amount');

        $cashSettlements = CashSettlement::with('collector')
            ->whereBetween('settlement_date', [$dateFrom, $dateTo])
            ->latest('settlement_date')
            ->limit(20)
            ->get();

        $customerStats = [
            'total' => Customer::count(),
            'active' => Customer::where('status', 'active')->count(),
            'inactive' => Customer::where('status', 'inactive')->count(),
            'suspended' => Customer::where('status', 'suspended')->count(),
        ];

        return view('reports.index', compact(
            'dateFrom', 'dateTo', 'totalReceived', 'totalTransactions',
            'byCollector', 'byMethod', 'arrears', 'totalArrears',
            'cashSettlements', 'customerStats'
        ));
    }

    public function export(Request $request): StreamedResponse
    {
        $dateFrom = $request->get('date_from', Carbon::now()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', Carbon::now()->toDateString());

        $payments = Payment::where('status', PaymentStatus::Success)
            ->whereBetween('paid_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->with(['customer', 'collector'])
            ->latest('paid_at')
            ->get();

        $filename = 'transaksi_'.$dateFrom.'_sd_'.$dateTo.'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($payments) {
            $fh = fopen('php://output', 'w');
            fprintf($fh, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($fh, ['No Transaksi', 'Tanggal', 'Pelanggan', 'Kode', 'Petugas', 'Metode', 'Nominal', 'Status']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->transaction_number,
                    $p->paid_at->format('Y-m-d H:i'),
                    $p->customer->name,
                    $p->customer->customer_code,
                    $p->collector->name,
                    $p->payment_method->label(),
                    $p->amount_received,
                    $p->status->label(),
                ]);
            }
            fclose($fh);
        };

        return response()->stream($callback, 200, $headers);
    }
}
