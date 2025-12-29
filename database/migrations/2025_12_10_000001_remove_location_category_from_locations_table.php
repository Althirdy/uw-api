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
            if (Schema::hasColumn('locations', 'location_category')) {
                // Drop foreign key constraint first if it exists
                try {
                    $table->dropForeign(['location_category']);
                } catch (\Exception $e) {
                    // Foreign key doesn't exist, continue
                }
                // Then drop the column
                $table->dropColumn('location_category');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            if (!Schema::hasColumn('locations', 'location_category')) {
                // Add the column back without foreign key constraint
                // The constraint will be added by the original migration
                $table->unsignedBigInteger('location_category')->nullable()->after('barangay');
            }
        });
    }
};
