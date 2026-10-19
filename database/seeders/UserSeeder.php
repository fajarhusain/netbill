<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'username' => 'admin',
                'password' => Hash::make('password'),
                'role' => Role::Admin,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'petugas01'],
            [
                'name' => 'Petugas 01',
                'username' => 'petugas01',
                'password' => Hash::make('password'),
                'role' => Role::Collector,
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['username' => 'petugas02'],
            [
                'name' => 'Petugas 02',
                'username' => 'petugas02',
                'password' => Hash::make('password'),
                'role' => Role::Collector,
                'is_active' => true,
            ]
        );
    }
}
