<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $invoiceService) {}

    public function index(Request $request)
    {
        $query = Invoice::query()->with('customer')->withCount('allocations');

        if ($period = $request->get('period')) {
            $query->where('billing_period', $period);
        }
        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($search = $request->get('q')) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('customer_code', 'like', "%{$search}%");
            });
        }
        if ($request->boolean('arrears')) {
            $query->unsettled();
        }

        $invoices = $query->latest('billing_period')->paginate(15)->withQueryString();
        $statuses = InvoiceStatus::cases();
        $currentPeriod = Carbon::now()->format('Y-m');

        return view('invoices.index', compact('invoices', 'statuses', 'currentPeriod'));
    }

    public function create()
    {
        $currentPeriod = Carbon::now()->format('Y-m');
        $customers = Customer::where('status', 'active')->orderBy('name')->get();

        return view('invoices.create', compact('currentPeriod', 'customers'));
    }

    public function store(Request $request)
    {
        $mode = $request->input('mode', 'bulk');

        if ($mode === 'single') {
            $validated = $request->validate([
                'customer_id' => ['required', 'exists:customers,id'],
                'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            ]);

            try {
                $customer = Customer::findOrFail($validated['customer_id']);
                $invoice = $this->invoiceService->createInvoice($customer, $validated['period'], issuerId: auth()->id());

                return redirect()->route('invoices.show', $invoice)->with('success', 'Tagihan dibuat.');
            } catch (\Throwable $e) {
                return back()->withInput()->with('error', $e->getMessage());
            }
        }

        $validated = $request->validate([
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
        ]);

        try {
            $result = $this->invoiceService->generateForPeriod($validated['period'], issuerId: auth()->id());

            return redirect()->route('invoices.index', ['period' => $validated['period']])
                ->with('success', "Tagihan dibuat: {$result['created']} baru, {$result['skipped']} dilewati (sudah ada).");
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(Invoice $invoice)
    {
        $invoice->load(['customer.package', 'allocations.payment.collector']);

        return view('invoices.show', compact('invoice'));
    }

    public function cancel(Request $request, Invoice $invoice)
    {
        $validated = $request->validate(['reason' => ['required', 'string', 'min:5', 'max:255']]);

        try {
            $this->invoiceService->cancel($invoice, auth()->id(), $validated['reason']);
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Tagihan dibatalkan.');
    }

    public function destroy(Invoice $invoice)
    {
        try {
            $this->invoiceService->delete($invoice, auth()->id());
        } catch (\Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('invoices.index')->with('success', 'Tagihan berhasil dihapus.');
    }
}
