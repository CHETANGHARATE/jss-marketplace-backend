<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class AdminStaffController extends Controller
{
    /**
     * Standard role metadata mapping for system RBAC.
     */
    public const ROLE_MAP = [
        'super_admin' => [
            'id' => 1,
            'slug' => 'super_admin',
            'title' => 'Super Admin',
            'permissions' => 'Full Access across all modules, settings & system configuration',
            'module_access' => ['All Modules'],
        ],
        'catalog_manager' => [
            'id' => 2,
            'slug' => 'catalog_manager',
            'title' => 'Catalog Manager',
            'permissions' => 'Products, Categories, Brands, Attributes & Inventory (Read/Write)',
            'module_access' => ['Products', 'Categories', 'Brands', 'Attributes', 'Inventory'],
        ],
        'order_manager' => [
            'id' => 3,
            'slug' => 'order_manager',
            'title' => 'Order & Logistics Manager',
            'permissions' => 'Orders, Shipments, Couriers & Delivery Tracking (Read/Write)',
            'module_access' => ['Orders', 'Shipments', 'Delivery'],
        ],
        'finance_officer' => [
            'id' => 4,
            'slug' => 'finance_officer',
            'title' => 'Finance & Settlement Officer',
            'permissions' => 'Payments, Refunds, Vendor Payouts & Tax Reports',
            'module_access' => ['Payments', 'Vendor Settlements', 'Financial Reports'],
        ],
        'support_executive' => [
            'id' => 5,
            'slug' => 'support_executive',
            'title' => 'Customer Support Executive',
            'permissions' => 'Customer Accounts, Reviews, Questions & Support Tickets',
            'module_access' => ['Customers', 'Reviews', 'Tickets'],
        ],
    ];

    /**
     * Resolve any role name, title, or slug into its canonical role metadata.
     */
    public static function resolveRoleInfo(?string $input): array
    {
        if (empty($input)) {
            return self::ROLE_MAP['catalog_manager'];
        }

        $clean = strtolower(trim($input));

        if (str_contains($clean, 'super') || $clean === 'super_admin') {
            return self::ROLE_MAP['super_admin'];
        }
        if (str_contains($clean, 'catalog') || str_contains($clean, 'product') || $clean === 'catalog_manager') {
            return self::ROLE_MAP['catalog_manager'];
        }
        if (str_contains($clean, 'order') || str_contains($clean, 'logistic') || $clean === 'order_manager') {
            return self::ROLE_MAP['order_manager'];
        }
        if (str_contains($clean, 'finance') || str_contains($clean, 'account') || str_contains($clean, 'settle') || $clean === 'finance_officer') {
            return self::ROLE_MAP['finance_officer'];
        }
        if (str_contains($clean, 'support') || str_contains($clean, 'ticket') || str_contains($clean, 'customer') || $clean === 'support_executive') {
            return self::ROLE_MAP['support_executive'];
        }

        return self::ROLE_MAP['catalog_manager'];
    }

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
            ->with('roles')
            ->latest('id')
            ->get()
            ->map(function ($u) {
                $spatieRole = $u->roles->first(fn($r) => !in_array($r->name, ['admin', 'customer', 'seller']));
                $roleSlug = $spatieRole ? $spatieRole->name : 'admin';
                $roleInfo = self::ROLE_MAP[$roleSlug] ?? [
                    'slug' => $roleSlug,
                    'title' => ($roleSlug === 'admin' ? 'Super Admin' : ucfirst(str_replace('_', ' ', $roleSlug)))
                ];

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'phone' => $u->phone,
                    'role' => $u->role instanceof UserRole ? $u->role->value : (string)$u->role,
                    'role_slug' => $roleInfo['slug'],
                    'role_title' => $roleInfo['title'],
                    'permissions' => $u->hasRoleSafely('super_admin') ? ['*'] : $u->getAllPermissions()->pluck('name')->values(),
                    'module_access' => $roleInfo['module_access'] ?? ['All Modules'],
                    'status' => $u->status instanceof UserStatus ? $u->status->value : ($u->status ?? 'active'),
                    'is_active' => ($u->status instanceof UserStatus ? $u->status->value : $u->status) === 'active',
                    'created_at' => $u->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $staffUsers,
            'meta' => [
                'total' => $staffUsers->count(),
            ]
        ]);
    }

    /**
     * Create new staff account atomically.
     */
    public function storeStaff(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'phone' => 'nullable|string|max:20|unique:users,phone',
            'password' => 'required|string|min:8',
            'role_title' => 'nullable|string|max:100',
            'role' => 'nullable|string|max:100',
        ], [
            'name.required' => 'Please enter the staff member\'s full name.',
            'email.required' => 'Please enter a valid email address.',
            'email.email' => 'Please enter a valid email address.',
            'email.unique' => 'This email address is already registered to an existing account.',
            'phone.unique' => 'This mobile number is already registered to an existing account.',
            'password.required' => 'A temporary password is required.',
            'password.min' => 'Temporary password must be at least 8 characters long.',
        ]);

        $roleInput = $validated['role'] ?? $validated['role_title'] ?? 'catalog_manager';
        $roleInfo = self::resolveRoleInfo($roleInput);

        $user = DB::transaction(function () use ($validated, $roleInfo) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'] ?? null,
                'password' => Hash::make($validated['password']),
                'role' => UserRole::ADMIN,
                'status' => UserStatus::ACTIVE,
                'email_verified_at' => now(),
                'phone_verified_at' => !empty($validated['phone']) ? now() : null,
            ]);

            // Assign Spatie role safely across guards
            $user->assignRoleSafely($roleInfo['slug']);

            return $user;
        });

        return response()->json([
            'success' => true,
            'message' => "Staff account for '{$user->name}' created successfully with role '{$roleInfo['title']}'.",
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => 'admin',
                'role_slug' => $roleInfo['slug'],
                'role_title' => $roleInfo['title'],
                'status' => 'active',
                'is_active' => true,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
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
            'phone' => 'nullable|string|max:20|unique:users,phone,' . $user->id,
            'is_active' => 'sometimes|boolean',
            'status' => 'sometimes|string|in:active,inactive,banned',
            'role_title' => 'sometimes|string|max:100',
            'role' => 'sometimes|string|max:100',
        ], [
            'phone.unique' => 'This mobile number is already registered to an existing account.',
        ]);

        DB::transaction(function () use ($user, $validated) {
            $updateData = [];
            if (isset($validated['name'])) $updateData['name'] = $validated['name'];
            if (array_key_exists('phone', $validated)) $updateData['phone'] = $validated['phone'];
            if (isset($validated['status'])) {
                $updateData['status'] = $validated['status'];
            } elseif (isset($validated['is_active'])) {
                $updateData['status'] = $validated['is_active'] ? UserStatus::ACTIVE : UserStatus::INACTIVE;
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            $roleInput = $validated['role'] ?? $validated['role_title'] ?? null;
            if ($roleInput) {
                $roleInfo = self::resolveRoleInfo($roleInput);
                $user->assignRoleSafely($roleInfo['slug']);
            }
        });

        return response()->json([
            'success' => true,
            'message' => "Staff account for '{$user->name}' updated successfully.",
            'data' => $user->fresh(),
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
