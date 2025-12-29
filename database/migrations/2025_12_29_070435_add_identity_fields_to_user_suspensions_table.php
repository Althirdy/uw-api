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
        Schema::table('user_suspensions', function (Blueprint $table) {
            // Store banned user's identity information for ban evasion prevention
            $table->string('phone_number')->nullable()->after('suspended_by');
            $table->string('first_name')->nullable()->after('phone_number');
            $table->string('middle_name')->nullable()->after('first_name');
            $table->string('last_name')->nullable()->after('middle_name');
            $table->string('suffix')->nullable()->after('last_name');

            // Add indexes for fast ban checking during registration
            $table->index('phone_number');
            $table->index(['first_name', 'last_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_suspensions', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex(['user_suspensions_phone_number_index']);
            $table->dropIndex(['user_suspensions_first_name_last_name_index']);

            // Drop columns
            $table->dropColumn(['phone_number', 'first_name', 'middle_name', 'last_name', 'suffix']);
        });
    }
};
