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
     * Process snapshot from YOLO detection system.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function ProcessSnapShot(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'snapshot' => 'required|file|image|max:10240', // Max 10MB
                'device_id' => 'required|integer|exists:cctv_devices,id',
                'detected_at' => 'nullable|date',
            ]);

            if ($validator->fails()) {
                return $this->sendError('Validation failed', $validator->errors(), 422);
            }

            // Get parameters from Python YOLO script
            $file = $request->file('snapshot');
            $deviceId = $request->input('device_id');
            $detectedAt = $request->input('detected_at');
            
            Log::info('YOLO Snapshot received from Python', [
                'device_id' => $deviceId,
                'detected_at' => $detectedAt,
                'file_size' => $file->getSize(),
            ]);

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
