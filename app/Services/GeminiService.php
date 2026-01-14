<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    protected $apiKey;

    protected $baseUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';

    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Transcribe and analyze audio content with category and severity detection.
     *
     * @param  string  $fileContent  Raw binary content of the file
     * @param  string  $mimeType  Mime type of the file (e.g., 'audio/mp3')
     * @return array|null Returns array with 'transcription_text', 'title', 'description', 'category', 'severity', 'confidence' or null on failure
     */
    public function analyzeAudio(string $fileContent, string $mimeType)
    {
        try {
            if (! $this->apiKey) {
                Log::error('Gemini API Key is missing.');

                return null;
            }

            $base64Data = base64_encode($fileContent);

            $prompt = 'Transcribe the following audio recording of a citizen concern (likely in Filipino or Taglish). '.
                      'Provide the transcription text verbatim. '.
                      'Generate a concise 3-5 word title in Tagalog (Filipino). '.
                      'Generate a brief 1-sentence summary description in Tagalog (Filipino). '.
                      "\n\n".
                      'Also analyze the concern to determine the category and severity:'."\n".
                      'CATEGORIES: safety, security, infrastructure, environment, noise, other'."\n".
                      'SEVERITY LEVELS: low, medium, high'."\n".
                      "\n".
                      'EXAMPLES:'."\n".
                      '- "May sunog" → category: safety, severity: high'."\n".
                      '- "Maraming basura" → category: environment, severity: medium'."\n".
                      '- "Sira ang daan" → category: infrastructure, severity: medium'."\n".
                      '- "Malakas ang ingay" → category: noise, severity: low'."\n".
                      "\n".
                      "Return strictly valid JSON with keys: 'transcription_text', 'title', 'description', 'category', 'severity', 'confidence'. ".
                      'Do not include markdown formatting (like ```json) in the response.';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Data,
                                ],
                            ],
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $responseData = $response->json();

            // Extract the text from the response
            if (! isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini API: Unexpected response format', ['response' => $responseData]);

                return null;
            }

            $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];

            // Clean up any markdown code blocks if present (just in case)
            $jsonString = preg_replace('/^```json\s*|\s*```$/', '', $jsonString);

            $result = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini API: Failed to parse JSON response', [
                    'error' => json_last_error_msg(),
                    'raw' => $jsonString,
                ]);

                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiService Exception', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Analyze concern text to determine category and severity.
     *
     * @param  string  $text  The concern text (transcript, title, or description)
     * @return array|null Returns array with 'category', 'severity', 'confidence', 'reasoning' or null on failure
     */
    public function analyzeConcernCategoryAndSeverity(string $text)
    {
        try {
            if (! $this->apiKey) {
                Log::error('Gemini API Key is missing.');

                return null;
            }

            $prompt = 'Analyze the following citizen concern text (in Filipino/Taglish or English). '.
                      'Determine the most appropriate category and severity level. '.
                      "\n\n".
                      'CATEGORIES (choose one):'."\n".
                      '- safety: Threats to personal safety, fire (sunog), accidents (aksidente), dangerous situations'."\n".
                      '- security: Crime, theft (nakawan), suspicious activity (kahina-hinala), violence'."\n".
                      '- infrastructure: Roads (daan), utilities (kuryente/tubig), broken facilities (sira), construction issues'."\n".
                      '- environment: Garbage (basura), pollution (polusyon), flooding (baha), sanitation (kalinisan)'."\n".
                      '- noise: Loud noise (ingay), disturbances, noise pollution'."\n".
                      '- other: Anything that doesn\'t fit the above categories'."\n".
                      "\n".
                      'SEVERITY LEVELS (choose one):'."\n".
                      '- high: Immediate danger, emergency, requires urgent action'."\n".
                      '- medium: Significant issue, needs attention soon'."\n".
                      '- low: Minor issue, can be addressed in normal schedule'."\n".
                      "\n".
                      'EXAMPLES:'."\n".
                      '- "May sunog sa bahay" → category: safety, severity: high'."\n".
                      '- "Maraming basura sa kalsada" → category: environment, severity: medium'."\n".
                      '- "Sira ang kalsada" → category: infrastructure, severity: medium'."\n".
                      '- "Napakalakas ng ingay ng kapitbahay" → category: noise, severity: low'."\n".
                      '- "May nakawan sa amin" → category: security, severity: high'."\n".
                      "\n".
                      'Concern Text: '.$text."\n\n".
                      'Return strictly valid JSON with keys: '.
                      '"category" (one of: safety, security, infrastructure, environment, noise, other), '.
                      '"severity" (one of: low, medium, high), '.
                      '"confidence" (decimal 0.0 to 1.0 indicating how confident you are), '.
                      '"reasoning" (brief explanation in English). '.
                      'Do not include markdown formatting.';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error (Category Analysis)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $responseData = $response->json();

            if (! isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini API: Unexpected response format (Category Analysis)', ['response' => $responseData]);

                return null;
            }

            $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];
            $jsonString = preg_replace('/^```json\s*|\s*```$/', '', $jsonString);

            $result = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini API: Failed to parse JSON response (Category Analysis)', [
                    'error' => json_last_error_msg(),
                    'raw' => $jsonString,
                ]);

                return null;
            }

            // Validate response structure
            if (! isset($result['category']) || ! isset($result['severity']) || ! isset($result['confidence'])) {
                Log::error('Gemini API: Missing required fields in response', ['result' => $result]);

                return null;
            }

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiService Exception (Category Analysis)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Analyze image content to verify emergency validity and extract incident details.
     *
     * @param  string  $fileContent  Raw binary content of the image file
     * @param  string  $mimeType  Mime type of the file (e.g., 'image/jpeg')
     * @param  array  $context  Additional context like device location, device name
     * @return array|null Returns array with 'is_valid', 'accident_type', 'severity', 'title', 'description', 'confidence', 'detected_objects', 'reasoning' or null on failure
     */
    public function analyzeImage(string $fileContent, string $mimeType, array $context = [])
    {
        try {
            if (! $this->apiKey) {
                Log::error('Gemini API Key is missing.');

                return null;
            }

            $base64Data = base64_encode($fileContent);

            // Build context string if provided
            $contextInfo = '';
            if (! empty($context)) {
                $contextInfo = "\n\nContext Information:\n";
                if (isset($context['device_name'])) {
                    $contextInfo .= "- Camera: {$context['device_name']}\n";
                }
                if (isset($context['location'])) {
                    $contextInfo .= "- Location: {$context['location']}\n";
                }
            }

            $prompt = "You are a STRICT emergency detection AI system analyzing CCTV footage. Your job is to ONLY flag REAL emergencies and REJECT anything that looks normal or is a false positive.\n\n".
                      "BE VERY CONSERVATIVE - When in doubt, mark as FALSE ALARM. It's better to miss a minor incident than to flood the system with false alarms.\n\n".
                      "Analyze the image and determine if it shows a REAL EMERGENCY that requires immediate response.\n\n".
                      "VALID EMERGENCIES (is_valid: true) - Must show CLEAR and OBVIOUS signs:\n".
                      "- Fire: ACTIVE large flames visible, heavy black smoke rising, building/vehicle clearly on fire, fire spreading\n".
                      "- Flood: Water level ABOVE normal (at least ankle-deep on roads), vehicles stuck in water, water entering buildings, people wading through flood water\n".
                      "- Accident: CLEAR vehicle collision damage visible, overturned/crashed vehicles, people lying injured on ground, debris scattered on road, emergency responders already on scene\n\n".
                      "FALSE ALARMS TO REJECT (is_valid: false):\n\n".
                      "FIRE FALSE ALARMS - REJECT these:\n".
                      "- Sunlight, sun glare, or reflections on windows/metal surfaces\n".
                      "- Fog, mist, steam, exhaust from vehicles or buildings\n".
                      "- Dust clouds from construction or vehicles\n".
                      "- Orange/red/yellow colored objects (cars, signs, clothing, banners)\n".
                      "- Sunset/sunrise lighting making things look orange\n".
                      "- Red/orange neon signs or lights\n".
                      "- Candles, lighters, cigarettes, controlled cooking fires\n".
                      "- Grilling, BBQ, or outdoor cooking\n".
                      "- Bonfires or campfires in appropriate areas\n".
                      "- Smoke from cigarettes or vaping\n".
                      "- Heat haze or shimmer from hot surfaces\n\n".
                      "FLOOD FALSE ALARMS - REJECT these:\n".
                      "- Wet roads after rain (water draining normally)\n".
                      "- Small puddles or standing water in low spots\n".
                      "- Water from car washes, sprinklers, or cleaning\n".
                      "- Street cleaning vehicles spraying water\n".
                      "- Normal rain (even heavy rain without flooding)\n".
                      "- Reflections on wet pavement\n".
                      "- Shadows that look like water\n".
                      "- Rivers/streams/canals at normal levels\n".
                      "- Drainage systems working normally\n\n".
                      "ACCIDENT FALSE ALARMS - REJECT these:\n".
                      "- Normal traffic, even if congested or slow\n".
                      "- Vehicles stopped at traffic lights or intersections\n".
                      "- Parked vehicles (even if parked badly)\n".
                      "- Motorcycles lane splitting or weaving\n".
                      "- Delivery trucks loading/unloading\n".
                      "- Construction work or road repairs\n".
                      "- People crossing streets or jaywalking\n".
                      "- People running (could be exercise or late)\n".
                      "- Kids playing, sports, or games on streets\n".
                      "- People arguing or having discussions\n".
                      "- Minor fender benders with no visible damage\n".
                      "- Vehicles pulling over to the side\n".
                      "- Emergency vehicles passing through (not stopped at scene)\n".
                      "- Tow trucks picking up normally parked vehicles\n".
                      "- Blurry or unclear images where you cannot confirm emergency\n\n".
                      "IMPORTANT RULES:\n".
                      "- If the image is blurry, dark, or unclear - mark as FALSE ALARM\n".
                      "- If you're less than 70% confident it's a real emergency - mark as FALSE ALARM\n".
                      "- Normal daily activities should NEVER be flagged as emergencies\n".
                      "- When in doubt, choose FALSE ALARM\n\n".
                      "If the image shows a REAL EMERGENCY (is_valid: true):\n".
                      "1. accident_type: Choose exactly one: 'Fire', 'Flood', or 'Accident'\n".
                      "2. severity: Choose exactly one: 'Low', 'Medium', or 'High' based on danger level\n".
                      "3. title: Generate a clear, simple 5-8 word title in conversational Filipino/Tagalog. Use everyday words, not deep or poetic language. Examples: 'Sunog sa Bahay sa Main Street', 'Aksidente ng Dalawang Sasakyan', 'Baha sa Kalsada'\n".
                      "4. description: Generate a natural 2-3 sentence description in conversational Filipino/Tagalog. Write like you're reporting to someone casually - simple, clear, easy to understand. Avoid formal or poetic words. Just describe what happened, where, and what the situation is.\n".
                      "5. confidence: Number from 70-100 indicating your confidence this is a real emergency (must be 70+ to be valid)\n".
                      "6. detected_objects: Array of key objects/elements you detected (e.g., ['flames', 'smoke', 'building'])\n".
                      "7. reasoning: Brief explanation in English of why this is a real emergency\n\n".
                      "If the image is a FALSE ALARM (is_valid: false):\n".
                      "1. Set is_valid to false\n".
                      "2. Set all other fields to null except reasoning\n".
                      "3. reasoning: Explain in English why this is NOT a real emergency (be specific about what you see)\n".
                      $contextInfo."\n".
                      "Return ONLY valid JSON with this exact structure:\n".
                      "{\n".
                      "  \"is_valid\": boolean,\n".
                      "  \"accident_type\": \"Fire|Flood|Accident\" or null,\n".
                      "  \"severity\": \"Low|Medium|High\" or null,\n".
                      "  \"title\": \"string in Tagalog\" or null,\n".
                      "  \"description\": \"string in Tagalog\" or null,\n".
                      "  \"confidence\": number (0-100) or null,\n".
                      "  \"detected_objects\": [\"array\", \"of\", \"strings\"] or null,\n".
                      "  \"reasoning\": \"explanation in English\"\n".
                      "}\n\n".
                      'Do not include markdown formatting (like ```json) in the response.';

            $response = Http::timeout(60)->withHeaders([
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}?key={$this->apiKey}", [
                'contents' => [
                    [
                        'parts' => [
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Data,
                                ],
                            ],
                            [
                                'text' => $prompt,
                            ],
                        ],
                    ],
                ],
                'generationConfig' => [
                    'response_mime_type' => 'application/json',
                    'temperature' => 0.4, // Lower temperature for more consistent/reliable responses
                ],
            ]);

            if ($response->failed()) {
                Log::error('Gemini API Error (Image Analysis)', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $responseData = $response->json();

            // Extract the text from the response
            if (! isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini API: Unexpected response format (Image Analysis)', ['response' => $responseData]);

                return null;
            }

            $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];

            // Clean up any markdown code blocks if present
            $jsonString = preg_replace('/^```json\s*|\s*```$/', '', trim($jsonString));

            $result = json_decode($jsonString, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini API: Failed to parse JSON response (Image Analysis)', [
                    'error' => json_last_error_msg(),
                    'raw' => $jsonString,
                ]);

                return null;
            }

            // Log the analysis result
            Log::info('Gemini Image Analysis Complete', [
                'is_valid' => $result['is_valid'] ?? false,
                'accident_type' => $result['accident_type'] ?? null,
                'confidence' => $result['confidence'] ?? null,
            ]);

            return $result;

        } catch (\Exception $e) {
            Log::error('GeminiService Exception (Image Analysis)', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return null;
        }
    }

    /**
     * Analyze Philippine National ID image for authenticity verification and data extraction.
     *
     * @param  string  $fileContent  Raw binary content of the image file
     * @param  string  $mimeType  Mime type of the file (e.g., 'image/jpeg')
     * @return array Returns array with 'is_authentic', 'data' containing extracted fields, or throws exception on API failure
     *
     * @throws \Exception When Gemini API fails
     */
    public function analyzeNationalId(string $fileContent, string $mimeType): array
    {
        if (! $this->apiKey) {
            Log::error('Gemini API Key is missing.');
            throw new \Exception('Gemini API configuration error');
        }

        $base64Data = base64_encode($fileContent);

        $prompt = <<<'PROMPT'
You are an expert document verification AI specialized in Philippine National ID (PhilSys ID) authentication and data extraction.

REFERENCE - AUTHENTIC PHILIPPINE NATIONAL ID CHARACTERISTICS:
1. Header: "REPUBLIKA NG PILIPINAS" at top, "Republic of the Philippines" below
2. Title: "PAMBANSANG PAGKAKAKILANLAN" with "Philippine Identification Card" subtitle
3. PhilSys Logo: Official government seal on the left side
4. PCN Number: 16-digit format XXXX-XXXX-XXXX-XXXX (e.g., 3243-7486-4027-9268)
5. Bilingual Field Labels (Filipino/English):
   - "Apelyido/Last Name"
   - "Mga Pangalan/Given Names"
   - "Gitnang Apelyido/Middle Name"
   - "Petsa ng Kapanganakan/Date of Birth"
   - "Tirahan/Address"
6. Security Features: Holographic gradient background with wavy patterns (orange/yellow/blue tones)
7. Fingerprint icon on the right side
8. "PHL" country code indicator
9. Photo area on the left with ghost image security feature

TASK: Analyze the provided image and perform TWO functions:

FUNCTION 1 - AUTHENTICITY VERIFICATION:
Determine if this is a GENUINE Philippine National ID by checking:
- Presence of official header text and government seal
- Correct bilingual field label format
- Valid PCN number format (16 digits)
- Holographic/gradient background pattern
- Proper layout and typography consistent with official PhilSys ID
- NOT a photocopy, screenshot, or digitally manipulated image

FUNCTION 2 - DATA EXTRACTION:
If authentic, extract the following fields:
- PCN Number (format: XXXX-XXXX-XXXX-XXXX)
- Last Name (Apelyido)
- Given Names (Mga Pangalan)
- Middle Name (Gitnang Apelyido)
- Date of Birth (convert to MM/DD/YYYY format)
- Address components: Street, Barangay, City, Region
- Postal Code: INFER the correct Philippine postal code based on the Barangay and City combination

IMPORTANT NOTES:
- If this is the BACK side of the ID (contains "Date of Issue", "Blood Type", "Marital Status"), mark as back_side_detected
- If image is blurry, partial, or unreadable, mark as image_quality_issue
- For postal code, use your knowledge of Philippine postal codes to match the barangay/city

Return ONLY valid JSON with this exact structure:
{
    "is_authentic": boolean,
    "back_side_detected": boolean,
    "image_quality_issue": boolean,
    "confidence": number (0-100),
    "data": {
        "pcn_number": "XXXX-XXXX-XXXX-XXXX" or null,
        "last_name": "string" or null,
        "given_name": "string" or null,
        "middle_name": "string" or null,
        "date_of_birth": "MM/DD/YYYY" or null,
        "address": "street address" or null,
        "barangay": "string" or null,
        "city": "string" or null,
        "region": "string" or null,
        "full_address": "complete address string" or null,
        "postal_code": "4-digit code" or null
    },
    "reasoning": "Brief explanation of authenticity determination"
}

Do not include markdown formatting (like ```json) in the response.
PROMPT;

        $response = Http::timeout(60)->withHeaders([
            'Content-Type' => 'application/json',
        ])->post("{$this->baseUrl}?key={$this->apiKey}", [
            'contents' => [
                [
                    'parts' => [
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data,
                            ],
                        ],
                        [
                            'text' => $prompt,
                        ],
                    ],
                ],
            ],
            'generationConfig' => [
                'response_mime_type' => 'application/json',
                'temperature' => 0.2, // Low temperature for consistent document analysis
            ],
        ]);

        if ($response->failed()) {
            Log::error('Gemini API Error (National ID Analysis)', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
            throw new \Exception('Gemini API request failed');
        }

        $responseData = $response->json();

        if (! isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            Log::error('Gemini API: Unexpected response format (National ID Analysis)', ['response' => $responseData]);
            throw new \Exception('Gemini API returned unexpected response format');
        }

        $jsonString = $responseData['candidates'][0]['content']['parts'][0]['text'];
        $jsonString = preg_replace('/^```json\s*|\s*```$/', '', trim($jsonString));

        $result = json_decode($jsonString, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error('Gemini API: Failed to parse JSON response (National ID Analysis)', [
                'error' => json_last_error_msg(),
                'raw' => $jsonString,
            ]);
            throw new \Exception('Failed to parse Gemini response');
        }

        Log::info('Gemini National ID Analysis Complete', [
            'is_authentic' => $result['is_authentic'] ?? false,
            'confidence' => $result['confidence'] ?? null,
            'has_pcn' => ! empty($result['data']['pcn_number'] ?? null),
        ]);

        return $result;
    }
}
