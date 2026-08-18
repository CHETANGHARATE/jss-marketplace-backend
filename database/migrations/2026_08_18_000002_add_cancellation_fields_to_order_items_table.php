<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('order_items', 'cancellation_reason')) {
                $table->string('cancellation_reason')->nullable()->after('status');
            }
            if (!Schema::hasColumn('order_items', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_reason');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            if (Schema::hasColumn('order_items', 'cancelled_at')) {
                $table->dropColumn('cancelled_at');
            }
            if (Schema::hasColumn('order_items', 'cancellation_reason')) {
                $table->dropColumn('cancellation_reason');
            }
        });
    }
};
