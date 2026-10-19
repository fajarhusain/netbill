<?php

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use Tests\TestCase;

class ScanTest extends TestCase
{
    public function test_scan_page_loads(): void
    {
        $this->actingAsCollector();

        $this->get('/scan')->assertOk()->assertSee('Scan Pelanggan');
    }

    public function test_scan_valid_barcode_token_finds_customer(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer();

        $this->post('/scan/lookup', ['code' => $customer->barcode_token])
            ->assertRedirect('/scan/customer/'.$customer->id);
    }

    public function test_scan_valid_customer_code_finds_customer(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer(['customer_code' => 'RTW-9001']);

        $this->post('/scan/lookup', ['code' => 'RTW-9001'])
            ->assertRedirect('/scan/customer/'.$customer->id);
    }

    public function test_scan_invalid_code_returns_error(): void
    {
        $this->actingAsCollector();

        $this->from('/scan')->post('/scan/lookup', ['code' => 'NOT-EXIST-999'])
            ->assertRedirect('/scan')
            ->assertSessionHas('error');
    }

    public function test_scan_inactive_customer_shows_warning_but_accessible(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer([
            'status' => CustomerStatus::Suspended,
            'customer_code' => 'RTW-SUSP',
        ]);

        $this->post('/scan/lookup', ['code' => 'RTW-SUSP'])
            ->assertRedirect('/scan/customer/'.$customer->id)
            ->assertSessionHas('warning');
    }

    public function test_scan_customer_detail_shows_arrears(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $this->makeArrears($customer, 2);

        $this->get('/scan/customer/'.$customer->id)
            ->assertOk()
            ->assertSee($customer->name)
            ->assertSee('Total Tunggakan');
    }

    public function test_scan_does_not_change_invoice_status(): void
    {
        $this->actingAsCollector();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);

        $this->get('/scan/customer/'.$customer->id)->assertOk();

        $this->assertSame('unpaid', $invoice->fresh()->status->value);
        $this->assertSame(0, (int) $invoice->fresh()->paid_amount);
    }
}
