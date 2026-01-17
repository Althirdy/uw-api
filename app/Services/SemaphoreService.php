<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SemaphoreService
{
    protected $baseUrl = 'https://api.semaphore.co/api/v4';

    protected $apiKey;

    protected $senderName;

    public function __construct($apiKey, $senderName)
    {
        $this->apiKey = config('services.semaphore.api_key', $apiKey);
        $this->senderName = config('services.semaphore.sender_name', $senderName);
    }

    /**
     * METHOD 1: Send a Plain Message
     * Use this for: Marketing, Reminders, Announcements.
     * Rate Limit: ~120 per minute.
     */
    public function send($phoneNumber, $message)
    {
        return $this->executeRequest('/messages', [
            'apikey' => $this->apiKey,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => $this->senderName,
        ]);
    }

    /**
     * METHOD 2: Send an OTP (One-Time Password)
     * Use this for: Login codes, Verifications.
     * Rate Limit: NONE (Unlimited priority).
     *
     * * @param string $code (Optional) If null, Semaphore generates a 6-digit code for you.
     */
    public function sendOtp($phoneNumber, $message, $code = null)
    {
        $params = [
            'apikey' => $this->apiKey,
            'number' => $phoneNumber,
            'message' => $message,
            'sendername' => $this->senderName,
        ];
        if ($code) {
            $params['code'] = $code;
        }

        return $this->executeRequest('/otp', $params);
    }

    /**
     * Helper Function to keep code DRY (Don't Repeat Yourself)
     */
    protected function executeRequest($endpoint, $params)
    {
        try {
            $response = Http::post($this->baseUrl.$endpoint, $params);
            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json(),
                ];
            }

            // Log technical failures (404, 500, etc)
            Log::error("Semaphore API Error [{$endpoint}]: ".$response->body());

            return [
                'success' => false,
                'error' => $response->body(),
            ];
        } catch (\Exception $e) {
            Log::error('Semaphore Connection Error: '.$e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
