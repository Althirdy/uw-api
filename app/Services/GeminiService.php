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
     * Transcribe and analyze audio content.
     *
     * @param  string  $fileContent  Raw binary content of the file
     * @param  string  $mimeType  Mime type of the file (e.g., 'audio/mp3')
     * @return array|null Returns array with 'transcription_text', 'title', 'description' or null on failure
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
                      "Return strictly valid JSON with keys: 'transcription_text', 'title', 'description'. ".
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
}
