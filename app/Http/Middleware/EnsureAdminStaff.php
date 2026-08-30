<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminStaff
{
    /**
     * Handle an incoming request.
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

        // Allow user if marked as admin enum or holds any staff/super_admin role
        $isStaff = $user->role === UserRole::ADMIN
            || $user->role === 'admin'
            || $user->hasRoleSafely('super_admin')
            || $user->hasRoleSafely('admin')
            || $user->hasAnyRole([
                'super_admin',
                'admin',
                'catalog_manager',
                'order_manager',
                'finance_officer',
                'support_executive',
                'marketing_manager',
                'content_manager',
                'b2b_manager',
            ]);

        if (!$isStaff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. You do not have permission to access the administrator portal.',
            ], 403);
        }

        return $next($request);
    }
}
