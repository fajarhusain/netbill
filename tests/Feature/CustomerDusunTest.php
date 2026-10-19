<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Dusun;
use Tests\TestCase;

class CustomerDusunTest extends TestCase
{
    public function test_by_dusun_page_loads_for_admin(): void
    {
        $this->actingAsAdmin();

        $this->get('/customers/by-dusun')->assertOk()->assertSee('Pelanggan per Dusun');
    }

    public function test_collector_cannot_access_by_dusun(): void
    {
        $this->actingAsCollector();

        $this->get('/customers/by-dusun')->assertForbidden();
    }

    public function test_by_dusun_groups_customers_under_dusun(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'dusun' => 'Krajan', 'name' => 'Andi']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'dusun' => 'Tegalsari', 'name' => 'Budi']);
        Customer::whereKey($a->id)->update(['dusun' => 'Krajan']);
        Customer::whereKey($b->id)->update(['dusun' => 'Tegalsari']);

        $response = $this->get('/customers/by-dusun')->assertOk();

        $response->assertSee('Krajan')->assertSee('Tegalsari');
        $response->assertSee('Andi')->assertSee('Budi');
    }

    public function test_by_dusun_filter_by_specific_dusun(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'name' => 'Andi']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'name' => 'Budi']);
        Customer::whereKey($a->id)->update(['dusun' => 'Krajan']);
        Customer::whereKey($b->id)->update(['dusun' => 'Tegalsari']);

        $this->get('/customers/by-dusun?dusun=Krajan')
            ->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Budi');
    }

    public function test_index_filter_by_dusun(): void
    {
        $this->actingAsAdmin();
        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'name' => 'Andi']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'name' => 'Budi']);
        Customer::whereKey($a->id)->update(['dusun' => 'Krajan']);
        Customer::whereKey($b->id)->update(['dusun' => 'Tegalsari']);

        $this->get('/customers?dusun=Krajan')
            ->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Budi');
    }

    public function test_customer_can_be_created_with_dusun(): void
    {
        $this->actingAsAdmin();
        $package = $this->makePackage();
        Dusun::firstOrCreate(['name' => 'Krajan'], ['name' => 'Krajan']);

        $this->post('/customers', [
            'customer_code' => 'RTW-DSN',
            'name' => 'Citra',
            'phone' => '081200000000',
            'address' => 'Jl. Test',
            'dusun' => 'Krajan',
            'package_id' => $package->id,
            'monthly_fee' => 150000,
            'due_day' => 10,
            'status' => 'active',
        ])->assertRedirect();

        $this->assertDatabaseHas('customers', [
            'customer_code' => 'RTW-DSN',
            'dusun' => 'Krajan',
        ]);
    }
}
