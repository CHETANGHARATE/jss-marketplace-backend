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
        // 1. Attribute Templates
        Schema::create('attribute_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('cascade');
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });

        // 2. Attribute Template Items (Pivot linking Templates to Attributes)
        Schema::create('attribute_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attribute_template_id')->constrained('attribute_templates')->onDelete('cascade');
            $table->foreignId('attribute_id')->constrained('attributes')->onDelete('cascade');
            $table->boolean('is_required')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 3. Product Variants
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable();
            $table->string('title');
            $table->decimal('price', 10, 2)->default(0);
            $table->decimal('offer_price', 10, 2)->nullable();
            $table->integer('stock_quantity')->default(0);
            $table->string('image')->nullable();
            $table->json('attributes')->nullable();
            $table->boolean('is_default')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();
        });

        // 4. Modify products table to support full Modules 1-13
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'child_category_id')) {
                $table->foreignId('child_category_id')->nullable()->after('subcategory_id')->constrained('categories')->onDelete('set null');
            }
            if (!Schema::hasColumn('products', 'gst_percent')) {
                $table->decimal('gst_percent', 5, 2)->default(0)->after('cost_price');
                $table->boolean('tax_inclusive')->default(true)->after('gst_percent');
            }
            if (!Schema::hasColumn('products', 'weight')) {
                $table->decimal('weight', 8, 2)->nullable()->after('stock_quantity');
                $table->decimal('length', 8, 2)->nullable()->after('weight');
                $table->decimal('width', 8, 2)->nullable()->after('length');
                $table->decimal('height', 8, 2)->nullable()->after('width');
                $table->integer('dispatch_days')->default(1)->after('height');
                $table->decimal('shipping_charge', 8, 2)->default(0)->after('dispatch_days');
                $table->boolean('is_free_shipping')->default(false)->after('shipping_charge');
                $table->boolean('is_cod_available')->default(true)->after('is_free_shipping');
            }
            if (!Schema::hasColumn('products', 'return_policy')) {
                $table->text('return_policy')->nullable();
                $table->text('replacement_policy')->nullable();
                $table->text('warranty_summary')->nullable();
                $table->text('guarantee_summary')->nullable();
                $table->text('cancellation_policy')->nullable();
            }
            if (!Schema::hasColumn('products', 'canonical_url')) {
                $table->string('canonical_url')->nullable();
                $table->string('og_image')->nullable();
            }
            if (!Schema::hasColumn('products', 'ai_description')) {
                $table->text('ai_description')->nullable();
                $table->json('ai_seo')->nullable();
                $table->json('ai_highlights')->nullable();
                $table->json('ai_keywords')->nullable();
            }
            if (!Schema::hasColumn('products', 'highlights')) {
                $table->json('highlights')->nullable();
                $table->text('search_keywords')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
        Schema::dropIfExists('attribute_template_items');
        Schema::dropIfExists('attribute_templates');
    }
};
