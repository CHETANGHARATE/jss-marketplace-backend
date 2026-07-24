<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Admin Account
        $admin = User::firstOrCreate(
            ['email' => 'admin@jss.solutions'],
            [
                'name' => 'System Administrator',
                'phone' => '+919876543210',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $admin->assignRole(UserRole::ADMIN->value);

        // 2. Create Vendor / Seller Account
        $seller = User::firstOrCreate(
            ['email' => 'seller@jss.solutions'],
            [
                'name' => 'Supercom Net Vendor',
                'phone' => '+919876543211',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::SELLER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $seller->assignRole(UserRole::SELLER->value);

        // 3. Create Retail Customer Account
        $customer = User::firstOrCreate(
            ['email' => 'customer@jss.solutions'],
            [
                'name' => 'John Doe',
                'phone' => '+919876543212',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $customer->assignRole(UserRole::CUSTOMER->value);
    }
}
