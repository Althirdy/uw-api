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
        Schema::table('public_posts', function (Blueprint $table) {
            // Add public_id column after id
            // This is used to generate a unique 6-character identifier for each post
            // Example: 0ECB9B, A3F2D1, etc.
            $table->string('public_id', 6)->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_posts', function (Blueprint $table) {
            // Drop the unique index first
            $table->dropUnique(['public_id']);
            // Then drop the column
            $table->dropColumn('public_id');
        });
    }
};
