<?php

namespace App\Http\Controllers\Api\Yolo;

use App\Http\Controllers\Api\BaseApiController;
use Illuminate\Http\Request;
use App\Services\FileUploadService;
use App\Models\Accident;
use App\Models\IncidentMedia;
<<<<<<< HEAD
=======
use App\Events\AccidentDetected;
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YoloAccidentController extends BaseApiController
{
    protected $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    private function ValidateSnapShot($file)
    {
        // Add validation logic here if needed
        return true;
    }

    private function ProcessImage($file, Request $request)
    {
        try {
            DB::beginTransaction();

<<<<<<< HEAD
            // 1. Upload image to storage and get public URL
=======
            // 1. Get dynamic data from request (sent by YOLO script)
            $accidentType = $request->input('accident_type', 'accident');
            $severity = $request->input('severity', 'Medium'); // Default: Medium (matches ENUM)
            $title = $request->input('title', 'YOLO Detected Incident');
            $description = $request->input('description', 'Incident automatically detected by YOLO AI system');
            $latitude = $request->input('latitude', '14.123456');
            $longitude = $request->input('longitude', '121.123456');

            // 2. Upload image to storage and get public URL
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
            $uploadResult = $this->fileUploadService->uploadSingle($file, 'yolo');
            $publicUrl = $uploadResult['public_url'] ?? null;
            $storagePath = $uploadResult['storage_path'] ?? null;

<<<<<<< HEAD
            // 2. Create accident record with hardcoded data
            $accident = Accident::create([
                'title' => 'YOLO Detected Accident',
                'description' => 'Accident automatically detected by YOLO AI system',
                'latitude' => '14.123456',
                'longitude' => '121.123456',
                'occurred_at' => now(),
                'accident_type' => 'accident',
                'status' => 'pending',
                'severity' => 'medium',
            ]);

            // 3. Create incident media record and link to accident
=======
            // 3. Create accident record with dynamic data from YOLO
            $accident = Accident::create([
                'title' => $title,
                'description' => $description,
                'latitude' => $latitude,
                'longitude' => $longitude,
                'occurred_at' => now(),
                'accident_type' => $accidentType,
                'status' => 'pending',
                'severity' => $severity,
            ]);

            // 4. Create incident media record and link to accident
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
            $incidentMedia = IncidentMedia::create([
                'source_type' => Accident::class,
                'source_id' => $accident->id,
                'source_category' => 'cctv_detection', // Use existing enum value
                'media_type' => 'image',
                'original_path' => $publicUrl,
                'blurred_path' => null,
                'public_id' => $storagePath, // Use storage_path like in ManualConcernController
                'original_filename' => $uploadResult['original_filename'] ?? $file->getClientOriginalName(),
                'file_size' => $uploadResult['file_size'] ?? $file->getSize(),
                'mime_type' => $uploadResult['mime_type'] ?? $file->getMimeType(),
                'detection_metadata' => [
                    'detection_source' => 'yolo',
<<<<<<< HEAD
                    'detection_type' => 'accident',
=======
                    'detection_type' => $accidentType,
                    'severity' => $severity,
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
                    'confidence' => null,
                    'detected_objects' => null,
                ],
                'device_identifier' => 'yolo_camera_01', // You can make this dynamic later
                'captured_at' => now(),
            ]);

            DB::commit();

<<<<<<< HEAD
=======
            // 5. Broadcast the event to all connected clients
            broadcast(new AccidentDetected($accident));

            Log::info('Accident detected and broadcasted', [
                'accident_id' => $accident->id,
                'type' => $accidentType,
                'severity' => $severity
            ]);

>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)
            return [
                'success' => true,
                'accident_id' => $accident->id,
                'img' => $publicUrl,
                'media_id' => $incidentMedia->id,
                'title' => $accident->title,
                'description' => $accident->description,
                'latitude' => $accident->latitude,
                'longitude' => $accident->longitude,
                'occurred_at' => $accident->occurred_at,
                'accident_type' => $accident->accident_type,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('YOLO Accident Processing Error: ' . $e->getMessage());
            
            throw $e;
        }
    }

    public function ProcessSnapShot(Request $request)
    {
        try {
            if (!$request->hasFile('snapshot')) {
                return $this->sendError('No snapshot file provided', null, 400);
            }

            if (!$this->ValidateSnapShot($request->file('snapshot'))) {
                return $this->sendError('Invalid snapshot', null, 400);
            }

<<<<<<< HEAD
            $result = $this->ProcessImage($request->file('snapshot'));
=======
            $result = $this->ProcessImage($request->file('snapshot'), $request);
>>>>>>> ed05b14 (feat: Add Laravel Reverb real-time updates for YOLO accident detection)

            return $this->sendResponse($result, 'Accident detected and saved successfully', 200);

        } catch (\Exception $e) {
            return $this->sendError('Failed to process accident: ' . $e->getMessage(), null, 500);
        }
    }
}
