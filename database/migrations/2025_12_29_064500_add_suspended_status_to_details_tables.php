<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'suspended' to citizen_details status enum
        DB::statement("ALTER TABLE `citizen_details` MODIFY `status` ENUM('active', 'inactive', 'archived', 'suspended') DEFAULT 'active'");

        // Add 'suspended' to officials_details status enum
        DB::statement("ALTER TABLE `officials_details` MODIFY `status` ENUM('active', 'inactive', 'archived', 'suspended') DEFAULT 'active'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // First, update any 'suspended' status to 'active' before removing it from enum
        DB::table('citizen_details')->where('status', 'suspended')->update(['status' => 'active']);
        DB::table('officials_details')->where('status', 'suspended')->update(['status' => 'active']);

        // Remove 'suspended' from citizen_details status enum
        DB::statement("ALTER TABLE `citizen_details` MODIFY `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active'");

        // Remove 'suspended' from officials_details status enum
        DB::statement("ALTER TABLE `officials_details` MODIFY `status` ENUM('active', 'inactive', 'archived') DEFAULT 'active'");
    }
};
