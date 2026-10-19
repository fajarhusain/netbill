<?php

namespace Database\Seeders;

use App\Models\Dusun;
use Illuminate\Database\Seeder;

class DusunSeeder extends Seeder
{
    public function run(): void
    {
        $dusun = ['Krajan', 'Tegalsari', 'Ngajaran'];

        foreach ($dusun as $name) {
            Dusun::updateOrCreate(['name' => $name], ['name' => $name]);
        }
    }
}
