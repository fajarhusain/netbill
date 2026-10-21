<?php

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    public function test_invoice_index_shows_unpaid_invoices(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->get('/invoices')->assertOk()->assertSee($invoice->invoice_number);
    }

    public function test_create_invoice_page_loads(): void
    {
        $this->actingAsAdmin();
        $this->get('/invoices/create')->assertOk()->assertSee('Buat Tagihan');
    }

    public function test_admin_can_create_single_invoice(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();

        $this->post('/invoices', [
            'mode' => 'single',
            'customer_id' => $customer->id,
            'period' => '2026-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'billing_period' => '2026-10',
            'amount' => $customer->monthly_fee,
        ]);
    }

    public function test_duplicate_invoice_for_same_period_prevented(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $this->makeInvoice($customer, ['billing_period' => '2026-10']);

        $this->from('/invoices/create')->post('/invoices', [
            'mode' => 'single',
            'customer_id' => $customer->id,
            'period' => '2026-10',
        ])->assertRedirect('/invoices/create');

        $this->assertSame(1, Invoice::where('customer_id', $customer->id)->where('billing_period', '2026-10')->count());
    }

    public function test_bulk_generation_creates_for_active_customers_only(): void
    {
        $this->actingAsAdmin();
        $active = $this->makeCustomer(['customer_code' => 'RTW-A', 'started_at' => '2026-09-01']);
        $inactive = $this->makeCustomer(['customer_code' => 'RTW-I', 'started_at' => '2026-09-01', 'status' => CustomerStatus::Inactive]);

        $this->post('/invoices', [
            'mode' => 'bulk',
            'period' => '2026-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', ['customer_id' => $active->id, 'billing_period' => '2026-10']);
        $this->assertDatabaseMissing('invoices', ['customer_id' => $inactive->id, 'billing_period' => '2026-10']);
    }

    public function test_bulk_generation_skips_existing_invoices(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer(['started_at' => '2026-09-01']);
        $this->makeInvoice($customer, ['billing_period' => '2026-10']);

        $response = app(InvoiceService::class)->generateForPeriod('2026-10');

        $this->assertSame(0, $response['created']);
        $this->assertSame(1, $response['skipped']);
    }

    public function test_invoice_period_format_validated(): void
    {
        $this->actingAsAdmin();

        $this->post('/invoices', [
            'mode' => 'bulk',
            'period' => 'invalid',
        ])->assertSessionHasErrors(['period']);
    }

    public function test_admin_can_delete_invoice_without_payments(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->delete("/invoices/{$invoice->id}")->assertRedirect('/invoices');

        $this->assertDatabaseMissing('invoices', ['id' => $invoice->id]);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'invoice.deleted',
            'entity_type' => 'invoice',
            'entity_id' => $invoice->id,
        ]);
    }

    public function test_cannot_delete_invoice_that_has_payments(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        app(\App\Services\PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'del-1',
        );

        $this->from("/invoices/{$invoice->id}")
            ->delete("/invoices/{$invoice->id}")
            ->assertRedirect("/invoices/{$invoice->id}")
            ->assertSessionHas('error');

        $this->assertDatabaseHas('invoices', ['id' => $invoice->id]);
    }

    public function test_collector_cannot_delete_invoice(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->delete("/invoices/{$invoice->id}")->assertForbidden();
    }

    public function test_show_page_has_delete_button_when_no_payments(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->get("/invoices/{$invoice->id}")->assertOk()->assertSee('Hapus');
    }

    public function test_index_page_has_delete_button_for_invoice_without_payments(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->get('/invoices')->assertOk()->assertSee('confirmDeleteInvoice');
    }

    public function test_index_page_hides_delete_button_for_invoice_with_payments(): void
    {
        $this->actingAsAdmin();
        $collector = $this->collector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        app(\App\Services\PaymentService::class)->processPayment(
            collector: $collector,
            customer: $customer,
            invoiceIds: [$invoice->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'idx-1',
        );

        $this->get('/invoices')->assertOk()->assertDontSee('confirmDeleteInvoice(' . $invoice->id . ',');
    }
}
