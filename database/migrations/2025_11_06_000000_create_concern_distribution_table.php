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
        Schema::create('concern_distribution', function (Blueprint $table) {
            $table->id();
            $table->foreignId('concern_id')->constrained('concerns')->onDelete('cascade');
            $table->foreignId('purok_leader_id')->constrained('users')->onDelete('cascade');
            $table->enum('status', ['assigned', 'acknowledged', 'in_progress', 'resolved'])->default('assigned');
            $table->timestamp('assigned_at')->useCurrent();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            // Indexes for better query performance
            $table->index('concern_id');
            $table->index('purok_leader_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('concern_distribution');
    }
};
