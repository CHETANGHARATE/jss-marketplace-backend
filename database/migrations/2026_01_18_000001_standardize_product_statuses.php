<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations to standardize all product moderation statuses to pending_review.
     */
    public function up(): void
    {
        if (Schema::hasTable('products')) {
            DB::table('products')
                ->where('status', 'pending_approval')
                ->orWhere('status', 'pendingReview')
                ->orWhere('status', 'pending-review')
                ->update(['status' => 'pending_review']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No reversal needed
    }
};
