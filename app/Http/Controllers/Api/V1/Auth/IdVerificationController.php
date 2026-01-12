<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\GeminiService;
use App\Services\NationalIdOcrService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class IdVerificationController extends BaseApiController
{
    protected $ocrService;

    protected $geminiService;

    public function __construct(NationalIdOcrService $ocrService, GeminiService $geminiService)
    {
        $this->ocrService = $ocrService;
        $this->geminiService = $geminiService;
    }

    /**
     * Scan National ID front and extract data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function scanIdFront(Request $request)
    {
        $request->validate([
            'image' => 'required|file|mimes:jpg,jpeg,png,heic,heif|max:10240', // 10MB max
        ]);

        try {
            $image = $request->file('image');
            $mimeType = $image->getMimeType();
            $fileContent = file_get_contents($image->getRealPath());

            // Compress image to improve Gemini API response time
            $optimizedContent = $this->compressImage($fileContent, $mimeType);

            // Try Gemini first for better accuracy and authenticity verification
            try {
                $startTime = microtime(true);
                $geminiResult = $this->geminiService->analyzeNationalId($optimizedContent, $mimeType);
                $geminiDuration = microtime(true) - $startTime;

                Log::info('Gemini API response time', [
                    'duration_seconds' => round($geminiDuration, 2),
                    'original_size_kb' => round(strlen($fileContent) / 1024, 2),
                    'compressed_size_kb' => round(strlen($optimizedContent) / 1024, 2),
                ]);

                // Check if back side was detected
                if ($geminiResult['back_side_detected'] ?? false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'It looks like you uploaded the BACK side of the ID. Please upload the FRONT side.',
                    ], 400);
                }

                // Check for image quality issues
                if ($geminiResult['image_quality_issue'] ?? false) {
                    return response()->json([
                        'success' => false,
                        'message' => 'The image quality is too low. Please upload a clearer photo of your National ID.',
                    ], 400);
                }

                // Check authenticity
                if (! ($geminiResult['is_authentic'] ?? false)) {
                    Log::warning('National ID authenticity check failed', [
                        'confidence' => $geminiResult['confidence'] ?? null,
                        'reasoning' => $geminiResult['reasoning'] ?? null,
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid National ID. Please upload a clear photo of your valid Philippine National ID.',
                    ], 400);
                }

                $data = $geminiResult['data'] ?? [];

                // Check for missing required fields
                $requiredFields = ['last_name', 'given_name', 'middle_name', 'date_of_birth'];
                $missingFields = [];

                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $missingFields[] = $field;
                    }
                }

                $result = [
                    'success' => true,
                    'data' => [
                        'pcn_number' => $data['pcn_number'] ?? null,
                        'last_name' => $data['last_name'] ?? null,
                        'given_name' => $data['given_name'] ?? null,
                        'middle_name' => $data['middle_name'] ?? null,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'address' => $data['address'] ?? null,
                        'barangay' => $data['barangay'] ?? null,
                        'city' => $data['city'] ?? null,
                        'region' => $data['region'] ?? null,
                        'full_address' => $data['full_address'] ?? null,
                        'postal_code' => $data['postal_code'] ?? null,
                    ],
                    'debug' => [
                        'verification_method' => 'gemini',
                        'confidence' => $geminiResult['confidence'] ?? null,
                    ],
                ];

                if (! empty($missingFields)) {
                    $result['warning'] = 'Could not extract: '.implode(', ', $missingFields);
                }

                Log::info('National ID verified with Gemini', [
                    'pcn' => $data['pcn_number'] ?? 'unknown',
                    'confidence' => $geminiResult['confidence'] ?? null,
                    'duration_seconds' => round($geminiDuration, 2),
                ]);

                return response()->json($result, 200);

            } catch (\Exception $geminiException) {
                // Gemini failed, fall back to OCR.space
                Log::warning('Gemini failed, falling back to OCR', [
                    'error' => $geminiException->getMessage(),
                    'attempted_duration_seconds' => isset($startTime) ? round(microtime(true) - $startTime, 2) : null,
                ]);

                // Convert to base64 for OCR service
                $base64Image = 'data:'.$mimeType.';base64,'.base64_encode($fileContent);

                $ocrResult = $this->ocrService->extractIdData($base64Image);

                if (! $ocrResult['success']) {
                    // Both Gemini and OCR failed
                    Log::error('Both Gemini and OCR failed', [
                        'gemini_error' => $geminiException->getMessage(),
                        'ocr_error' => $ocrResult['error'] ?? 'Unknown OCR error',
                    ]);

                    return response()->json([
                        'success' => false,
                        'message' => 'System temporarily unavailable. Please try again later.',
                    ], 503);
                }

                // OCR succeeded as fallback
                $data = $ocrResult['data'];

                // Check for missing required fields
                $requiredFields = ['last_name', 'given_name', 'middle_name', 'date_of_birth'];
                $missingFields = [];

                foreach ($requiredFields as $field) {
                    if (empty($data[$field])) {
                        $missingFields[] = $field;
                    }
                }

                $result = [
                    'success' => true,
                    'data' => [
                        'pcn_number' => $data['pcn_number'] ?? null,
                        'last_name' => $data['last_name'] ?? null,
                        'given_name' => $data['given_name'] ?? null,
                        'middle_name' => $data['middle_name'] ?? null,
                        'date_of_birth' => $data['date_of_birth'] ?? null,
                        'address' => $data['address'] ?? null,
                        'barangay' => $data['barangay'] ?? null,
                        'city' => $data['city'] ?? null,
                        'region' => $data['region'] ?? null,
                        'full_address' => $data['full_address'] ?? null,
                        'postal_code' => null, // OCR doesn't provide postal code
                    ],
                    'debug' => [
                        'verification_method' => 'ocr_fallback',
                        'raw_text' => $ocrResult['debug']['raw_text'] ?? null,
                    ],
                ];

                if (! empty($missingFields)) {
                    $result['warning'] = 'Could not extract: '.implode(', ', $missingFields);
                }

                Log::info('National ID verified with OCR fallback', [
                    'pcn' => $data['pcn_number'] ?? 'unknown',
                ]);

                return response()->json($result, 200);
            }

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

    /**
     * Compress image to optimize Gemini API performance.
     *
     * @param  string  $fileContent  Original image binary content
     * @param  string  $mimeType  Image MIME type
     * @return string Compressed image binary content
     */
    protected function compressImage(string $fileContent, string $mimeType): string
    {
        try {
            // Load image based on MIME type
            $image = imagecreatefromstring($fileContent);
            if ($image === false) {
                // If image processing fails, return original
                Log::warning('Image compression failed, using original');
                return $fileContent;
            }

            $originalWidth = imagesx($image);
            $originalHeight = imagesy($image);

            // Target max width for optimal balance between quality and speed
            $maxWidth = 1920;
            $maxHeight = 1920;

            // Only resize if image is larger than max dimensions
            if ($originalWidth <= $maxWidth && $originalHeight <= $maxHeight) {
                imagedestroy($image);
                return $fileContent;
            }

            // Calculate new dimensions maintaining aspect ratio
            $ratio = min($maxWidth / $originalWidth, $maxHeight / $originalHeight);
            $newWidth = (int) round($originalWidth * $ratio);
            $newHeight = (int) round($originalHeight * $ratio);

            // Create resized image
            $resizedImage = imagecreatetruecolor($newWidth, $newHeight);

            // Preserve transparency for PNG images
            if (strpos($mimeType, 'png') !== false) {
                imagealphablending($resizedImage, false);
                imagesavealpha($resizedImage, true);
                $transparent = imagecolorallocatealpha($resizedImage, 0, 0, 0, 127);
                imagefilledrectangle($resizedImage, 0, 0, $newWidth, $newHeight, $transparent);
            }

            imagecopyresampled($resizedImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $originalWidth, $originalHeight);

            // Output to buffer
            ob_start();
            if (strpos($mimeType, 'png') !== false) {
                imagepng($resizedImage, null, 8); // Compression level 8 (0-9)
            } else {
                imagejpeg($resizedImage, null, 90); // Quality 90 (0-100)
            }
            $compressedContent = ob_get_clean();

            // Cleanup
            imagedestroy($image);
            imagedestroy($resizedImage);

            return $compressedContent;

        } catch (\Exception $e) {
            Log::warning('Image compression exception, using original', [
                'error' => $e->getMessage(),
            ]);
            return $fileContent;
        }
    }
}
