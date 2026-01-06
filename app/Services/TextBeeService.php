<?php


namespace App\Services;


class TextBeeService
{
    protected $baseUrl = 'https://api.textbee.dev/api/v1';
    protected $apiKey;
    protected $deviceId;

    public function __construct($apiKey, $deviceId)
    {
        $this->apiKey = config('services.textbee.api_key', $apiKey);
        $this->deviceId = config('services.textbee.device_id', $deviceId);
    }
    


    public function sendSms($recipients, $message)
    {
        $endpoint = '/gateway/devices/' . $this->deviceId . '/send-sms';
        
        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'x-api-key' => $this->apiKey,
        ])->post($this->baseUrl . $endpoint, [
            'recipients' => $recipients,
            'message' => $message,
        ]);

        if ($response->successful()) {
            return $response->json();
        } else {
            \Illuminate\Support\Facades\Log::error('TextBee Send SMS Error: ' . $response->body());
            return null;
        }
    }

    public function sendOtp($phoneNumber, $otpCode)
    {
        $message = "Your UrbanWatch verification code is: {$otpCode}. Valid for 1 minute. Do not share this code.";
        
        $recipients = is_array($phoneNumber) ? $phoneNumber : [$phoneNumber];
        
        return $this->sendSms($recipients, $message);
    }

    public function sendTestMessage($phoneNumber, $testMessage = null)
    {
        $message = $testMessage ?? "This is a test message from UrbanWatch. Your TextBee integration is working!";
        
        $recipients = is_array($phoneNumber) ? $phoneNumber : [$phoneNumber];
        
        return $this->sendSms($recipients, $message);
    }
}
