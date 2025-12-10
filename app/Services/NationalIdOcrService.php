<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NationalIdOcrService
{
    protected $apiKey;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('services.ocr_space.api_key');
        $this->apiUrl = config('services.ocr_space.api_url');
    }

    /**
     * Extract data from National ID front image.
     *
     * @param string $base64Image Base64 encoded image
     * @return array
     */
    public function extractIdData($base64Image)
    {
        try {
            // Step 1: Send image to OCR.space
            $ocrResult = $this->sendToOcrSpace($base64Image);

            if (!$ocrResult['success']) {
                return [
                    'success' => false,
                    'error' => $ocrResult['error'] ?? 'OCR processing failed',
                ];
            }

            $fullText = $ocrResult['text'];

            // Step 2: Validate it's a National ID (check for PCN)
            $pcnNumber = $this->parsePcnNumber($fullText);

            if (!$pcnNumber) {
                // Check if it's the back of the ID
                if ($this->isBackOfId($fullText)) {
                    return [
                        'success' => false,
                        'error' => 'It looks like you uploaded the BACK side of the ID. Please upload the FRONT side.',
                    ];
                }

                return [
                    'success' => false,
                    'error' => 'Could not detect a valid Philippine National ID Number. Please ensure the image is clear and the ID number is visible.',
                ];
            }

            // Step 3: Parse all fields
            $names = $this->parseNameFields($fullText);
            $dateOfBirth = $this->parseDateOfBirth($fullText);
            $addressInfo = $this->parseAddress($fullText);

            // Step 4: Return structured data
            return [
                'success' => true,
                'data' => [
                    'pcn_number' => $pcnNumber,
                    'last_name' => $names['last_name'],
                    'given_name' => $names['given_name'],
                    'middle_name' => $names['middle_name'],
                    'date_of_birth' => $dateOfBirth,
                    'address' => $addressInfo['street'],
                    'barangay' => $addressInfo['barangay'],
                    'city' => $addressInfo['city'],
                    'region' => $addressInfo['region'],
                    'full_address' => $addressInfo['full_address'],
                ],
                'debug' => [
                    'raw_text' => $fullText,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('OCR extraction failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'An unexpected error occurred during ID processing.',
                'details' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send image to OCR.space API.
     */
    protected function sendToOcrSpace($base64Image)
    {
        try {
            $response = Http::timeout(60)
                ->asForm()
                ->post($this->apiUrl, [
                    'apikey' => $this->apiKey,
                    'base64Image' => $base64Image,
                    'language' => 'eng',
                    'isOverlayRequired' => 'false',
                    'detectOrientation' => 'true',
                    'scale' => 'true',
                    'OCREngine' => '2', // Engine 2 is better for IDs
                ]);

            if (!$response->successful()) {
                return [
                    'success' => false,
                    'error' => 'Failed to connect to OCR service',
                ];
            }

            $data = $response->json();

            if (!empty($data['IsErroredOnProcessing'])) {
                return [
                    'success' => false,
                    'error' => $data['ErrorMessage'][0] ?? 'OCR processing error',
                ];
            }

            $parsedResults = $data['ParsedResults'] ?? [];
            if (empty($parsedResults)) {
                return [
                    'success' => false,
                    'error' => 'No text detected in image',
                ];
            }

            return [
                'success' => true,
                'text' => $parsedResults[0]['ParsedText'] ?? '',
            ];

        } catch (\Exception $e) {
            Log::error('OCR.space API call failed', ['error' => $e->getMessage()]);
            return [
                'success' => false,
                'error' => 'Failed to process image: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Parse PCN Number (16-digit ID number).
     */
    protected function parsePcnNumber($fullText)
    {
        // Pattern: XXXX-XXXX-XXXX-XXXX
        if (preg_match('/(\d{4}[-\s]?\d{4}[-\s]?\d{4}[-\s]?\d{4})/', $fullText, $matches)) {
            $pcn = $matches[1];
            $cleaned = preg_replace('/[\s-]/', '', $pcn);

            if (strlen($cleaned) === 16) {
                return substr($cleaned, 0, 4) . '-' .
                       substr($cleaned, 4, 4) . '-' .
                       substr($cleaned, 8, 4) . '-' .
                       substr($cleaned, 12, 4);
            }
        }

        return null;
    }

    /**
     * Parse name fields (Last Name, Given Name, Middle Name).
     */
    protected function parseNameFields($fullText)
    {
        $names = [
            'last_name' => null,
            'given_name' => null,
            'middle_name' => null,
        ];

        // Last Name: Between "Apelyido/Last Name" and "Given Names"
        if (preg_match('/(?:Apelyido|Last Name)[\s\r\n]+([\s\S]+?)(?=\s*(?:Mga Pangalan|Given Names))/i', $fullText, $match)) {
            $names['last_name'] = trim($match[1]);
        }

        // Given Name: Between "Given Names" and "Middle Name"
        if (preg_match('/(?:Mga Pangalan|Given Names)[\s\r\n]+([\s\S]+?)(?=\s*(?:Gitnang Apelyido|Middle Name))/i', $fullText, $match)) {
            $names['given_name'] = trim($match[1]);
        }

        // Middle Name: Between "Middle Name" and "Date of Birth"
        if (preg_match('/(?:Gitnang Apelyido|Middle Name)[\s\r\n]+([\s\S]+?)(?=\s*(?:Petsa ng Kapanganakan|Date of Birth))/i', $fullText, $match)) {
            $names['middle_name'] = trim($match[1]);
        }

        return $names;
    }

    /**
     * Parse Date of Birth (convert to MM/DD/YYYY format).
     */
    protected function parseDateOfBirth($fullText)
    {
        $months = [
            'JANUARY' => '01', 'FEBRUARY' => '02', 'MARCH' => '03', 'APRIL' => '04',
            'MAY' => '05', 'JUNE' => '06', 'JULY' => '07', 'AUGUST' => '08',
            'SEPTEMBER' => '09', 'OCTOBER' => '10', 'NOVEMBER' => '11', 'DECEMBER' => '12',
        ];

        // Pattern: "MONTH DD, YYYY" after "Date of Birth" label
        if (preg_match('/(?:Petsa ng Kapanganakan|Date of Birth)[\s\r\n]+([A-Z]+)\s+(\d{1,2})\s*,\s*(\d{4})/i', $fullText, $match)) {
            $month = strtoupper($match[1]);
            $day = str_pad($match[2], 2, '0', STR_PAD_LEFT);
            $year = $match[3];

            $monthNum = $months[$month] ?? '00';
            return "{$monthNum}/{$day}/{$year}";
        }

        // Fallback: Find date anywhere (validate year range)
        if (preg_match_all('/([A-Z]+)\s+(\d{1,2})\s*,\s*(\d{4})/i', $fullText, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $year = (int)$match[3];
                if ($year >= 1900 && $year <= 2025) {
                    $month = strtoupper($match[1]);
                    $day = str_pad($match[2], 2, '0', STR_PAD_LEFT);
                    $monthNum = $months[$month] ?? '00';
                    return "{$monthNum}/{$day}/{$match[3]}";
                }
            }
        }

        return null;
    }

    /**
     * Parse address components.
     */
    protected function parseAddress($fullText)
    {
        $components = [
            'full_address' => null,
            'street' => null,
            'barangay' => null,
            'city' => null,
            'region' => null,
        ];

        // Find address after "Tirahan/Address" label
        if (preg_match('/(?:Tirahan|Address)[\s\r\n]+([\s\S]+)/i', $fullText, $match)) {
            $rawAddress = trim($match[1]);

            // Clean up lines
            $lines = explode("\n", $rawAddress);
            $cleanLines = [];

            foreach ($lines as $line) {
                $line = trim($line);

                // Skip empty lines
                if (empty($line)) continue;

                // Stop at known non-address keywords
                if (preg_match('/PHILIPPINE IDENTIFICATION|PCN|DATE OF BIRTH/i', $line)) break;

                // Filter noise
                if (strlen($line) < 4 && !preg_match('/NCR/i', $line)) continue;
                if (stripos($line, 'JITUD') !== false) continue;

                $cleanLines[] = $line;
            }

            $fullAddressStr = implode(', ', $cleanLines);
            $components['full_address'] = $fullAddressStr;

            // Split by comma
            $parts = array_map('trim', explode(',', $fullAddressStr));

            // Find indices
            $brgyIdx = -1;
            $cityIdx = -1;

            foreach ($parts as $i => $part) {
                if (preg_match('/BARANGAY|BRGY/i', $part)) {
                    $brgyIdx = $i;
                }
                if (preg_match('/CITY|MUNICIPALITY/i', $part)) {
                    $cityIdx = $i;
                }
            }

            // Assign components
            $endStreetIdx = count($parts);

            if ($brgyIdx !== -1) {
                $endStreetIdx = $brgyIdx;
                $components['barangay'] = $parts[$brgyIdx];
            } elseif ($cityIdx !== -1) {
                $endStreetIdx = $cityIdx;
            }

            if ($endStreetIdx > 0) {
                $components['street'] = implode(', ', array_slice($parts, 0, $endStreetIdx));
            }

            if ($cityIdx !== -1) {
                $components['city'] = $parts[$cityIdx];
            }

            $startRegionIdx = count($parts);
            if ($cityIdx !== -1) {
                $startRegionIdx = $cityIdx + 1;
            } elseif ($brgyIdx !== -1) {
                $startRegionIdx = $brgyIdx + 1;
            }

            if ($startRegionIdx < count($parts)) {
                $components['region'] = implode(', ', array_slice($parts, $startRegionIdx));
            }
        }

        return $components;
    }

    /**
     * Check if the text is from the back of the ID.
     */
    protected function isBackOfId($fullText)
    {
        $backKeywords = ['date of issue', 'blood type', 'marital status', 'place of birth'];

        foreach ($backKeywords as $keyword) {
            if (stripos($fullText, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }
}