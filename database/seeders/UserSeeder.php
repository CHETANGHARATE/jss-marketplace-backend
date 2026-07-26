<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\User;
use App\Models\VendorStore;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds for Admin, Customers, and 16 Realistic Vendors with Stores.
     */
    public function run(): void
    {
        // 1. System Admin Account
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

        // 2. Retail Customer Account
        $customer = User::firstOrCreate(
            ['email' => 'customer@jss.solutions'],
            [
                'name' => 'Rahul Sharma',
                'phone' => '+919876543212',
                'password' => Hash::make('Password123!'),
                'role' => UserRole::CUSTOMER,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
        $customer->assignRole(UserRole::CUSTOMER->value);

        // 3. Seed 16 Realistic Marketplace Vendors & Stores
        $vendors = [
            ['name' => 'Organic Farms India', 'email' => 'vendor1@jss.solutions', 'phone' => '+919876543220', 'city' => 'Pune', 'state' => 'Maharashtra'],
            ['name' => 'Swadeshi Spices Ltd', 'email' => 'vendor2@jss.solutions', 'phone' => '+919876543221', 'city' => 'Nashik', 'state' => 'Maharashtra'],
            ['name' => 'Heritage Handicrafts & Art', 'email' => 'vendor3@jss.solutions', 'phone' => '+919876543222', 'city' => 'Jaipur', 'state' => 'Rajasthan'],
            ['name' => 'Royal Jewellery House', 'email' => 'vendor4@jss.solutions', 'phone' => '+919876543223', 'city' => 'Mumbai', 'state' => 'Maharashtra'],
            ['name' => 'AgriTech Bio-Solutions', 'email' => 'vendor5@jss.solutions', 'phone' => '+919876543224', 'city' => 'Nagpur', 'state' => 'Maharashtra'],
            ['name' => 'Panchamrut Pooja Bhandar', 'email' => 'vendor6@jss.solutions', 'phone' => '+919876543225', 'city' => 'Varanasi', 'state' => 'Uttar Pradesh'],
            ['name' => 'TechPulse Electronics', 'email' => 'vendor7@jss.solutions', 'phone' => '+919876543226', 'city' => 'Bengaluru', 'state' => 'Karnataka'],
            ['name' => 'Authentic Kolhapuri Chappals', 'email' => 'vendor8@jss.solutions', 'phone' => '+919876543227', 'city' => 'Kolhapur', 'state' => 'Maharashtra'],
            ['name' => 'Grandma Kitchen Homemade', 'email' => 'vendor9@jss.solutions', 'phone' => '+919876543228', 'city' => 'Satara', 'state' => 'Maharashtra'],
            ['name' => 'Natural Oils & Herbs India', 'email' => 'vendor10@jss.solutions', 'phone' => '+919876543229', 'city' => 'Kochi', 'state' => 'Kerala'],
            ['name' => 'Ratna Gems & Astro House', 'email' => 'vendor11@jss.solutions', 'phone' => '+919876543230', 'city' => 'Surat', 'state' => 'Gujarat'],
            ['name' => 'Maharashtra Faral Express', 'email' => 'vendor12@jss.solutions', 'phone' => '+919876543231', 'city' => 'Thane', 'state' => 'Maharashtra'],
            ['name' => 'AyurVeda Cosmetics', 'email' => 'vendor13@jss.solutions', 'phone' => '+919876543232', 'city' => 'Haridwar', 'state' => 'Uttarakhand'],
            ['name' => 'Little Angels Kids Store', 'email' => 'vendor14@jss.solutions', 'phone' => '+919876543233', 'city' => 'Delhi', 'state' => 'Delhi'],
            ['name' => 'AutoCare Accessories India', 'email' => 'vendor15@jss.solutions', 'phone' => '+919876543234', 'city' => 'Chennai', 'state' => 'Tamil Nadu'],
            ['name' => 'Taste Of India Pickles', 'email' => 'vendor16@jss.solutions', 'phone' => '+919876543235', 'city' => 'Ahmedabad', 'state' => 'Gujarat'],
        ];

        foreach ($vendors as $v) {
            $user = User::firstOrCreate(
                ['email' => $v['email']],
                [
                    'name' => $v['name'],
                    'phone' => $v['phone'],
                    'password' => Hash::make('Password123!'),
                    'role' => UserRole::SELLER,
                    'status' => UserStatus::ACTIVE,
                    'email_verified_at' => now(),
                    'phone_verified_at' => now(),
                ]
            );
            $user->assignRole(UserRole::SELLER->value);

            VendorStore::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'store_name' => $v['name'],
                    'slug' => Str::slug($v['name']),
                    'store_email' => $v['email'],
                    'store_phone' => $v['phone'],
                    'city' => $v['city'],
                    'state' => $v['state'],
                    'address' => 'Industrial Area Phase 1, ' . $v['city'],
                    'pincode' => '400001',
                    'kyc_status' => 'approved',
                    'status' => 'active',
                    'commission_rate' => 5.00,
                    'description' => 'Verified manufacturer and authorized seller of authentic ' . $v['name'] . ' products.',
                ]
            );
        }
    }
}
