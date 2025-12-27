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
        Schema::table('accidents', function (Blueprint $table) {
            $table->unsignedBigInteger('cctv_device_id')->nullable()->after('id');
            $table->foreign('cctv_device_id')
                  ->references('id')
                  ->on('cctv_devices')
                  ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accidents', function (Blueprint $table) {
            $table->dropForeign(['cctv_device_id']);
            $table->dropColumn('cctv_device_id');
        });
    }
};
