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
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number')->unique(); // e.g. 'SET-20260721-9812'
            $table->foreignId('vendor_store_id')->constrained('vendor_stores')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->json('bank_details');
            $table->enum('status', ['requested', 'processing', 'paid', 'rejected'])->default('requested');
            $table->string('reference_number')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->index(['vendor_store_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('settlements');
    }
};
