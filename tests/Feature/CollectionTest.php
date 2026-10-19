<?php

namespace Tests\Feature;

use App\Models\Dusun;
use Tests\TestCase;

class CollectionTest extends TestCase
{
    public function test_index_loads_for_collector(): void
    {
        $this->actingAsCollector();

        $this->get('/collections')->assertOk()->assertSee('Penagihan per Dusun');
    }

    public function test_index_loads_for_admin(): void
    {
        $this->actingAsAdmin();

        $this->get('/collections')->assertOk()->assertSee('Penagihan per Dusun');
    }

    public function test_index_shows_dusun_with_stats(): void
    {
        $this->actingAsAdmin();
        Dusun::firstOrCreate(['name' => 'Krajan'], ['name' => 'Krajan']);
        $customer = $this->makeCustomer(['customer_code' => 'RTW-K', 'dusun' => 'Krajan', 'name' => 'Kustomer Krajan']);
        $this->makeInvoice($customer);

        $this->get('/collections')
            ->assertOk()
            ->assertSee('Krajan')
            ->assertSee('1 pelanggan');
    }

    public function test_show_loads_customers_in_dusun(): void
    {
        $this->actingAsCollector();
        $dusun = Dusun::create(['name' => 'Tegalsari']);
        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'dusun' => 'Tegalsari', 'name' => 'Andi']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'dusun' => 'Tegalsari', 'name' => 'Budi']);
        $this->makeInvoice($a);

        $this->get('/collections/'.$dusun->id)
            ->assertOk()
            ->assertSee('Tegalsari')
            ->assertSee('Andi')
            ->assertSee('Budi');
    }

    public function test_show_with_arrears_filter(): void
    {
        $this->actingAsCollector();
        $dusun = Dusun::create(['name' => 'Ngajaran']);
        $a = $this->makeCustomer(['customer_code' => 'RTW-A', 'dusun' => 'Ngajaran', 'name' => 'Andi']);
        $b = $this->makeCustomer(['customer_code' => 'RTW-B', 'dusun' => 'Ngajaran', 'name' => 'Budi']);
        $this->makeInvoice($a);

        $this->get('/collections/'.$dusun->id.'?filter=arrears')
            ->assertOk()
            ->assertSee('Andi')
            ->assertDontSee('Budi');
    }

    public function test_collections_accessible_to_all_authenticated(): void
    {
        $this->actingAsCollector();
        $this->get('/collections')->assertOk();
    }
}
