<?php

namespace Tests\Feature;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Dusun;
use App\Models\Payment;
use App\Models\PaymentAllocation;
use Tests\TestCase;

class ReportTest extends TestCase
{
    public function test_report_index_loads_with_data(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $invoice = $this->makeInvoice($customer);
        $collector = $this->collector();

        $payment = Payment::create([
            'transaction_number' => 'PAY-TEST-0001',
            'idempotency_key' => 'key-'.uniqid(),
            'customer_id' => $customer->id,
            'collector_id' => $collector->id,
            'paid_at' => now(),
            'amount_received' => 50000,
            'payment_method' => PaymentMethod::Cash,
            'status' => PaymentStatus::Success,
        ]);
        PaymentAllocation::create([
            'payment_id' => $payment->id,
            'invoice_id' => $invoice->id,
            'amount_allocated' => 50000,
        ]);

        $this->get('/reports')->assertOk()->assertSee('Laporan Keuangan');
    }

    public function test_report_export_csv_loads(): void
    {
        $this->actingAsAdmin();
        $this->get('/reports/export')->assertOk();
    }

    public function test_report_export_excel_loads(): void
    {
        $this->actingAsAdmin();
        $this->get('/reports/export/excel')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.ms-excel; charset=UTF-8');
    }

    public function test_report_export_pdf_loads(): void
    {
        $this->actingAsAdmin();
        $this->get('/reports/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_collector_cannot_access_reports(): void
    {
        $this->actingAsCollector();
        $this->get('/reports')->assertForbidden();
        $this->get('/reports/export/excel')->assertForbidden();
        $this->get('/reports/export/pdf')->assertForbidden();
    }

    public function test_dusun_filter_scopes_payments_and_arrears(): void
    {
        $this->actingAsAdmin();
        Dusun::firstOrCreate(['name' => 'Krajan'], ['name' => 'Krajan']);
        Dusun::firstOrCreate(['name' => 'Tegalsari'], ['name' => 'Tegalsari']);

        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'name' => 'Andi', 'dusun' => 'Krajan']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'name' => 'Budi', 'dusun' => 'Tegalsari']);
        $this->makeInvoice($a, ['billing_period' => '2026-10']);
        $this->makeInvoice($b, ['billing_period' => '2026-10']);

        $response = $this->get('/reports?dusun=Krajan')->assertOk();

        $response->assertSee('Krajan');
        $response->assertSee('Andi');
        $response->assertDontSee('Budi');
    }

    public function test_status_filter_paid_shows_only_paid_section(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer(['customer_code' => 'RTW-P', 'name' => 'Paid Customer']);
        $this->makeInvoice($customer, ['status' => InvoiceStatus::Paid, 'paid_amount' => 150000]);

        $unpaidCustomer = $this->makeCustomer(['customer_code' => 'RTW-U', 'name' => 'Unpaid Customer']);
        $this->makeInvoice($unpaidCustomer);

        $response = $this->get('/reports?status=paid')->assertOk();

        $response->assertSee('Paid Customer');
        $response->assertDontSee('Unpaid Customer');
        $response->assertDontSee('Pelanggan Menunggak');
    }

    public function test_status_filter_arrears_shows_only_arrears_section(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer(['customer_code' => 'RTW-P', 'name' => 'Paid Customer']);
        $this->makeInvoice($customer, ['status' => InvoiceStatus::Paid, 'paid_amount' => 150000]);

        $unpaidCustomer = $this->makeCustomer(['customer_code' => 'RTW-U', 'name' => 'Unpaid Customer']);
        $this->makeInvoice($unpaidCustomer);

        $response = $this->get('/reports?status=arrears')->assertOk();

        $response->assertSee('Pelanggan Menunggak');
        $response->assertSee('Unpaid Customer');
        $response->assertDontSee('Paid Customer');
    }

    public function test_default_status_shows_both_sections(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer();
        $this->makeInvoice($customer);

        $response = $this->get('/reports')->assertOk();

        $response->assertSee('Pelanggan Menunggak');
        $response->assertSee('Pelanggan Lunas');
    }
}
