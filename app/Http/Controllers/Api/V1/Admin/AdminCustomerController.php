<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCustomerController extends Controller
{
    /**
     * List all customers with optional search and status filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::role('customer');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->input('status');
            if ($status === 'active') {
                $query->whereNull('blocked_at');
            } elseif ($status === 'blocked') {
                $query->whereNotNull('blocked_at');
            }
        }

        $customers = $query->latest()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => UserResource::collection($customers),
            'meta' => [
                'current_page' => $customers->currentPage(),
                'last_page'    => $customers->lastPage(),
                'total'        => $customers->total(),
                'per_page'     => $customers->perPage(),
            ],
        ], 200);
    }

    /**
     * Toggle customer block/unblock status.
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $customer = User::findOrFail($id);

        if ($customer->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot modify admin accounts.',
            ], 403);
        }

        if ($customer->blocked_at) {
            $customer->update(['blocked_at' => null]);
            $message = 'Customer account has been activated.';
        } else {
            $customer->update(['blocked_at' => now()]);
            $message = 'Customer account has been blocked.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => new UserResource($customer),
        ], 200);
    }
}
