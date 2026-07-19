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

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        // Create Roles and Assign Permissions
        $adminRole = Role::findOrCreate(UserRole::ADMIN->value, 'web');
        $adminRole->givePermissionTo(Permission::all());

        $sellerRole = Role::findOrCreate(UserRole::SELLER->value, 'web');
        $sellerRole->givePermissionTo([
            'manage-own-store',
            'manage-own-products',
            'manage-own-orders',
            'view-own-wallet',
            'request-payout',
            'manage-own-profile',
            'manage-own-addresses',
        ]);

        $customerRole = Role::findOrCreate(UserRole::CUSTOMER->value, 'web');
        $customerRole->givePermissionTo([
            'place-orders',
            'write-reviews',
            'manage-own-profile',
            'manage-own-addresses',
            'manage-cart-wishlist',
        ]);
    }
}
