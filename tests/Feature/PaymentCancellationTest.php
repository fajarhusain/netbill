<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Services\PaymentService;
use Tests\TestCase;

class PaymentCancellationTest extends TestCase
{
    public function test_admin_can_cancel_payment_and_invoice_reverts(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cancel-1',
        );

        $this->assertSame(InvoiceStatus::Paid->value, $invoice->fresh()->status->value);

        $this->post("/payments/{$payment->id}/cancel", [
            'reason' => 'Kesalahan input oleh petugas',
        ])->assertRedirect();

        $payment->refresh();
        $invoice->refresh();
        $this->assertSame(PaymentStatus::Cancelled->value, $payment->status->value);
        $this->assertNotNull($payment->cancelled_at);
        $this->assertSame('Kesalahan input oleh petugas', $payment->cancellation_reason);
        $this->assertSame(InvoiceStatus::Unpaid->value, $invoice->status->value);
        $this->assertSame(0, $invoice->paid_amount);
    }

    public function test_collector_cannot_cancel_payment(): void
    {
        $this->actingAsCollector();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cancel-2',
        );

        $this->post("/payments/{$payment->id}/cancel", [
            'reason' => 'coba batalkan',
        ])->assertForbidden();
    }

    public function test_cancelled_payment_still_visible_in_history(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cancel-3',
        );

        app(PaymentService::class)->cancel($payment, $admin, 'Tes audit');

        $this->get('/payments')->assertOk()->assertSee($payment->transaction_number);
        $this->get("/payments/{$payment->id}")->assertOk()->assertSee('Dibatalkan');
    }

    public function test_cancel_requires_reason(): void
    {
        $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cancel-4',
        );

        $this->post("/payments/{$payment->id}/cancel", ['reason' => ''])->assertSessionHasErrors('reason');
    }

    public function test_cannot_cancel_already_cancelled_payment(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $payment = app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'cancel-5',
        );

        app(PaymentService::class)->cancel($payment, $admin, 'Pertama');

        $this->expectException(\RuntimeException::class);
        app(PaymentService::class)->cancel($payment->fresh(), $admin, 'Kedua');
    }
}
