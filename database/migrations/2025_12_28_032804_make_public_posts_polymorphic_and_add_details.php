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
            // Remove the old foreign key and column
            $table->dropForeign(['report_id']);
            $table->dropColumn('report_id');

            // Add polymorphic columns
            $table->nullableMorphs('postable');

            // Add essential fields
            $table->string('title')->after('id');
            $table->text('content')->after('title');
            $table->string('image_path')->nullable()->after('content');
            $table->string('category')->default('general')->after('image_path');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('public_posts', function (Blueprint $table) {
            $table->unsignedBigInteger('report_id')->after('id')->nullable();
            $table->foreign('report_id')->references('id')->on('reports');

            $table->dropMorphs('postable');
            $table->dropColumn(['title', 'content', 'image_path', 'category']);
        });
    }
};
