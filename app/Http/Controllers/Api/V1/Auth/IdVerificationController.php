<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\NationalIdOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdVerificationController extends BaseApiController
{
    protected $ocrService;

    public function __construct(NationalIdOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Scan National ID Front
     * 
     * Extract personal information from the front of a Philippine National ID using OCR technology.
     * Supports multiple image formats including HEIC and HEIF.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam image file required National ID front image (jpg, jpeg, png, heic, heif, max 10MB). Example: id_front.jpg
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "last_name": "Doe",
     *     "given_name": "John",
     *     "middle_name": "Michael",
     *     "date_of_birth": "1990-05-15"
     *   }
     * }
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "last_name": "Doe",
     *     "given_name": "John",
     *     "middle_name": "",
     *     "date_of_birth": "1990-05-15"
     *   },
     *   "warning": "Could not extract: middle_name"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Could not extract data from ID",
     *   "debug": {}
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "image": ["The image field is required."]
     *   }
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "An unexpected error occurred while processing the ID.",
     *   "error": null
     * }
     */
    public function scanIdFront(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,heic,heif|max:10240', // 10MB max
        ]);

        try {
            $image = $request->file('image');

            // Convert image to base64
            $mimeType = $image->getMimeType();
            $base64Image = 'data:'.$mimeType.';base64,'.base64_encode(file_get_contents($image->getRealPath()));

            Log::info('ID scan initiated');

            // Extract data using OCR
            $result = $this->ocrService->extractIdData($base64Image);

            if (! $result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                    'debug' => $result['debug'] ?? null,
                ], 400);
            }

            // Check for missing required fields
            $data = $result['data'];
            $requiredFields = ['last_name', 'given_name', 'middle_name', 'date_of_birth'];
            $missingFields = [];

            foreach ($requiredFields as $field) {
                if (empty($data[$field])) {
                    $missingFields[] = $field;
                }
            }

            if (! empty($missingFields)) {
                $result['warning'] = 'Could not extract: '.implode(', ', $missingFields);
            }

            return response()->json($result, 200);
        } catch (\Exception $e) {
            Log::error('ID scan failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred while processing the ID.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
