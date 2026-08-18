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
        // 1. Create loyalty_transactions table if not exists
        if (!Schema::hasTable('loyalty_transactions')) {
            Schema::create('loyalty_transactions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->integer('points');
                $table->enum('type', ['earned', 'redeemed', 'refunded', 'adjusted'])->default('earned');
                $table->decimal('inr_value', 10, 2)->default(0.00);
                $table->string('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
            });
        }

        // 2. Add loyalty columns to orders table
        Schema::table('orders', function (Blueprint $table) {
            if (!Schema::hasColumn('orders', 'loyalty_points_redeemed')) {
                $table->integer('loyalty_points_redeemed')->default(0)->after('discount_amount');
            }
            if (!Schema::hasColumn('orders', 'loyalty_discount_amount')) {
                $table->decimal('loyalty_discount_amount', 10, 2)->default(0.00)->after('loyalty_points_redeemed');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasColumn('orders', 'loyalty_discount_amount')) {
                $table->dropColumn('loyalty_discount_amount');
            }
            if (Schema::hasColumn('orders', 'loyalty_points_redeemed')) {
                $table->dropColumn('loyalty_points_redeemed');
            }
        });

        Schema::dropIfExists('loyalty_transactions');
    }
};
