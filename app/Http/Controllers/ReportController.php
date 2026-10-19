<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CashSettlement;
use App\Models\Customer;
use App\Models\Dusun;
use App\Models\Invoice;
use App\Models\Payment;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = $this->gatherReportData($request);

        return view('reports.index', $data);
    }

    public function export(Request $request): StreamedResponse
    {
        $data = $this->gatherReportData($request);
        $payments = $data['payments'];

        $filename = 'transaksi_'.$data['dateFrom'].'_sd_'.$data['dateTo'].'.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ];

        $callback = function () use ($payments) {
            $fh = fopen('php://output', 'w');
            fprintf($fh, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($fh, ['No Transaksi', 'Tanggal', 'Pelanggan', 'Kode', 'Dusun', 'Petugas', 'Metode', 'Nominal', 'Status']);
            foreach ($payments as $p) {
                fputcsv($fh, [
                    $p->transaction_number,
                    $p->paid_at->format('Y-m-d H:i'),
                    $p->customer->name,
                    $p->customer->customer_code,
                    $p->customer->dusun ?? '',
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

    public function exportExcel(Request $request)
    {
        $data = $this->gatherReportData($request);

        $dusunPart = $data['dusun'] ? '_'.str_replace(' ', '-', $data['dusun']) : '';
        $filename = 'laporan_'.$data['dateFrom'].'_sd_'.$data['dateTo'].$dusunPart.'.xls';

        return response()
            ->view('reports.excel', $data)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="'.$filename.'"');
    }

    public function exportPdf(Request $request)
    {
        $data = $this->gatherReportData($request);
        $pdf = Pdf::loadView('reports.pdf', $data);
        $pdf->setPaper('a4', 'portrait');

        $dusunPart = $data['dusun'] ? '_'.str_replace(' ', '-', $data['dusun']) : '';

        return $pdf->download('laporan_'.$data['dateFrom'].'_sd_'.$data['dateTo'].$dusunPart.'.pdf');
    }

    private function gatherReportData(Request $request): array
    {
        $now = Carbon::now();
        $dateFrom = $request->get('date_from', $now->copy()->startOfMonth()->toDateString());
        $dateTo = $request->get('date_to', $now->toDateString());
        $dusun = $request->get('dusun');
        $statusFilter = $request->get('status'); // arrears | paid | null

        $payments = Payment::where('status', PaymentStatus::Success)
            ->whereBetween('paid_at', [$dateFrom.' 00:00:00', $dateTo.' 23:59:59'])
            ->with(['customer', 'collector'])
            ->when($dusun, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('dusun', $dusun)))
            ->latest('paid_at')
            ->get();

        $totalReceived = (int) $payments->sum('amount_received');
        $totalTransactions = $payments->count();

        $byCollector = $payments
            ->groupBy('collector_id')
            ->map(function ($items) {
                return (object) [
                    'collector' => $items->first()->collector,
                    'tx_count' => $items->count(),
                    'total' => (int) $items->sum('amount_received'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $byMethod = $payments
            ->groupBy(fn ($p) => $p->payment_method->value)
            ->map(function ($items, $method) {
                return (object) [
                    'method' => PaymentMethod::from($method),
                    'tx_count' => $items->count(),
                    'total' => (int) $items->sum('amount_received'),
                ];
            })
            ->sortByDesc('total')
            ->values();

        $arrearsQuery = Invoice::with('customer')
            ->unsettled()
            ->when($dusun, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('dusun', $dusun)));
        $arrears = (clone $arrearsQuery)->orderBy('due_date')->limit(50)->get();
        $totalArrears = (int) (clone $arrearsQuery)->sum('amount') - (int) (clone $arrearsQuery)->sum('paid_amount');

        $paidInvoices = Invoice::with('customer')
            ->where('status', InvoiceStatus::Paid)
            ->when($dusun, fn ($q) => $q->whereHas('customer', fn ($c) => $c->where('dusun', $dusun)))
            ->latest('updated_at')
            ->limit(50)
            ->get();

        $cashSettlements = CashSettlement::with('collector')
            ->whereBetween('settlement_date', [$dateFrom, $dateTo])
            ->latest('settlement_date')
            ->limit(20)
            ->get();

        $customerStats = [
            'total' => Customer::when($dusun, fn ($q) => $q->where('dusun', $dusun))->count(),
            'active' => Customer::when($dusun, fn ($q) => $q->where('dusun', $dusun))->where('status', 'active')->count(),
            'inactive' => Customer::when($dusun, fn ($q) => $q->where('dusun', $dusun))->where('status', 'inactive')->count(),
            'suspended' => Customer::when($dusun, fn ($q) => $q->where('dusun', $dusun))->where('status', 'suspended')->count(),
        ];

        $dusunList = Dusun::orderBy('name')->pluck('name');

        return compact(
            'dateFrom', 'dateTo', 'dusun', 'statusFilter', 'dusunList',
            'payments', 'totalReceived', 'totalTransactions',
            'byCollector', 'byMethod', 'arrears', 'totalArrears',
            'paidInvoices', 'cashSettlements', 'customerStats'
        );
    }
}
