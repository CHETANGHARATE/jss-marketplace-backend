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
        // 1. Saved Cart Items (Feature 15: Save for Later)
        if (!Schema::hasTable('saved_cart_items')) {
            Schema::create('saved_cart_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->integer('quantity')->default(1);
                $table->decimal('price_snapshot', 12, 2)->default(0.00);
                $table->timestamp('saved_at')->useCurrent();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
                $table->index(['user_id', 'created_at']);
            });
        }

        // 2. Store Followers (Feature 65: Follow Seller)
        if (!Schema::hasTable('store_followers')) {
            Schema::create('store_followers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('vendor_store_id')->constrained('vendor_stores')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'vendor_store_id']);
                $table->index('vendor_store_id');
            });
        }

        // 3. User Favorite Brands (Feature 66: Favorite Brands)
        if (!Schema::hasTable('user_favorite_brands')) {
            Schema::create('user_favorite_brands', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('brand_id')->constrained('brands')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'brand_id']);
                $table->index('user_id');
            });
        }

        // 4. User Favorite Categories (Feature 67: Favorite Categories)
        if (!Schema::hasTable('user_favorite_categories')) {
            Schema::create('user_favorite_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
                $table->timestamps();

                $table->unique(['user_id', 'category_id']);
                $table->index('user_id');
            });
        }

        // 5. Price Drop Alerts (Feature 40: Price Drop Alert)
        if (!Schema::hasTable('price_drop_alerts')) {
            Schema::create('price_drop_alerts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->decimal('target_price', 12, 2)->nullable();
                $table->decimal('initial_price', 12, 2)->default(0.00);
                $table->enum('status', ['active', 'triggered', 'cancelled'])->default('active');
                $table->timestamp('triggered_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
                $table->index(['product_id', 'status']);
            });
        }

        // 6. Back-in-Stock Subscriptions (Feature 41: Back-in-Stock Alert)
        if (!Schema::hasTable('back_in_stock_subscriptions')) {
            Schema::create('back_in_stock_subscriptions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
                $table->enum('status', ['active', 'notified', 'cancelled'])->default('active');
                $table->timestamp('subscribed_at')->useCurrent();
                $table->timestamp('notified_at')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'product_id']);
                $table->index(['product_id', 'status']);
            });
        }

        // 7. Order Returns & Reverse Logistics (Features 36 & 37)
        if (!Schema::hasTable('order_returns')) {
            Schema::create('order_returns', function (Blueprint $table) {
                $table->id();
                $table->string('return_number')->unique();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('reason');
                $table->text('notes')->nullable();
                $table->json('evidence_urls')->nullable();
                $table->json('pickup_address_snapshot')->nullable();
                $table->enum('status', [
                    'requested',
                    'approved',
                    'pickup_scheduled',
                    'picked_up',
                    'received',
                    'inspected',
                    'approved_for_refund',
                    'refund_processing',
                    'refunded',
                    'rejected'
                ])->default('requested');
                $table->decimal('refund_amount', 12, 2)->default(0.00);
                $table->string('courier_name')->nullable();
                $table->string('tracking_number')->nullable();
                $table->text('rejection_reason')->nullable();
                $table->timestamp('processed_at')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('order_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_returns');
        Schema::dropIfExists('back_in_stock_subscriptions');
        Schema::dropIfExists('price_drop_alerts');
        Schema::dropIfExists('user_favorite_categories');
        Schema::dropIfExists('user_favorite_brands');
        Schema::dropIfExists('store_followers');
        Schema::dropIfExists('saved_cart_items');
    }
};
