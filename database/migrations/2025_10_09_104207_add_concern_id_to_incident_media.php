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
        Schema::table('incident_media', function (Blueprint $table) {
            // Add polymorphic relationship fields
            $table->string('source_type'); // 'App\Models\Citizen\Concern', 'App\Models\CctvDevices', etc.
            $table->unsignedBigInteger('source_id'); // ID of the source record
            
            // Add source category for easier filtering
            $table->enum('source_category', ['citizen_concern', 'device_snapshot', 'cctv_detection'])->default('citizen_concern');
            
            // Add detection metadata for YOLO detections
            $table->json('detection_metadata')->nullable(); // Store YOLO detection data
            
            // Add device information for device snapshots
            $table->string('device_identifier')->nullable(); // Device ID/name for snapshots
            
            // Add temporal information
            $table->timestamp('captured_at')->nullable(); // When media was actually captured
            
            // Add index for better performance
            $table->index(['source_type', 'source_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('incident_media', function (Blueprint $table) {
            // Drop index if exists using raw SQL
            \DB::statement('DROP INDEX IF EXISTS `incident_media_source_type_source_id_index` ON `incident_media`');
        });
        
        // Check and drop columns individually if they exist
        if (Schema::hasColumn('incident_media', 'source_type')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('source_type');
            });
        }
        
        if (Schema::hasColumn('incident_media', 'source_id')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('source_id');
            });
        }
        
        if (Schema::hasColumn('incident_media', 'source_category')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('source_category');
            });
        }
        
        if (Schema::hasColumn('incident_media', 'detection_metadata')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('detection_metadata');
            });
        }
        
        if (Schema::hasColumn('incident_media', 'device_identifier')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('device_identifier');
            });
        }
        
        if (Schema::hasColumn('incident_media', 'captured_at')) {
            Schema::table('incident_media', function (Blueprint $table) {
                $table->dropColumn('captured_at');
            });
        }
    }
};
