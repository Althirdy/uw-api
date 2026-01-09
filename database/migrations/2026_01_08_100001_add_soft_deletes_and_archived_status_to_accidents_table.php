<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // First, add 'Archived' to the status enum
        DB::statement("ALTER TABLE accidents MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Resolved', 'Archived') DEFAULT 'Pending'");

        // Add soft deletes column
        Schema::table('accidents', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('accidents', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        // Revert the enum change
        DB::statement("ALTER TABLE accidents MODIFY COLUMN status ENUM('Pending', 'In Progress', 'Resolved') DEFAULT 'Pending'");
    }
};
