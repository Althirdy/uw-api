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
        Schema::table('locations', function (Blueprint $table) {
            // Drop foreign key constraint first
            $table->dropForeign(['location_category']);
            // Then drop the column
            $table->dropColumn('location_category');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->foreignId('location_category')
                ->after('barangay')
                ->constrained('location_categories')
                ->onDelete('cascade');
        });
    }
};
