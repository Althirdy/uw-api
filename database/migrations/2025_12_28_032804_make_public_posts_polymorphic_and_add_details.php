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
        // First, drop the foreign key and index if they exist
        if (Schema::hasColumn('public_posts', 'report_id')) {
            try {
                Schema::table('public_posts', function (Blueprint $table) {
                    $table->dropForeign(['report_id']);
                });
            } catch (\Exception $e) {
                // Foreign key doesn't exist, continue
            }

            try {
                Schema::table('public_posts', function (Blueprint $table) {
                    $table->dropIndex('public_posts_report_id_index');
                });
            } catch (\Exception $e) {
                // Index doesn't exist, continue
            }

            Schema::table('public_posts', function (Blueprint $table) {
                $table->dropColumn('report_id');
            });
        }

        // Now add the new columns
        Schema::table('public_posts', function (Blueprint $table) {
            // Add polymorphic columns if they don't exist
            if (! Schema::hasColumn('public_posts', 'postable_id')) {
                $table->nullableMorphs('postable');
            }

            // Add essential fields if they don't exist
            if (! Schema::hasColumn('public_posts', 'title')) {
                $table->string('title')->after('id');
            }
            if (! Schema::hasColumn('public_posts', 'content')) {
                $table->text('content')->after('title');
            }
            if (! Schema::hasColumn('public_posts', 'image_path')) {
                $table->string('image_path')->nullable()->after('content');
            }
            if (! Schema::hasColumn('public_posts', 'category')) {
                $table->string('category')->default('general')->after('image_path');
            }
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
