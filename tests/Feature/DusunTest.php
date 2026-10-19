<?php

namespace Tests\Feature;

use App\Models\Dusun;
use Tests\TestCase;

class DusunTest extends TestCase
{
    public function test_index_loads_for_admin(): void
    {
        $this->actingAsAdmin();
        $this->get('/dusun')->assertOk()->assertSee('Data Dusun');
    }

    public function test_collector_cannot_access_dusun(): void
    {
        $this->actingAsCollector();
        $this->get('/dusun')->assertForbidden();
        $this->get('/dusun/create')->assertForbidden();
    }

    public function test_admin_can_create_dusun(): void
    {
        $this->actingAsAdmin();

        $this->post('/dusun', ['name' => 'Krajan'])
            ->assertRedirect('/dusun');

        $this->assertDatabaseHas('dusun', ['name' => 'Krajan']);
    }

    public function test_duplicate_dusun_rejected(): void
    {
        $this->actingAsAdmin();
        Dusun::create(['name' => 'Tegalsari']);

        $this->from('/dusun/create')->post('/dusun', ['name' => 'Tegalsari'])
            ->assertSessionHasErrors(['name']);
    }

    public function test_admin_can_rename_dusun_and_customers_cascade(): void
    {
        $this->actingAsAdmin();
        $dusun = Dusun::create(['name' => 'Krajan Lama']);
        $customer = $this->makeCustomer([
            'customer_code' => 'RTW-DSN',
            'dusun' => 'Krajan Lama',
        ]);

        $this->put('/dusun/'.$dusun->id, ['name' => 'Krajan Baru'])
            ->assertRedirect('/dusun');

        $this->assertSame('Krajan Baru', $dusun->fresh()->name);
        $this->assertSame('Krajan Baru', $customer->fresh()->dusun);
    }

    public function test_cannot_delete_dusun_still_used_by_customer(): void
    {
        $this->actingAsAdmin();
        $dusun = Dusun::create(['name' => 'Ngajaran']);
        $this->makeCustomer(['customer_code' => 'RTW-NGJ', 'dusun' => 'Ngajaran']);

        $this->from('/dusun')->delete('/dusun/'.$dusun->id)
            ->assertRedirect('/dusun')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('dusun', ['id' => $dusun->id]);
    }

    public function test_can_delete_unused_dusun(): void
    {
        $this->actingAsAdmin();
        $dusun = Dusun::create(['name' => 'Kosong']);

        $this->delete('/dusun/'.$dusun->id)->assertRedirect('/dusun');

        $this->assertDatabaseMissing('dusun', ['id' => $dusun->id]);
    }

    public function test_customer_validation_rejects_unknown_dusun(): void
    {
        $this->actingAsAdmin();
        $package = $this->makePackage();

        $this->from('/customers/create')->post('/customers', [
            'name' => 'Andi',
            'dusun' => 'Dusun Tidak Terdaftar',
            'package_id' => $package->id,
            'monthly_fee' => 100000,
            'due_day' => 10,
            'status' => 'active',
        ])->assertSessionHasErrors(['dusun']);
    }
}
