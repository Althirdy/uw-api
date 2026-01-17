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
        Schema::create('false_alarms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cctv_device_id')
                ->constrained('cctv_devices')
                ->onDelete('cascade');
            $table->string('attempted_accident_type')->nullable();
            $table->text('gemini_reasoning');
            $table->decimal('confidence_score', 5, 2)->nullable();
            $table->json('detected_objects')->nullable();
            $table->json('gemini_metadata')->nullable();
            $table->timestamp('detected_at');
            $table->timestamps();

            // Performance indexes for fast queries
            $table->index('cctv_device_id');
            $table->index('created_at');
            $table->index('detected_at');
            $table->index(['cctv_device_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('false_alarms');
    }
};
