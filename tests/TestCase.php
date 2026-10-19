<?php

namespace Tests;

use App\Enums\CustomerStatus;
use App\Enums\InvoiceStatus;
use App\Models\Customer;
use App\Models\Dusun;
use App\Models\Invoice;
use App\Models\Package;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;

    protected function adminUser(): User
    {
        return User::factory()->admin()->create();
    }

    protected function collector(): User
    {
        return User::factory()->collector()->create();
    }

    protected function actingAsAdmin(): User
    {
        $user = $this->adminUser();
        $this->actingAs($user);

        return $user;
    }

    protected function actingAsCollector(): User
    {
        $user = $this->collector();
        $this->actingAs($user);

        return $user;
    }

    protected function makePackage(array $overrides = []): Package
    {
        return Package::create(array_merge([
            'name' => 'Paket Reguler',
            'speed' => '10 Mbps',
            'monthly_price' => 150000,
            'is_active' => true,
        ], $overrides));
    }

    protected function makeCustomer(array $overrides = []): Customer
    {
        $package = $overrides['package_id'] ?? null;
        if (! $package instanceof Package) {
            $package = $this->makePackage();
        }

        $dusun = $overrides['dusun'] ?? null;
        if ($dusun) {
            Dusun::firstOrCreate(['name' => $dusun], ['name' => $dusun]);
        }

        return Customer::create(array_merge([
            'customer_code' => 'RTW-0001',
            'barcode_token' => 'token-'.uniqid(),
            'name' => 'Budi Santoso',
            'phone' => '081234567890',
            'address' => 'Jl. Melati No. 1',
            'dusun' => null,
            'package_id' => $package->id,
            'monthly_fee' => 150000,
            'started_at' => '2026-01-05',
            'due_day' => 10,
            'status' => CustomerStatus::Active,
            'notes' => null,
        ], $overrides));
    }

    protected function makeInvoice(Customer $customer, array $overrides = []): Invoice
    {
        return Invoice::create(array_merge([
            'invoice_number' => 'INV-'.uniqid(),
            'customer_id' => $customer->id,
            'billing_period' => '2026-10',
            'issued_at' => '2026-10-01',
            'due_date' => '2026-10-10',
            'amount' => 150000,
            'paid_amount' => 0,
            'status' => InvoiceStatus::Unpaid,
        ], $overrides));
    }

    protected function makeArrears(Customer $customer, int $count = 3): Collection
    {
        $invoices = collect();
        $base = Carbon::parse('2026-01-01');
        for ($i = 0; $i < $count; $i++) {
            $period = $base->copy()->addMonths($i)->format('Y-m');
            $invoices->push($this->makeInvoice($customer, [
                'invoice_number' => 'INV-'.$period.'-'.$i,
                'billing_period' => $period,
                'issued_at' => $base->copy()->addMonths($i)->toDateString(),
                'due_date' => $base->copy()->addMonths($i)->day(10)->toDateString(),
            ]));
        }

        return $invoices;
    }
}
