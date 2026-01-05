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
        Schema::create('user_suspensions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('punishment_type', ['warning_1', 'warning_2', 'suspension'])->comment('warning_1: 3 days, warning_2: 7 days, suspension: permanent');
            $table->integer('duration_days')->nullable()->comment('Duration in days, null for permanent');
            $table->timestamp('suspended_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->comment('When the suspension expires, null for permanent');
            $table->enum('status', ['active', 'expired', 'revoked'])->default('active');
            $table->text('reason')->nullable()->comment('Reason for suspension');
            $table->foreignId('suspended_by')->constrained('users')->comment('Admin who applied the suspension');
            $table->timestamps();
            
            // Index for faster queries
            $table->index('user_id');
            $table->index(['user_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_suspensions');
    }
};
