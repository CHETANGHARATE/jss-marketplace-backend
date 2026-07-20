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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_number')->unique(); // e.g. 'PAY-20260720-9812'
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->enum('gateway', ['razorpay', 'stripe', 'cod'])->default('razorpay');
            $table->string('transaction_id')->nullable()->index(); // Gateway Payment ID / Order ID
            $table->decimal('amount', 12, 2);
            $table->string('currency', 10)->default('INR');

            $table->enum('status', ['pending', 'authorized', 'captured', 'failed', 'refunded'])->default('pending');

            $table->json('payment_method_details')->nullable(); // Card brand, UPI ID, etc.
            $table->string('error_code')->nullable();
            $table->text('error_description')->nullable();

            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['order_id', 'status']);
            $table->index(['gateway', 'transaction_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
