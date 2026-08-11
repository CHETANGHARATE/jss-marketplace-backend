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
        Schema::table('otps', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
            $table->string('phone')->nullable()->after('email')->index();
            $table->string('purpose')->nullable()->after('type')->index();
            $table->string('req_id')->nullable()->after('purpose')->index();
            $table->integer('attempts')->default(0)->after('used');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->dropColumn(['phone', 'purpose', 'req_id', 'attempts']);
        });
    }
};
