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
        Schema::table('concerns', function (Blueprint $table) {
            // Store user's original selections
            $table->string('user_selected_category')->nullable()->after('category');
            $table->enum('user_selected_severity', ['low', 'medium', 'high'])->nullable()->after('severity');
            
            // Store AI-detected values
            $table->string('ai_category')->nullable()->after('user_selected_severity');
            $table->enum('ai_severity', ['low', 'medium', 'high'])->nullable()->after('ai_category');
            
            // Store AI metadata
            $table->decimal('ai_confidence', 3, 2)->nullable()->after('ai_severity')->comment('AI confidence score 0.00-1.00');
            $table->timestamp('ai_processed_at')->nullable()->after('ai_confidence');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('concerns', function (Blueprint $table) {
            $table->dropColumn([
                'user_selected_category',
                'user_selected_severity',
                'ai_category',
                'ai_severity',
                'ai_confidence',
                'ai_processed_at'
            ]);
        });
    }
};
