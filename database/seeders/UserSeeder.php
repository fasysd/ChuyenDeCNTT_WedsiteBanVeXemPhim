<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // User::truncate();

        User::create([
            'username' => 'admin',
            'full_name' => 'Administrator',
            'email' => 'admin@gmail.com',
            'phone' => '0900000001',
            'password' => Hash::make('123456'),
            'role' => 'ADMIN',
        ]);

        User::create([
            'username' => 'staff',
            'full_name' => 'Staff',
            'email' => 'staff@gmail.com',
            'phone' => '0900000002',
            'password' => Hash::make('123456'),
            'role' => 'STAFF',
        ]);

        User::create([
            'username' => 'customer1',
            'full_name' => 'Customer',
            'email' => 'customer1@gmail.com',
            'phone' => '0900000003',
            'password' => Hash::make('123456'),
            'role' => 'CUSTOMER',
        ]);

        User::create([
            'username' => 'customer2',
            'full_name' => 'Customer Two',
            'email' => 'customer2@gmail.com',
            'phone' => '0900000004',
            'password' => Hash::make('123456'),
            'role' => 'CUSTOMER',
        ]);
    }
}