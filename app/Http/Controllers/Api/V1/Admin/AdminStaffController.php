<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminStaffController extends Controller
{
    /**
     * List all staff roles with real database assigned user counts.
     */
    public function indexRoles(): JsonResponse
    {
        $predefinedRoles = [
            [
                'id' => 1,
                'name' => 'Super Admin',
                'slug' => 'super_admin',
                'permissions' => 'Full Access across all modules, settings & system configuration',
                'module_access' => ['All Modules'],
            ],
            [
                'id' => 2,
                'name' => 'Catalog Manager',
                'slug' => 'catalog_manager',
                'permissions' => 'Products, Categories, Brands, Attributes & Inventory (Read/Write)',
                'module_access' => ['Products', 'Categories', 'Brands', 'Attributes', 'Inventory'],
            ],
            [
                'id' => 3,
                'name' => 'Order & Logistics Manager',
                'slug' => 'order_manager',
                'permissions' => 'Orders, Shipments, Couriers & Delivery Tracking (Read/Write)',
                'module_access' => ['Orders', 'Shipments', 'Delivery'],
            ],
            [
                'id' => 4,
                'name' => 'Finance & Settlement Officer',
                'slug' => 'finance_officer',
                'permissions' => 'Payments, Refunds, Vendor Payouts & Tax Reports',
                'module_access' => ['Payments', 'Vendor Settlements', 'Financial Reports'],
            ],
            [
                'id' => 5,
                'name' => 'Customer Support Executive',
                'slug' => 'support_executive',
                'permissions' => 'Customer Accounts, Reviews, Questions & Support Tickets',
                'module_access' => ['Customers', 'Reviews', 'Tickets'],
            ],
        ];

        // Query real staff accounts from DB
        $totalAdmins = User::where('role', 'admin')->count();

        foreach ($predefinedRoles as &$role) {
            if ($role['slug'] === 'super_admin') {
                $role['users_count'] = $totalAdmins;
            } else {
                try {
                    $roleModel = Role::where('name', $role['slug'])->orWhere('name', $role['name'])->first();
                    $role['users_count'] = $roleModel ? $roleModel->users()->count() : 0;
                } catch (\Exception $e) {
                    $role['users_count'] = 0;
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $predefinedRoles,
            'summary' => [
                'total_staff' => $totalAdmins,
            ]
        ]);
    }

    /**
     * List all staff accounts.
     */
    public function indexStaff(): JsonResponse
    {
        $staffUsers = User::where('role', 'admin')
            ->orWhereHas('roles', fn($q) => $q->whereNotIn('name', ['customer', 'seller']))
            ->latest('id')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $staffUsers,
            'meta' => [
                'total' => $staffUsers->count(),
            ]
        ]);
    }

    /**
     * Create new staff account.
     */
    public function storeStaff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role_title' => 'nullable|string|max:100',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => Hash::make($validated['password']),
            'role' => 'admin',
            'is_active' => true,
            'email_verified_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => "Staff account for '{$user->name}' created successfully.",
            'data' => $user,
        ], 201);
    }

    /**
     * Update staff account status / role.
     */
    public function updateStaff(Request $request, int $id): JsonResponse
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'is_active' => 'sometimes|boolean',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => "Staff account for '{$user->name}' updated successfully.",
            'data' => $user,
        ]);
    }

    /**
     * Delete staff account.
     */
    public function destroyStaff(int $id): JsonResponse
    {
        $user = User::findOrFail($id);
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own logged-in admin account.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Staff account deleted successfully.',
        ]);
    }
}
