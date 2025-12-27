<?php

namespace App\Http\Controllers\Api\V1\Yolo;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\YoloAccidentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * YoloAccidentController
 *
 * Handles HTTP requests for CCTV-based accident detection via YOLO integration.
 * Business logic is delegated to YoloAccidentService for separation of concerns.
 *
 * Expected POST Parameters from Python YOLO script:
 * - snapshot (file, required): Image file captured from CCTV
 * - device_id (integer, optional): ID of the CCTV device from cctv_devices table
 *   - If not provided, uses TEST_DEVICE_ID for webcam testing
 * - detected_at (timestamp, optional): When the detection occurred
 *
 * Architecture:
 * Controller (thin) -> Service (business logic) -> [GeminiService, FileUploadService, Models]
 *
 * Flow:
 * 1. Validate HTTP request
 * 2. Delegate to YoloAccidentService
 * 3. Format and return HTTP response
 */
class YoloAccidentController extends BaseApiController
{
    protected $yoloService;

    // Hardcoded test device ID for webcam testing (change this to your actual test device ID)
    const TEST_DEVICE_ID = 1; // TODO: Update this after creating test CCTV device

    public function __construct(YoloAccidentService $yoloService)
    {
        $this->yoloService = $yoloService;
    }

    /**
     * Process YOLO Detection Snapshot
     * 
     * This endpoint receives images from the Python YOLO script and processes them through AI verification.
     * The system will automatically verify if the detection is a real emergency or a false alarm using Google Gemini AI.
     * 
     * **Flow:**
     * 1. Receives snapshot from Python YOLO script
     * 2. Verifies with Gemini AI (checks for real emergencies vs false alarms)
     * 3. If false alarm: Returns success with reasoning, does NOT upload to cloud storage
     * 4. If valid emergency: Uploads to Cloudinary, creates database records, broadcasts to web dashboard
     * 
     * **Examples of Valid Emergencies:**
     * - House/building fires with visible flames or heavy smoke
     * - Road flooding with significant water accumulation
     * - Vehicle collisions or accidents
     * 
     * **Examples of False Alarms (rejected):**
     * - Candles, lighters, campfires, cooking fires
     * - Small puddles or light rain
     * - Parked vehicles or minor incidents
     *
     * @group YOLO Integration
     * @authenticated
     * 
     * @bodyParam snapshot file required The snapshot image captured from CCTV. Must be jpg/png format, max 10MB. Example: fire_detection.jpg
     * @bodyParam device_id integer CCTV device ID from the cctv_devices table. Defaults to test device (ID: 1) if not provided. Example: 1
     * @bodyParam detected_at string Timestamp when the detection occurred (format: Y-m-d H:i:s). Defaults to current time. Example: 2025-12-27 10:30:00
     * 
     * @response 201 scenario="Valid Emergency Detected" {
     *   "success": true,
     *   "message": "Emergency verified and saved successfully",
     *   "data": {
     *     "success": true,
     *     "false_alarm": false,
     *     "accident_id": 123,
     *     "media_id": 456,
     *     "accident_type": "Fire",
     *     "severity": "High",
     *     "title": "Malaking Sunog sa Bahay",
     *     "description": "Isang bahay ang lubos na nasusunog, na may malalaking apoy at makapal na usok...",
     *     "latitude": "14.5995",
     *     "longitude": "120.9842",
     *     "location_name": "Main Street Corner",
     *     "barangay": "Barangay 1",
     *     "occurred_at": "2025-12-27T10:30:00.000000Z",
     *     "confidence": 95,
     *     "detected_objects": ["flames", "smoke", "building"],
     *     "processing_time_ms": 3542.67,
     *     "image_url": "https://res.cloudinary.com/your-cloud/image/upload/yolo/detection123.jpg"
     *   }
     * }
     * 
     * @response 200 scenario="False Alarm Detected" {
     *   "success": true,
     *   "message": "Detection analyzed: False alarm (no emergency action needed)",
     *   "data": {
     *     "success": true,
     *     "false_alarm": true,
     *     "message": "Detection verified as false alarm by AI - Image not stored",
     *     "reasoning": "This appears to be a small candle flame, not a dangerous fire that requires emergency response.",
     *     "device_name": "TEST_WEBCAM",
     *     "location": "Test Location Manila",
     *     "processing_time_ms": 2345.12
     *   }
     * }
     * 
     * @response 422 scenario="Validation Error" {
     *   "success": false,
     *   "message": "Validation failed",
     *   "data": {
     *     "snapshot": ["The snapshot field is required."],
     *     "device_id": ["The selected device id is invalid."]
     *   }
     * }
     * 
     * @response 500 scenario="Processing Error" {
     *   "success": false,
     *   "message": "Failed to process detection: Gemini AI analysis failed or returned null",
     *   "data": null
     * }
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function ProcessSnapShot(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'snapshot' => 'required|file|image|max:10240', // Max 10MB
                'device_id' => 'nullable|integer|exists:cctv_devices,id',
                'detected_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            // Get parameters
            $file = $request->file('snapshot');
            $deviceId = $request->input('device_id', self::TEST_DEVICE_ID);
            $detectedAt = $request->input('detected_at');

            // Delegate to service for business logic
            $result = $this->yoloService->processDetection($file, $deviceId, $detectedAt);

            // Format response based on result
            if ($result['false_alarm'] ?? false) {
                return $this->sendResponse(
                    $result,
                    'Detection analyzed: False alarm (no emergency action needed)',
                    200
                );
            }

            return $this->sendResponse(
                $result,
                'Emergency verified and saved successfully',
                201
            );

        } catch (\Exception $e) {
            Log::error('YoloAccidentController: Exception caught', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->sendError(
                'Failed to process detection: '.$e->getMessage(),
                null,
                500
            );
        }
    }
}
