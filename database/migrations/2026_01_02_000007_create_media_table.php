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
        Schema::create('media', function (Blueprint $table) {
            $table->id();
            $table->nullableMorphs('model'); // Polymorphic model association (e.g. Category, Brand, Product)
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_type')->nullable(); // e.g. image/jpeg, image/png
            $table->unsignedBigInteger('file_size')->default(0); // In bytes
            $table->string('disk')->default('public');
            $table->string('collection')->default('default'); // e.g. 'logo', 'banner', 'gallery'
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['collection', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media');
    }
};
