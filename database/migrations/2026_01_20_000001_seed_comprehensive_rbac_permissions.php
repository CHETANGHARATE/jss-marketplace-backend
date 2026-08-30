<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Reset cached roles and permissions
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {}

        $guards = ['sanctum', 'web', 'api'];

        // 2. Comprehensive Action-Level Permission Definitions
        $allPermissions = [
            // Products
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.approve',
            'products.export',

            // Categories
            'categories.view',
            'categories.create',
            'categories.edit',
            'categories.delete',

            // Brands
            'brands.view',
            'brands.create',
            'brands.edit',
            'brands.delete',

            // Attributes
            'attributes.view',
            'attributes.create',
            'attributes.edit',
            'attributes.delete',

            // Inventory
            'inventory.view',
            'inventory.edit',

            // Orders
            'orders.view',
            'orders.edit',
            'orders.delete',
            'orders.export',

            // Customers
            'customers.view',
            'customers.edit',
            'customers.delete',

            // Vendors
            'vendors.view',
            'vendors.approve',
            'vendors.suspend',
            'vendors.edit',

            // Payments & Settlements
            'payments.view',
            'payments.settle',
            'payments.refund',

            // Shipping
            'shipping.view',
            'shipping.edit',

            // Promotions & Coupons
            'promotions.view',
            'promotions.create',
            'promotions.edit',
            'promotions.delete',

            // CMS & Content
            'cms.view',
            'cms.create',
            'cms.edit',
            'cms.delete',

            // Reviews & Ratings
            'reviews.view',
            'reviews.edit',
            'reviews.delete',

            // Reports & Analytics
            'reports.view',
            'reports.export',

            // Tax & Invoicing
            'tax.view',
            'tax.edit',

            // Suppliers & Purchase
            'suppliers.view',
            'suppliers.edit',

            // Staff & Roles
            'staff.view',
            'staff.create',
            'staff.edit',
            'staff.delete',

            // Notifications
            'notifications.view',
            'notifications.edit',

            // Support Tickets
            'support.view',
            'support.edit',

            // Settings
            'settings.view',
            'settings.edit',

            // Security & Backup
            'security.view',

            // B2B Marketplace
            'b2b.view',
            'b2b.edit',
            'b2b.approve',
        ];

        // 3. Create all permissions across all guards
        foreach ($guards as $guard) {
            foreach ($allPermissions as $permName) {
                try {
                    Permission::firstOrCreate([
                        'name' => $permName,
                        'guard_name' => $guard,
                    ]);
                } catch (\Throwable $e) {}
            }
        }

        // 4. Define Role Permissions Matrix
        $rolePermissions = [
            'super_admin' => $allPermissions, // Full Access
            'admin' => $allPermissions,

            // Catalog Manager: Products, Categories, Brands, Attributes (Read/Write, NO delete by default)
            'catalog_manager' => [
                'products.view',
                'products.create',
                'products.edit',
                'products.approve',
                'products.export',
                'categories.view',
                'categories.create',
                'categories.edit',
                'brands.view',
                'brands.create',
                'brands.edit',
                'attributes.view',
                'attributes.create',
                'attributes.edit',
            ],

            // Order & Logistics Manager: Orders, Shipments, Deliveries
            'order_manager' => [
                'orders.view',
                'orders.edit',
                'orders.export',
                'shipping.view',
                'shipping.edit',
            ],

            // Finance & Settlement Officer: Payments, Settlements, Refunds, Tax, Reports
            'finance_officer' => [
                'payments.view',
                'payments.settle',
                'payments.refund',
                'tax.view',
                'tax.edit',
                'reports.view',
                'reports.export',
            ],

            // Customer Support Executive: Customers, Orders (view), Reviews, Support
            'support_executive' => [
                'customers.view',
                'customers.edit',
                'orders.view',
                'reviews.view',
                'reviews.edit',
                'support.view',
                'support.edit',
            ],

            // Marketing Manager: Promotions, Coupons, Flash Sales
            'marketing_manager' => [
                'promotions.view',
                'promotions.create',
                'promotions.edit',
                'promotions.delete',
                'cms.view',
            ],

            // Content Manager: CMS, Banners, FAQs, Popups, Pages
            'content_manager' => [
                'cms.view',
                'cms.create',
                'cms.edit',
                'cms.delete',
            ],

            // B2B Manager: Buyers, RFQs, Quotes, Purchase Orders
            'b2b_manager' => [
                'b2b.view',
                'b2b.edit',
                'b2b.approve',
            ],
        ];

        // 5. Assign Permissions to Roles across all guards
        foreach ($guards as $guard) {
            foreach ($rolePermissions as $roleName => $perms) {
                try {
                    $role = Role::firstOrCreate([
                        'name' => $roleName,
                        'guard_name' => $guard,
                    ]);

                    $guardPerms = Permission::where('guard_name', $guard)
                        ->whereIn('name', $perms)
                        ->get();

                    $role->syncPermissions($guardPerms);
                } catch (\Throwable $e) {}
            }
        }

        // 6. Safe Normalization for Existing Users:
        // Ensure Super Admin accounts are explicitly granted 'super_admin' across guards
        try {
            $superAdmins = User::where('role', 'admin')
                ->where(function ($q) {
                    $q->where('id', 1)
                      ->orWhere('email', 'like', '%admin%')
                      ->orWhere('email', 'chetangharateofficial@gmail.com');
                })
                ->get();

            foreach ($superAdmins as $superAdmin) {
                $superAdmin->assignRoleSafely('super_admin');
            }

            // For other staff accounts, sync their permissions to their assigned role
            $staffUsers = User::where('role', 'admin')
                ->whereNotIn('id', $superAdmins->pluck('id'))
                ->get();

            foreach ($staffUsers as $staff) {
                $assignedRoles = $staff->roles()->pluck('name')->toArray();
                $staffRole = 'catalog_manager';
                foreach (['catalog_manager', 'order_manager', 'finance_officer', 'support_executive', 'marketing_manager', 'content_manager', 'b2b_manager'] as $checkRole) {
                    if (in_array($checkRole, $assignedRoles)) {
                        $staffRole = $checkRole;
                        break;
                    }
                }
                $staff->assignRoleSafely($staffRole);
            }
        } catch (\Throwable $e) {}

        // 7. Reset permission cache once more
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {}
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive down migration
    }
};
