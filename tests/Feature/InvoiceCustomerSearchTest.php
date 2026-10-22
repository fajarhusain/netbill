<?php

namespace Tests\Feature;

use App\Enums\CustomerStatus;
use Tests\TestCase;

class InvoiceCustomerSearchTest extends TestCase
{
    public function test_create_page_lists_active_customers_for_search_picker(): void
    {
        $this->actingAsAdmin();

        $this->makeCustomer(['customer_code' => 'RTW-0101', 'name' => 'Siti Aminah']);
        $this->makeCustomer(['customer_code' => 'RTW-0102', 'name' => 'Andi Wijaya', 'status' => CustomerStatus::Inactive]);

        $response = $this->get('/invoices/create')->assertOk();

        $response->assertSee('customer-search', false);
        $response->assertSee('RTW-0101');
        $response->assertDontSee('RTW-0102');
    }

    public function test_single_invoice_still_created_for_selected_customer(): void
    {
        $this->actingAsAdmin();
        $customer = $this->makeCustomer(['customer_code' => 'RTW-0201']);

        $this->post('/invoices', [
            'mode' => 'single',
            'customer_id' => $customer->id,
            'period' => '2026-10',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'billing_period' => '2026-10',
        ]);
    }

    public function test_create_page_renders_search_metadata_for_customer(): void
    {
        $this->actingAsAdmin();
        $this->makeCustomer([
            'customer_code' => 'RTW-0301',
            'name' => 'Rina Kartika',
            'phone' => '081298765432',
            'dusun' => 'Mangonan',
        ]);

        $this->get('/invoices/create')
            ->assertOk()
            ->assertSee('Rina Kartika')
            ->assertSee('081298765432')
            ->assertSee('Mangonan');
    }
}
