<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CashSettlement;
use App\Models\CollectionVisit;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $now = Carbon::now();
        $currentPeriod = $now->format('Y-m');
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $monthStart = $now->copy()->startOfMonth();
        $monthEnd = $now->copy()->endOfMonth();

        $isAdmin = $request->user()->isAdmin();

        $totalCustomers = Customer::count();
        $activeCustomers = Customer::where('status', 'active')->count();

        $invoicesQuery = Invoice::query();
        $paymentsQuery = Payment::where('status', PaymentStatus::Success);

        if (! $isAdmin) {
            $paymentsQuery->where('collector_id', $request->user()->id);
            $visitsQuery = CollectionVisit::where('collector_id', $request->user()->id);
        } else {
            $visitsQuery = CollectionVisit::query();
        }

        $currentPeriodInvoices = Invoice::where('billing_period', $currentPeriod)->get();
        $totalInvoicedCurrentPeriod = (int) $currentPeriodInvoices->sum('amount');
        $arrearsTotal = (int) Invoice::unsettled()->sum('amount') - (int) Invoice::unsettled()->sum('paid_amount');
        $unpaidCustomerCount = Customer::whereHas('invoices', function ($q) {
            $q->whereIn('status', [InvoiceStatus::Unpaid, InvoiceStatus::Partial]);
        })->count();

        $paidToday = (clone $paymentsQuery)->whereBetween('paid_at', [$todayStart, $todayEnd])->sum('amount_received');
        $paidThisMonth = (clone $paymentsQuery)->whereBetween('paid_at', [$monthStart, $monthEnd])->sum('amount_received');

        $visitsToday = (clone $visitsQuery)->whereBetween('visited_at', [$todayStart, $todayEnd])->count();

        $cashUnsettled = (clone $paymentsQuery)
            ->where('payment_method', PaymentMethod::Cash)
            ->sum('amount_received');

        $settledCash = CashSettlement::whereNotNull('confirmed_at')->sum('received_amount');
        $pendingSettlement = max(0, $cashUnsettled - $settledCash);

        $recentPayments = (clone $paymentsQuery)
            ->with(['customer', 'collector'])
            ->latest('paid_at')
            ->limit(8)
            ->get();

        $arrears = Invoice::with('customer')
            ->unsettled()
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return view('dashboard', [
            'isAdmin' => $isAdmin,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'currentPeriod' => $currentPeriod,
            'totalInvoicedCurrentPeriod' => $totalInvoicedCurrentPeriod,
            'arrearsTotal' => $arrearsTotal,
            'unpaidCustomerCount' => $unpaidCustomerCount,
            'paidToday' => (int) $paidToday,
            'paidThisMonth' => (int) $paidThisMonth,
            'visitsToday' => $visitsToday,
            'pendingSettlement' => $pendingSettlement,
            'recentPayments' => $recentPayments,
            'arrears' => $arrears,
            'now' => $now,
        ]);
    }
}
