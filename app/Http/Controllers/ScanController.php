<?php

namespace App\Http\Controllers;

use App\Models\CollectionVisit;
use App\Models\Customer;
use App\Services\CodeGeneratorService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RuntimeException;

class ScanController extends Controller
{
    public function __construct(
        private PaymentService $paymentService,
        private CodeGeneratorService $codes,
    ) {}

    public function index()
    {
        return view('scan.index');
    }

    public function lookup(Request $request)
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:80'],
        ]);

        $code = trim($validated['code']);
        $customer = Customer::where('barcode_token', $code)
            ->orWhere('customer_code', $code)
            ->first();

        if (! $customer) {
            return back()->with('error', 'Kode pelanggan tidak terdaftar.')->withInput();
        }

        if ($customer->status->value !== 'active') {
            return redirect()->route('scan.customer', $customer)->with('warning', 'Peringatan: pelanggan berstatus '.$customer->status->label().'. Periksa kembali sebelum menerima pembayaran.');
        }

        return redirect()->route('scan.customer', $customer);
    }

    public function show(Customer $customer)
    {
        $customer->load(['package', 'invoices' => fn ($q) => $q->unsettled()->orderBy('billing_period')]);

        $arrears = $customer->invoices;
        $totalArrears = (int) $arrears->sum(fn ($i) => $i->balance());

        $history = $customer->payments()->latest('paid_at')->limit(5)->get();

        return view('scan.show', compact('customer', 'arrears', 'totalArrears', 'history'));
    }

    public function payForm(Customer $customer)
    {
        $invoices = $customer->invoices()
            ->unsettled()
            ->orderBy('billing_period')
            ->get();

        $totalBalance = (int) $invoices->sum(fn ($i) => $i->balance());
        $idempotencyKey = $this->codes->generateIdempotencyKey();

        return view('scan.pay', compact('customer', 'invoices', 'totalBalance', 'idempotencyKey'));
    }

    public function payStore(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'idempotency_key' => ['required', 'string', 'max:64'],
            'invoices' => ['required', 'array', 'min:1'],
            'invoices.*' => ['required', 'integer', 'exists:invoices,id'],
            'amount_received' => ['required', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['cash', 'transfer', 'qris'])],
            'notes' => ['nullable', 'string', 'max:500'],
            'visit_result' => ['nullable', 'string'],
            'visit_notes' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $this->paymentService->processPayment(
                collector: $request->user(),
                customer: $customer,
                invoiceIds: $validated['invoices'],
                amountReceived: $validated['amount_received'],
                method: $validated['payment_method'],
                notes: $validated['notes'] ?? null,
                idempotencyKey: $validated['idempotency_key'],
            );

            if (! empty($validated['visit_result'])) {
                $this->recordVisit($customer, $request, $validated['visit_result'], $validated['visit_notes'] ?? null);
            }

            return redirect()->route('payments.show', $payment)->with('success', 'Pembayaran berhasil dicatat.');
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->with('error', 'Gagal menyimpan transaksi. Coba lagi atau hubungi admin.');
        }
    }

    private function recordVisit(Customer $customer, Request $request, string $result, ?string $notes): void
    {
        CollectionVisit::create([
            'customer_id' => $customer->id,
            'collector_id' => $request->user()->id,
            'visited_at' => now(),
            'result' => $result,
            'notes' => $notes,
        ]);
    }
}
