<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApprovedVendor
{
    /**
     * Handle an incoming request.
     * Enforces that the authenticated user has an approved, active vendor store.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated.',
            ], 401);
        }

        // Platform Administrators and Super Admins can access
        if (
            $user->role === UserRole::ADMIN
            || $user->role === 'admin'
            || $user->hasRoleSafely('super_admin')
            || $user->hasRoleSafely('admin')
        ) {
            return $next($request);
        }

        $store = $user->vendorStore;

        if (!$store) {
            return response()->json([
                'success' => false,
                'message' => 'No vendor store found for this account. Please submit a seller application first.',
                'vendor_status' => 'not_registered',
            ], 403);
        }

        if ($store->status === 'suspended') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor store account has been suspended. Please contact platform administration.',
                'vendor_status' => 'suspended',
            ], 403);
        }

        if ($store->status === 'rejected' || $store->kyc_status === 'rejected') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor store application has been rejected.',
                'vendor_status' => 'rejected',
            ], 403);
        }

        if ($store->status === 'pending' || $store->kyc_status === 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor application is currently pending approval. Access to vendor features will be enabled once approved by administration.',
                'vendor_status' => 'pending',
            ], 403);
        }

        if ($store->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your vendor store is not currently active.',
                'vendor_status' => $store->status,
            ], 403);
        }

        return $next($request);
    }
}
