<?php

namespace Tests\Feature;

use App\Services\PaymentService;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    public function test_dashboard_shows_real_statistics(): void
    {
        $admin = $this->actingAsAdmin();
        $collector = $this->collector();
        $c1 = $this->makeCustomer(['customer_code' => 'RTW-001']);
        $c2 = $this->makeCustomer(['customer_code' => 'RTW-002', 'name' => 'Siti']);
        $inv1 = $this->makeInvoice($c1);
        $inv2 = $this->makeInvoice($c2);

        app(PaymentService::class)->processPayment(
            collector: $collector,
            customer: $c1,
            invoiceIds: [$inv1->id],
            amountReceived: 150000,
            method: 'cash',
            idempotencyKey: 'dash-1',
        );

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('2', false) // total customers
            ->assertSee('Rp150.000');
    }

    public function test_collector_dashboard_loads(): void
    {
        $this->actingAsCollector();
        $this->get('/dashboard')->assertOk()->assertSee('Mulai Scan');
    }
}
