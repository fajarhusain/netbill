<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Payment;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    public function test_full_payment_makes_invoice_paid(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer, ['amount' => 150000]);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'key-full-1',
            'invoices' => [$invoice->id],
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Paid->value, $invoice->status->value);
        $this->assertSame(150000, $invoice->paid_amount);
        $this->assertSame(0, $invoice->balance());
    }

    public function test_partial_payment_sets_invoice_partial(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer, ['amount' => 150000]);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'key-partial-1',
            'invoices' => [$invoice->id],
            'amount_received' => 100000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect();

        $invoice->refresh();
        $this->assertSame(InvoiceStatus::Partial->value, $invoice->status->value);
        $this->assertSame(100000, $invoice->paid_amount);
        $this->assertSame(50000, $invoice->balance());
    }

    public function test_multi_period_payment_allocates_to_multiple_invoices(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoices = $this->makeArrears($customer, 3); // 3 x 150000 = 450000

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'key-multi-1',
            'invoices' => $invoices->pluck('id')->toArray(),
            'amount_received' => 300000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect();

        $invoices->each->refresh();
        // First two invoices fully paid, third untouched
        $this->assertSame(InvoiceStatus::Paid->value, $invoices[0]->status->value);
        $this->assertSame(InvoiceStatus::Paid->value, $invoices[1]->status->value);
        $this->assertSame(InvoiceStatus::Unpaid->value, $invoices[2]->status->value);
        $this->assertSame(0, $invoices[2]->paid_amount);

        $payment = Payment::first();
        $this->assertSame(300000, $payment->amount_received);
        $this->assertSame(300000, $payment->totalAllocated());
    }

    public function test_repeat_request_with_same_idempotency_key_does_not_create_duplicate(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payload = [
            'idempotency_key' => 'dup-key-123',
            'invoices' => [$invoice->id],
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ];

        $this->post("/scan/customer/{$customer->id}/pay", $payload)->assertRedirect();
        $this->post("/scan/customer/{$customer->id}/pay", $payload)->assertRedirect();

        $this->assertSame(1, Payment::where('idempotency_key', 'dup-key-123')->count());
        $this->assertSame(1, Payment::count());
    }

    public function test_overpayment_beyond_balance_rejected(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer, ['amount' => 150000]);

        $this->from("/scan/customer/{$customer->id}/pay")->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'over-1',
            'invoices' => [$invoice->id],
            'amount_received' => 999999,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect("/scan/customer/{$customer->id}/pay")
            ->assertSessionHas('error');

        $this->assertSame(0, Payment::count());
        $this->assertSame(0, $invoice->fresh()->paid_amount);
    }

    public function test_payment_requires_at_least_one_invoice(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'none-1',
            'invoices' => [],
            'amount_received' => 50000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertSessionHasErrors('invoices');
    }

    public function test_payment_status_success_only_after_saved(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'saved-1',
            'invoices' => [$invoice->id],
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ])->assertRedirect();

        $payment = Payment::first();
        $this->assertSame(PaymentStatus::Success->value, $payment->status->value);
        $this->assertNotNull($payment->transaction_number);
        $this->assertNotEmpty($payment->allocations);
    }

    public function test_payment_receipt_and_detail_pages_load(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'receipt-1',
            'invoices' => [$invoice->id],
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ]);

        $payment = Payment::first();
        $this->get("/payments/{$payment->id}")->assertOk()->assertSee('Detail Transaksi');
        $this->get("/payments/{$payment->id}/receipt")->assertOk()->assertSee('BUKTI')->assertSee($payment->transaction_number);
    }

    public function test_pdf_receipt_downloads(): void
    {
        $collector = $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->post("/scan/customer/{$customer->id}/pay", [
            'idempotency_key' => 'pdf-1',
            'invoices' => [$invoice->id],
            'amount_received' => 150000,
            'payment_method' => 'cash',
            'visit_result' => 'paid',
        ]);

        $payment = Payment::first();
        $this->get("/payments/{$payment->id}/pdf")
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
