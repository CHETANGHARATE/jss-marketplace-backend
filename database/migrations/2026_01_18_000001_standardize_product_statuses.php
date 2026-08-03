<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to standardize all product moderation statuses.
     */
    public function up(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $driver = DB::getDriverName();

        // 1. Alter status column definition FIRST to allow both new and old statuses during migration
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft', 'pending_review', 'pending_approval', 'approved', 'rejected', 'hidden', 'archived', 'out_of_stock') NOT NULL DEFAULT 'draft'");
        }

        // 2. Perform data migration
        DB::table('products')
            ->whereIn('status', ['pending_approval', 'pendingReview', 'pending-review'])
            ->update(['status' => 'pending_review']);

        // 3. Finalize ENUM definition to strictly required statuses
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft', 'pending_review', 'approved', 'rejected', 'hidden', 'archived', 'out_of_stock') NOT NULL DEFAULT 'draft'");
        }
    }

    /**
     * Reverse the migrations safely.
     */
    public function down(): void
    {
        if (!Schema::hasTable('products')) {
            return;
        }

        $driver = DB::getDriverName();

        // 1. Temporarily allow pending_approval and pending_review
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft', 'pending_review', 'pending_approval', 'approved', 'rejected', 'hidden', 'archived', 'out_of_stock') NOT NULL DEFAULT 'draft'");
        }

        // 2. Revert data
        DB::table('products')
            ->where('status', 'pending_review')
            ->update(['status' => 'pending_approval']);

        // 3. Revert ENUM definition
        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE products MODIFY COLUMN status ENUM('draft', 'pending_approval', 'approved', 'rejected', 'archived') NOT NULL DEFAULT 'approved'");
        }
    }
};
