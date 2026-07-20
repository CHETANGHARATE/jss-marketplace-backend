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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_id')->constrained('inventories')->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->enum('type', ['inbound', 'outbound', 'reserved', 'released', 'adjustment', 'transfer']);
            $table->integer('quantity'); // Positive for addition, negative for deduction
            $table->integer('before_quantity');
            $table->integer('after_quantity');

            $table->string('reference_type')->nullable(); // e.g., 'PO-1001', 'TRANSFER-WH01-WH02'
            $table->string('reference_id')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['inventory_id', 'type', 'created_at']);
            $table->index(['warehouse_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
