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
        Schema::table('concerns', function (Blueprint $table) {
            // Add tracking code after ID. Unique index ensures no duplicates.
            $table->string('tracking_code')->unique()->after('id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concerns', function (Blueprint $table) {
            $table->dropColumn('tracking_code');
        });
    }
};