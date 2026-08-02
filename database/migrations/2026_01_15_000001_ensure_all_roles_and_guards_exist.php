<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset Spatie permission cache
        try {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
        } catch (\Throwable $e) {
            // Ignore if Spatie container binding fails in standalone script
        }

        $roles = ['super_admin', 'admin', 'seller', 'customer'];
        $guards = ['sanctum', 'web', 'api'];

        foreach ($guards as $guard) {
            foreach ($roles as $roleName) {
                try {
                    Role::firstOrCreate([
                        'name' => $roleName,
                        'guard_name' => $guard,
                    ]);
                } catch (\Throwable $e) {
                    // Ignore race conditions or duplicate entries
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Non-destructive down migration
    }
};
