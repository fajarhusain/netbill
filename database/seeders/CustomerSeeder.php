<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Package;
use App\Services\CodeGeneratorService;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $generator = app(CodeGeneratorService::class);
        $packages = Package::pluck('id', 'name');

        $customers = [
            ['name' => 'Budi Santoso', 'phone' => '081234567801', 'address' => 'Jl. Melati No. 1, RT 01/RW 01', 'dusun' => 'Krajan', 'package' => 'Paket Reguler', 'due_day' => 10, 'status' => 'active', 'start' => '2026-01-05'],
            ['name' => 'Siti Aminah', 'phone' => '081234567802', 'address' => 'Jl. Mawar No. 2, RT 01/RW 01', 'dusun' => 'Krajan', 'package' => 'Paket Hemat', 'due_day' => 10, 'status' => 'active', 'start' => '2026-02-10'],
            ['name' => 'Agus Wijaya', 'phone' => '081234567803', 'address' => 'Jl. Anggrek No. 3, RT 02/RW 01', 'dusun' => 'Tegalsari', 'package' => 'Paket Premium', 'due_day' => 15, 'status' => 'active', 'start' => '2026-03-01'],
            ['name' => 'Dewi Lestari', 'phone' => '081234567804', 'address' => 'Jl. Kenanga No. 4, RT 02/RW 01', 'dusun' => 'Tegalsari', 'package' => 'Paket Reguler', 'due_day' => 10, 'status' => 'active', 'start' => '2026-01-20'],
            ['name' => 'Rudi Hartono', 'phone' => '081234567805', 'address' => 'Jl. Dahlia No. 5, RT 03/RW 02', 'dusun' => 'Ngajaran', 'package' => 'Paket Hemat', 'due_day' => 12, 'status' => 'active', 'start' => '2026-04-15'],
            ['name' => 'Maya Putri', 'phone' => '081234567806', 'address' => 'Jl. Cempaka No. 6, RT 03/RW 02', 'dusun' => 'Ngajaran', 'package' => 'Paket Premium', 'due_day' => 15, 'status' => 'suspended', 'start' => '2026-05-01'],
        ];

        foreach ($customers as $c) {
            $code = $generator->generateCustomerCode();
            Customer::updateOrCreate(
                ['customer_code' => $code],
                [
                    'customer_code' => $code,
                    'barcode_token' => $generator->generateBarcodeToken(),
                    'name' => $c['name'],
                    'phone' => $c['phone'],
                    'address' => $c['address'],
                    'dusun' => $c['dusun'],
                    'package_id' => $packages[$c['package']] ?? null,
                    'monthly_fee' => $this->priceForPackage($c['package']),
                    'started_at' => $c['start'],
                    'due_day' => $c['due_day'],
                    'status' => CustomerStatus::from($c['status']),
                    'notes' => null,
                ]
            );
        }
    }

    private function priceForPackage(string $name): int
    {
        return match ($name) {
            'Paket Hemat' => 100000,
            'Paket Reguler' => 150000,
            'Paket Premium' => 200000,
            default => 0,
        };
    }
}
