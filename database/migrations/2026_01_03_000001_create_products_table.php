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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('category_id')->constrained('categories')->restrictOnDelete();
            $table->foreignId('subcategory_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->foreignId('brand_id')->nullable()->constrained('brands')->nullOnDelete();

            $table->string('sku')->unique();
            $table->json('name'); // Multilingual JSON {"en": "...", "hi": "...", "mr": "..."}
            $table->string('slug')->unique();
            $table->json('short_description')->nullable();
            $table->json('description')->nullable();
            $table->string('thumbnail')->nullable();

            // Pricing Engine
            $table->decimal('original_price', 12, 2);
            $table->decimal('offer_price', 12, 2);
            $table->integer('discount_percent')->default(0);
            $table->decimal('cost_price', 12, 2)->nullable();

            // Stock & Metrics
            $table->enum('stock_status', ['in_stock', 'out_of_stock', 'pre_order'])->default('in_stock');
            $table->integer('stock_quantity')->default(0);
            $table->decimal('rating', 3, 2)->default(0.00);
            $table->integer('reviews_count')->default(0);

            // Flags & Status Workflow
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_trending')->default(false);
            $table->boolean('is_active')->default(true);
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected', 'archived'])->default('approved');
            $table->text('rejection_reason')->nullable();

            // SEO Metadata
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keywords')->nullable();

            $table->softDeletes();
            $table->timestamps();

            // Indexes for high-performance filtering & search
            $table->index(['category_id', 'subcategory_id', 'brand_id']);
            $table->index(['status', 'is_active', 'is_featured', 'is_trending']);
            $table->index(['offer_price', 'rating', 'discount_percent']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
