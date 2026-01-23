<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        // Создаём главную организацию
        $organization = Organization::create([
            'name' => 'C2D Admin',
            'city' => 'Москва',
            'country' => 'Россия',
            'status' => 'active',
        ]);

        // Создаём главного админа
        $admin = User::create([
            'organization_id' => $organization->id,
            'name' => 'Super Admin',
            'first_name' => 'Admin',
            'last_name' => 'Super',
            'email' => 'admin@c2d.ru',
            'password' => Hash::make('Admin2026!'),
            'status' => 'active',
            'two_factor_verified' => true,
            'two_factor_verified_at' => now(),
        ]);

        $admin->assignRole('super_admin');
    }
}
