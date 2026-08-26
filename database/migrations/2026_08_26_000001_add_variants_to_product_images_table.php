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
        Schema::table('product_images', function (Blueprint $table) {
            $table->json('variants')->nullable()->after('image_url');
            $table->unsignedInteger('width')->nullable()->after('variants');
            $table->unsignedInteger('height')->nullable()->after('width');
            $table->unsignedBigInteger('file_size')->nullable()->after('height');
            $table->string('format', 20)->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_images', function (Blueprint $table) {
            $table->dropColumn(['variants', 'width', 'height', 'file_size', 'format']);
        });
    }
};
