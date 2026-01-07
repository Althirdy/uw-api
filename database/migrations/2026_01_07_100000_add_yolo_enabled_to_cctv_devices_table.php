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
        Schema::table('cctv_devices', function (Blueprint $table) {
            $table->boolean('yolo_enabled')->default(false)->after('fps');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cctv_devices', function (Blueprint $table) {
            $table->dropColumn('yolo_enabled');
        });
    }
};
