<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guards = ['sanctum', 'web', 'api'];

        // Create Permissions
        $permissions = [
            // Admin permissions
            'manage-users',
            'manage-sellers',
            'manage-categories',
            'manage-products-global',
            'manage-orders-global',
            'manage-settings',
            'manage-commissions',
            'view-analytics',

            // Seller permissions
            'manage-own-store',
            'manage-own-products',
            'manage-own-orders',
            'view-own-wallet',
            'request-payout',

            // Customer permissions
            'place-orders',
            'write-reviews',
            'manage-own-profile',
            'manage-own-addresses',
            'manage-cart-wishlist',
        ];

        foreach ($guards as $guard) {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, $guard);
            }

            // 1. Super Admin Role
            $superAdminRole = Role::findOrCreate('super_admin', $guard);
            $superAdminRole->givePermissionTo(Permission::where('guard_name', $guard)->get());

            // 2. Admin Role
            $adminRole = Role::findOrCreate(UserRole::ADMIN->value, $guard);
            $adminRole->givePermissionTo(Permission::where('guard_name', $guard)->get());

            // 3. Seller Role
            $sellerRole = Role::findOrCreate(UserRole::SELLER->value, $guard);
            $sellerRole->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                'manage-own-store',
                'manage-own-products',
                'manage-own-orders',
                'view-own-wallet',
                'request-payout',
                'manage-own-profile',
                'manage-own-addresses',
            ])->get());

            // 4. Customer Role
            $customerRole = Role::findOrCreate(UserRole::CUSTOMER->value, $guard);
            $customerRole->givePermissionTo(Permission::where('guard_name', $guard)->whereIn('name', [
                'place-orders',
                'write-reviews',
                'manage-own-profile',
                'manage-own-addresses',
                'manage-cart-wishlist',
            ])->get());
        }
    }
}
