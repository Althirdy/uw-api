<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Http\Controllers\Controller;
use App\Services\NationalIdOcrService;
use Illuminate\Http\Request;

class IdVerificationController extends BaseApiController
{
    protected $ocrService;

    public function __construct(NationalIdOcrService $ocrService)
    {
        $this->ocrService = $ocrService;
    }

    /**
     * Scan National ID front and extract data.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
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
            $base64Image = 'data:' . $mimeType . ';base64,' . base64_encode(file_get_contents($image->getRealPath()));

            Log::info('ID scan initiated');

            // Extract data using OCR
            $result = $this->ocrService->extractIdData($base64Image);

            if (!$result['success']) {
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

            if (!empty($missingFields)) {
                $result['warning'] = 'Could not extract: ' . implode(', ', $missingFields);
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
