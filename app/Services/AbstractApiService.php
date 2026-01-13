<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AbstractApiService
{
    protected $apiKey;

    protected $baseUrl = 'https://emailvalidation.abstractapi.com/v1/';

    protected $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.abstract_api.api_key');
        $this->enabled = config('services.abstract_api.enabled', true);
    }

    /**
     * Validate an email address using AbstractAPI Email Validation.
     *
     * @param  string  $email  The email address to validate
     * @return array Returns validation result with keys:
     *               - valid: bool (overall validity)
     *               - deliverable: bool (can receive emails)
     *               - disposable: bool (is temporary/disposable email)
     *               - quality_score: float (0-1 quality score)
     *               - suggestion: string|null (typo suggestion if detected)
     *               - bypass: bool (true if validation was skipped due to error)
     *               - error: string|null (error message if bypass is true)
     */
    public function validateEmail(string $email): array
    {
        // If service is disabled, bypass validation
        if (! $this->enabled) {
            Log::info('AbstractAPI email validation disabled, bypassing', ['email' => $email]);

            return [
                'valid' => true,
                'deliverable' => true,
                'disposable' => false,
                'quality_score' => 1.0,
                'suggestion' => null,
                'bypass' => true,
                'error' => null,
            ];
        }

        if (! $this->apiKey) {
            Log::warning('AbstractAPI key is missing, bypassing validation', ['email' => $email]);

            return [
                'valid' => true,
                'deliverable' => true,
                'disposable' => false,
                'quality_score' => 1.0,
                'suggestion' => null,
                'bypass' => true,
                'error' => 'API key not configured',
            ];
        }

        try {
            $response = Http::timeout(10)->get($this->baseUrl, [
                'api_key' => $this->apiKey,
                'email' => $email,
            ]);

            if ($response->failed()) {
                Log::warning('AbstractAPI request failed, bypassing validation', [
                    'email' => $email,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [
                    'valid' => true,
                    'deliverable' => true,
                    'disposable' => false,
                    'quality_score' => 1.0,
                    'suggestion' => null,
                    'bypass' => true,
                    'error' => 'API request failed with status '.$response->status(),
                ];
            }

            $data = $response->json();

            // Parse the response
            $deliverability = $data['deliverability'] ?? 'UNKNOWN';
            $isDeliverable = $deliverability === 'DELIVERABLE';

            // Check for disposable email (from is_disposable_email field)
            $isDisposable = ($data['is_disposable_email']['value'] ?? false) === true;

            // Get quality score if available
            $qualityScore = (float) ($data['quality_score'] ?? 0.5);

            // Check for autocorrect suggestion
            $suggestion = $data['autocorrect'] ?? null;
            if ($suggestion === '' || $suggestion === $email) {
                $suggestion = null;
            }

            // Determine overall validity
            $isValid = $isDeliverable && ! $isDisposable && ($data['is_valid_format']['value'] ?? true);

            Log::info('Email validated via AbstractAPI', [
                'email' => $email,
                'valid' => $isValid,
                'deliverable' => $isDeliverable,
                'disposable' => $isDisposable,
                'quality_score' => $qualityScore,
                'suggestion' => $suggestion,
            ]);

            return [
                'valid' => $isValid,
                'deliverable' => $isDeliverable,
                'disposable' => $isDisposable,
                'quality_score' => $qualityScore,
                'suggestion' => $suggestion,
                'bypass' => false,
                'error' => null,
            ];

        } catch (\Exception $e) {
            Log::warning('AbstractAPI exception, bypassing validation', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return [
                'valid' => true,
                'deliverable' => true,
                'disposable' => false,
                'quality_score' => 1.0,
                'suggestion' => null,
                'bypass' => true,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Check if the service is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled && ! empty($this->apiKey);
    }
}
