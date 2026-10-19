<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    public function run(): void
    {
        $packages = [
            ['name' => 'Paket Hemat', 'speed' => '5 Mbps', 'monthly_price' => 100000, 'description' => 'Cocok untuk penggunaan ringan'],
            ['name' => 'Paket Reguler', 'speed' => '10 Mbps', 'monthly_price' => 150000, 'description' => 'Untuk keluarga, streaming standar'],
            ['name' => 'Paket Premium', 'speed' => '20 Mbps', 'monthly_price' => 200000, 'description' => 'Kebutuhan gaming & streaming HD'],
        ];

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['name' => $pkg['name']],
                array_merge($pkg, ['is_active' => true])
            );
        }
    }
}
