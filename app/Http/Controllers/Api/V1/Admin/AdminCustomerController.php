<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminCustomerController extends Controller
{
    /**
     * List all marketplace customers with metrics, search, and status filters.
     */
    public function index(Request $request): JsonResponse
    {
        // 1. Query all users that are customers (excluding admin & vendor roles)
        $query = User::query()
            ->where(function ($q) {
                $q->where('role', 'customer')
                  ->orWhereHas('roles', fn($r) => $r->where('name', 'customer'))
                  ->orWhere(function ($fallback) {
                      $fallback->whereNull('role')
                               ->orWhereNotIn('role', ['admin', 'seller']);
                  });
            })
            ->where(function ($q) {
                $q->where('role', '!=', 'admin')
                  ->where('role', '!=', 'seller')
                  ->whereDoesntHave('roles', fn($r) => $r->whereIn('name', ['admin', 'seller']));
            });

        // 2. Search by Name, Email, or Phone
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // 3. Status Filter (Active / Blocked)
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->whereNull('blocked_at')
                      ->where(function ($sq) {
                          $sq->whereNull('status')->orWhere('status', '!=', 'inactive');
                      });
            } elseif ($status === 'blocked' || $status === 'inactive') {
                $query->where(function ($sq) {
                    $sq->whereNotNull('blocked_at')->orWhere('status', 'inactive');
                });
            }
        }

        // 4. Order activity filter
        if ($request->filled('filter')) {
            $filter = $request->input('filter');
            if ($filter === 'with_orders') {
                $query->has('orders');
            } elseif ($filter === 'no_orders') {
                $query->doesntHave('orders');
            }
        }

        // 5. Pagination
        $perPage = min((int) $request->input('per_page', 20), 100);
        $customers = $query->latest('id')->paginate($perPage);

        // 6. Aggregate real order metrics for each customer in current page
        $userIds = $customers->pluck('id')->toArray();
        $orderStats = Order::whereIn('user_id', $userIds)
            ->select(
                'user_id',
                DB::raw('COUNT(*) as total_orders'),
                DB::raw("COUNT(CASE WHEN status IN ('delivered', 'completed') THEN 1 END) as completed_orders"),
                DB::raw("COUNT(CASE WHEN status IN ('cancelled') THEN 1 END) as cancelled_orders"),
                DB::raw("COUNT(CASE WHEN status IN ('returned', 'refunded') THEN 1 END) as returned_orders"),
                DB::raw("SUM(CASE WHEN status != 'cancelled' THEN total_amount ELSE 0 END) as lifetime_spent"),
                DB::raw('MAX(created_at) as last_order_at')
            )
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $mappedData = $customers->getCollection()->map(function ($cust) use ($orderStats) {
            $stats = $orderStats->get($cust->id);
            $totalOrders = (int) ($stats->total_orders ?? 0);
            $completedOrders = (int) ($stats->completed_orders ?? 0);
            $cancelledOrders = (int) ($stats->cancelled_orders ?? 0);
            $returnedOrders = (int) ($stats->returned_orders ?? 0);
            $lifetimeSpent = (float) ($stats->lifetime_spent ?? 0);
            $avgOrderValue = $completedOrders > 0 ? round($lifetimeSpent / $completedOrders, 2) : 0;
            $isBlocked = $cust->blocked_at !== null || (is_object($cust->status) ? $cust->status->value : $cust->status) === 'inactive';

            return [
                'id' => $cust->id,
                'name' => $cust->name,
                'email' => $cust->email,
                'phone' => $cust->phone,
                'role' => is_object($cust->role) ? $cust->role->value : ($cust->role ?? 'customer'),
                'role_label' => 'Customer',
                'status' => $isBlocked ? 'blocked' : 'active',
                'is_blocked' => $isBlocked,
                'email_verified' => $cust->email_verified_at !== null,
                'phone_verified' => $cust->phone_verified_at !== null,
                'total_orders' => $totalOrders,
                'completed_orders' => $completedOrders,
                'cancelled_orders' => $cancelledOrders,
                'returned_orders' => $returnedOrders,
                'lifetime_spent' => $lifetimeSpent,
                'avg_order_value' => $avgOrderValue,
                'last_order_at' => $stats->last_order_at ?? null,
                'created_at' => $cust->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $mappedData,
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'total'        => $customers->total(),
                'per_page'     => $customers->perPage(),
            ],
        ], 200);
    }

    /**
     * Get full Customer Detail Profile with orders, addresses, and behavior metrics.
     */
    public function show(int $id): JsonResponse
    {
        $customer = User::with(['addresses'])->findOrFail($id);

        if ($customer->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be viewed as customers.',
            ], 403);
        }

        // Fetch recent orders with order items
        $orders = Order::where('user_id', $customer->id)
            ->latest()
            ->take(15)
            ->get();

        $totalOrders = Order::where('user_id', $customer->id)->count();
        $completedOrders = Order::where('user_id', $customer->id)->whereIn('status', ['delivered', 'completed'])->count();
        $cancelledOrders = Order::where('user_id', $customer->id)->where('status', 'cancelled')->count();
        $returnedOrders = Order::where('user_id', $customer->id)->whereIn('status', ['returned', 'refunded'])->count();
        $lifetimeSpent = (float) Order::where('user_id', $customer->id)->where('status', '!=', 'cancelled')->sum('total_amount');
        $avgOrderValue = $completedOrders > 0 ? round($lifetimeSpent / $completedOrders, 2) : 0;
        $isBlocked = $customer->blocked_at !== null || (is_object($customer->status) ? $customer->status->value : $customer->status) === 'inactive';

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'phone' => $customer->phone,
                    'role' => is_object($customer->role) ? $customer->role->value : ($customer->role ?? 'customer'),
                    'status' => $isBlocked ? 'blocked' : 'active',
                    'is_blocked' => $isBlocked,
                    'email_verified' => $customer->email_verified_at !== null,
                    'phone_verified' => $customer->phone_verified_at !== null,
                    'created_at' => $customer->created_at?->toIso8601String(),
                ],
                'analytics' => [
                    'total_orders' => $totalOrders,
                    'completed_orders' => $completedOrders,
                    'cancelled_orders' => $cancelledOrders,
                    'returned_orders' => $returnedOrders,
                    'lifetime_spent' => $lifetimeSpent,
                    'avg_order_value' => $avgOrderValue,
                    'last_order_at' => $orders->first()?->created_at?->toIso8601String() ?? null,
                ],
                'orders' => $orders->map(fn($o) => [
                    'id' => $o->id,
                    'order_number' => $o->order_number,
                    'status' => $o->status,
                    'payment_status' => $o->payment_status,
                    'payment_method' => $o->payment_method,
                    'total_amount' => (float) $o->total_amount,
                    'created_at' => $o->created_at?->toIso8601String(),
                ]),
                'addresses' => $customer->addresses->map(fn($a) => [
                    'id' => $a->id,
                    'name' => $a->name,
                    'phone' => $a->phone,
                    'address_line_1' => $a->address_line_1,
                    'address_line_2' => $a->address_line_2,
                    'city' => $a->city,
                    'state' => $a->state,
                    'postal_code' => $a->postal_code,
                    'is_default' => (bool) $a->is_default,
                ]),
            ],
        ], 200);
    }

    /**
     * Toggle customer block/unblock status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $customer = User::findOrFail($id);

        if ($customer->isAdmin()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify administrator accounts.',
            ], 403);
        }

        $isCurrentlyBlocked = $customer->blocked_at !== null || (is_object($customer->status) ? $customer->status->value : $customer->status) === 'inactive';

        if ($isCurrentlyBlocked) {
            $customer->update([
                'blocked_at' => null,
                'status' => \App\Enums\UserStatus::ACTIVE ?? 'active',
            ]);
            $message = "Customer account '{$customer->name}' has been unblocked and activated.";
        } else {
            $customer->update([
                'blocked_at' => now(),
                'status' => \App\Enums\UserStatus::INACTIVE ?? 'inactive',
            ]);
            $message = "Customer account '{$customer->name}' has been blocked from placing orders.";
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'id' => $customer->id,
                'status' => $isCurrentlyBlocked ? 'active' : 'blocked',
                'is_blocked' => !$isCurrentlyBlocked,
            ],
        ], 200);
    }
}
