<?php

namespace App\Services;

use App\Events\AccidentDetected;
use App\Events\FalseAlarmDetected;
use App\Models\Accident;
use App\Models\cctvDevices;
use App\Models\FalseAlarm;
use App\Models\IncidentMedia;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YoloAccidentService
{
    protected $geminiService;

    protected $fileUploadService;

    public function __construct(GeminiService $geminiService, FileUploadService $fileUploadService)
    {
        $this->geminiService = $geminiService;
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Main orchestrator for processing YOLO detections.
     *
     * @param  UploadedFile  $file  The snapshot image file
     * @param  int  $deviceId  ID of the CCTV device
     * @param  string|null  $detectedAt  When the detection occurred
     * @return array Processing result with success status and relevant data
     *
     * @throws \Exception If processing fails
     */
    public function processDetection(UploadedFile $file, int $deviceId, ?string $detectedAt = null): array
    {
        $startTime = microtime(true);

        // Step 1: Fetch CCTV device with location
        $cctvDevice = $this->getCctvDeviceWithLocation($deviceId);

        // Step 2: Verify with Gemini AI before uploading
        $fileContent = file_get_contents($file->getRealPath());
        $mimeType = $file->getMimeType() ?? 'image/jpeg';

        $aiAnalysis = $this->verifyEmergencyWithAI($fileContent, $mimeType, $cctvDevice);

        // Step 3: Handle false alarm (no upload, no storage)
        if (! ($aiAnalysis['is_valid'] ?? false)) {
            return $this->handleFalseAlarm($aiAnalysis, $cctvDevice, microtime(true) - $startTime);
        }

        // Step 4: Valid emergency - Create accident records and upload image
        return $this->processValidEmergency($file, $aiAnalysis, $cctvDevice, $detectedAt, microtime(true) - $startTime);
    }

    /**
     * Fetch CCTV device with location relationship and validate.
     *
     *
     * @throws \Exception
     */
    protected function getCctvDeviceWithLocation(int $deviceId): cctvDevices
    {
        $cctvDevice = cctvDevices::with('location')->find($deviceId);

        if (! $cctvDevice) {
            throw new \Exception("CCTV Device not found: ID {$deviceId}");
        }

        if (! $cctvDevice->location) {
            throw new \Exception("CCTV Device {$deviceId} has no location assigned");
        }

        Log::info('YOLO Service: CCTV Device loaded', [
            'device_id' => $cctvDevice->id,
            'device_name' => $cctvDevice->device_name,
            'location' => $cctvDevice->location->location_name,
        ]);

        return $cctvDevice;
    }

    /**
     * Send image to Gemini AI for emergency verification.
     *
     * @param  string  $fileContent  Raw binary content of the image
     * @param  string  $mimeType  MIME type of the image
     * @param  cctvDevices  $cctvDevice  The CCTV device for context
     * @return array AI analysis result
     *
     * @throws \Exception
     */
    protected function verifyEmergencyWithAI(string $fileContent, string $mimeType, cctvDevices $cctvDevice): array
    {
        $context = [
            'device_name' => $cctvDevice->device_name,
            'location' => $cctvDevice->location->location_name.', '.$cctvDevice->location->barangay,
        ];

        Log::info('YOLO Service: Sending image to Gemini AI for verification');

        $aiAnalysis = $this->geminiService->analyzeImage($fileContent, $mimeType, $context);

        if (! $aiAnalysis) {
            throw new \Exception('Gemini AI analysis failed or returned null');
        }

        Log::info('YOLO Service: Gemini AI analysis complete', [
            'is_valid' => $aiAnalysis['is_valid'] ?? false,
            'accident_type' => $aiAnalysis['accident_type'] ?? null,
            'confidence' => $aiAnalysis['confidence'] ?? null,
            'reasoning' => $aiAnalysis['reasoning'] ?? null,
        ]);

        return $aiAnalysis;
    }

    /**
     * Handle false alarm case - save to database, broadcast event, and return.
     *
     * @param  array  $aiAnalysis  The AI analysis result
     * @param  cctvDevices  $cctvDevice  The CCTV device
     * @param  float  $processingTime  Time taken in seconds
     * @return array Response data for false alarm
     */
    protected function handleFalseAlarm(array $aiAnalysis, cctvDevices $cctvDevice, float $processingTime): array
    {
        $processingTimeMs = round($processingTime * 1000, 2);

        // Save false alarm to database for tracking
        $falseAlarm = FalseAlarm::createFromDetection($cctvDevice->id, $aiAnalysis);

        Log::warning('YOLO Service: False alarm detected - Image discarded, no Cloudinary upload', [
            'false_alarm_id' => $falseAlarm->id,
            'device_id' => $cctvDevice->id,
            'device_name' => $cctvDevice->device_name,
            'reasoning' => $aiAnalysis['reasoning'] ?? 'Unknown',
            'processing_time_ms' => $processingTimeMs,
        ]);

        // Broadcast false alarm event for real-time monitoring
        broadcast(new FalseAlarmDetected($falseAlarm));

        return [
            'success' => true,
            'false_alarm' => true,
            'false_alarm_id' => $falseAlarm->id,
            'message' => 'Detection verified as false alarm by AI - Image not stored',
            'reasoning' => $aiAnalysis['reasoning'] ?? 'Not a real emergency',
            'device_name' => $cctvDevice->device_name,
            'location' => $cctvDevice->location->location_name,
            'processing_time_ms' => $processingTimeMs,
        ];
    }

    /**
     * Process valid emergency - upload image, create records, and broadcast.
     *
     * @param  UploadedFile  $file  The snapshot image file
     * @param  array  $aiAnalysis  The AI analysis result
     * @param  cctvDevices  $cctvDevice  The CCTV device
     * @param  string|null  $detectedAt  When the detection occurred
     * @param  float  $processingTime  Time taken so far in seconds
     * @return array Response data with accident details
     *
     * @throws \Exception
     */
    protected function processValidEmergency(
        UploadedFile $file,
        array $aiAnalysis,
        cctvDevices $cctvDevice,
        ?string $detectedAt,
        float $processingTime
    ): array {
        DB::beginTransaction();

        try {
            // Upload to Cloudinary (only for valid emergencies)
            Log::info('YOLO Service: Valid emergency verified - Uploading to Cloudinary');
            $uploadResult = $this->fileUploadService->uploadSingle($file, 'yolo');
            $publicUrl = $uploadResult['public_url'] ?? null;
            $storagePath = $uploadResult['storage_path'] ?? null;

            if (! $publicUrl) {
                throw new \Exception('Failed to upload image to Cloudinary');
            }

            Log::info('YOLO Service: Image uploaded successfully', ['url' => $publicUrl]);

            // Extract location from device
            $latitude = $cctvDevice->location->latitude;
            $longitude = $cctvDevice->location->longitude;

            // Create accident record with AI-generated data
            $accident = Accident::create([
                'cctv_device_id' => $cctvDevice->id,
                'title' => $aiAnalysis['title'] ?? 'Insidente na Natukoy',
                'description' => $aiAnalysis['description'] ?? 'Awtomatikong natukoy ng AI system',
                'latitude' => $latitude,
                'longitude' => $longitude,
                'occurred_at' => $detectedAt ?? now(),
                'accident_type' => $aiAnalysis['accident_type'] ?? 'Accident',
                'status' => 'Pending',
                'severity' => $aiAnalysis['severity'] ?? 'Medium',
            ]);

            Log::info('YOLO Service: Accident record created', ['accident_id' => $accident->id]);

            // Create incident media record with full AI metadata
            $incidentMedia = IncidentMedia::create([
                'source_type' => Accident::class,
                'source_id' => $accident->id,
                'source_category' => 'cctv_detection',
                'media_type' => 'image',
                'original_path' => $publicUrl,
                'blurred_path' => null,
                'public_id' => $storagePath,
                'original_filename' => $uploadResult['original_filename'] ?? $file->getClientOriginalName(),
                'file_size' => $uploadResult['file_size'] ?? $file->getSize(),
                'mime_type' => $uploadResult['mime_type'] ?? $file->getMimeType(),
                'detection_metadata' => [
                    'detection_source' => 'yolo_with_gemini_ai',
                    'gemini_analysis' => $aiAnalysis,
                    'device_id' => $cctvDevice->id,
                    'device_name' => $cctvDevice->device_name,
                    'ai_confidence' => $aiAnalysis['confidence'] ?? null,
                    'detected_objects' => $aiAnalysis['detected_objects'] ?? [],
                    'ai_reasoning' => $aiAnalysis['reasoning'] ?? null,
                ],
                'device_identifier' => $cctvDevice->device_name,
                'captured_at' => $detectedAt ?? now(),
            ]);

            Log::info('YOLO Service: Incident media created', ['media_id' => $incidentMedia->id]);

            DB::commit();

            // Broadcast the event to all connected clients
            broadcast(new AccidentDetected($accident));

            $processingTimeMs = round($processingTime * 1000, 2);

            Log::info('YOLO Service: Emergency verified and broadcasted', [
                'accident_id' => $accident->id,
                'accident_type' => $accident->accident_type,
                'severity' => $accident->severity,
                'confidence' => $aiAnalysis['confidence'] ?? null,
                'processing_time_ms' => $processingTimeMs,
            ]);

            return [
                'success' => true,
                'false_alarm' => false,
                'accident_id' => $accident->id,
                'media_id' => $incidentMedia->id,
                'accident_type' => $accident->accident_type,
                'severity' => $accident->severity,
                'title' => $accident->title,
                'description' => $accident->description,
                'latitude' => $accident->latitude,
                'longitude' => $accident->longitude,
                'location_name' => $cctvDevice->location->location_name,
                'barangay' => $cctvDevice->location->barangay,
                'occurred_at' => $accident->occurred_at,
                'confidence' => $aiAnalysis['confidence'] ?? null,
                'detected_objects' => $aiAnalysis['detected_objects'] ?? [],
                'processing_time_ms' => $processingTimeMs,
                'image_url' => $publicUrl,
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('YOLO Service: Error processing valid emergency', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}
