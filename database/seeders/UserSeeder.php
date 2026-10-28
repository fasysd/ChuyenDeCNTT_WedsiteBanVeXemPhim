<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Quản trị viên (Admin)
        User::create([
            'username' => 'admin',
            'full_name' => 'Nguyễn Quản Trị',
            'email' => 'admin@gmail.com',
            'phone' => '0901234567',
            'password' => Hash::make('123456'),
            'role' => 'ADMIN',
        ]);

        // 2. Nhân viên rạp (Staff)
        User::create([
            'username' => 'staff1',
            'full_name' => 'Trần Nhân Viên',
            'email' => 'staff1@gmail.com',
            'phone' => '0902345678',
            'password' => Hash::make('123456'),
            'role' => 'STAFF',
        ]);

        User::create([
            'username' => 'staff2',
            'full_name' => 'Lê Soát Vé',
            'email' => 'staff2@gmail.com',
            'phone' => '0903456789',
            'password' => Hash::make('123456'),
            'role' => 'STAFF',
        ]);

        // 3. Khách hàng (Customer)
        User::create([
            'username' => 'customer1',
            'full_name' => 'Nguyễn Văn Khách',
            'email' => 'customer1@gmail.com',
            'phone' => '0904567890',
            'password' => Hash::make('123456'),
            'role' => 'CUSTOMER',
        ]);

        User::create([
            'username' => 'customer2',
            'full_name' => 'Phạm Thị Thùy',
            'email' => 'customer2@gmail.com',
            'phone' => '0905678901',
            'password' => Hash::make('123456'),
            'role' => 'CUSTOMER',
        ]);

        User::create([
            'username' => 'nguyenvanan',
            'full_name' => 'Nguyễn Văn An',
            'email' => 'an.nguyen@gmail.com',
            'phone' => '0906789012',
            'password' => Hash::make('123456'),
            'role' => 'CUSTOMER',
        ]);
    }
}
