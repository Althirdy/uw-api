<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Services\AbstractApiService;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected MailService $mailService;

    protected AbstractApiService $abstractApiService;

    public function __construct(MailService $mailService, AbstractApiService $abstractApiService)
    {
        $this->mailService = $mailService;
        $this->abstractApiService = $abstractApiService;
    }

    /**
     * Send OTP to email
     */
    public function send(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required|in:registration,forgot_password,email_verification',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $email = $request->email;
        $type = $request->type;
        $name = $request->name ?? 'User';

        // Validate email using AbstractAPI (only for registration)
        if ($type === 'registration') {
            $emailValidation = $this->abstractApiService->validateEmail($email);

            // Check if email is disposable
            if ($emailValidation['disposable'] ?? false) {
                Log::info('Disposable email rejected', ['email' => $email]);

                return response()->json([
                    'success' => false,
                    'message' => 'Disposable or temporary email addresses are not allowed. Please use a valid email address.',
                ], 400);
            }

            // Check if email is deliverable (only if not bypassed due to API error)
            if (! ($emailValidation['bypass'] ?? false) && ! ($emailValidation['deliverable'] ?? true)) {
                Log::info('Undeliverable email rejected', ['email' => $email]);

                $response = [
                    'success' => false,
                    'message' => 'This email address cannot receive messages. Please check and try again.',
                ];

                // Include typo suggestion if available
                if (! empty($emailValidation['suggestion'])) {
                    $response['suggestion'] = 'Did you mean: '.$emailValidation['suggestion'].'?';
                }

                return response()->json($response, 400);
            }

            // Include typo suggestion even if email is valid but has a common typo
            $typeSuggestion = null;
            if (! empty($emailValidation['suggestion'])) {
                $typeSuggestion = $emailValidation['suggestion'];
            }
        }

        // Rate limiting: 3 attempts per email per 10 minutes
        $key = 'otp:send:'.$email;
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $seconds = RateLimiter::availableIn($key);

            return response()->json([
                'success' => false,
                'message' => 'Too many OTP requests. Please try again in '.ceil($seconds / 60).' minutes.',
            ], 429);
        }

        // Additional validation based on type
        if ($type === 'registration') {
            // Check if user already exists
            if (User::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email already registered',
                ], 400);
            }
        } elseif ($type === 'forgot_password') {
            // Check if user exists
            if (! User::where('email', $email)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Email not found',
                ], 404);
            }
        }

        // Delete old OTPs for this email and type
        Otp::where('email', $email)
            ->where('type', $type)
            ->delete();

        // Generate 6-digit OTP
        $otpCode = str_pad((string) random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

        // Create OTP record
        $otp = Otp::create([
            'email' => $email,
            'otp' => $otpCode,
            'type' => $type,
            'expires_at' => Carbon::now()->addMinutes(10),
        ]);

        // Send email
        $emailSent = $this->mailService->sendOtpEmail($email, $otpCode, $name);

        if (! $emailSent) {
            // Delete OTP if email fails
            $otp->delete();

            return response()->json([
                'success' => false,
                'message' => 'Failed to send OTP email. Please try again.',
            ], 500);
        }

        RateLimiter::hit($key, 600); // 10 minutes

        $responseData = [
            'email' => $email,
            'expires_in' => 10, // minutes
        ];

        // Include typo suggestion in response if available
        if (isset($typeSuggestion)) {
            $responseData['email_suggestion'] = $typeSuggestion;
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => $responseData,
        ]);
    }

    /**
     * Verify OTP
     */
    public function verify(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'otp' => 'required|string|size:6',
            'type' => 'required|in:registration,forgot_password,email_verification',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Rate limiting: 5 attempts per email per 10 minutes
        $key = 'otp:verify:'.$request->email;
        if (RateLimiter::tooManyAttempts($key, 5)) {
            return response()->json([
                'success' => false,
                'message' => 'Too many verification attempts. Please request a new OTP.',
            ], 429);
        }

        // Find valid OTP
        $otp = Otp::forEmail($request->email)
            ->ofType($request->type)
            ->valid()
            ->latest()
            ->first();

        if (! $otp) {
            RateLimiter::hit($key, 600);

            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP',
            ], 400);
        }

        // Verify OTP code
        if ($otp->otp !== $request->otp) {
            RateLimiter::hit($key, 600);

            return response()->json([
                'success' => false,
                'message' => 'Incorrect OTP',
            ], 400);
        }

        // Mark as verified
        $otp->markAsVerified();

        // Clear rate limiting on successful verification
        RateLimiter::clear($key);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => [
                'email' => $request->email,
                'type' => $request->type,
            ],
        ]);
    }

    /**
     * Resend OTP
     */
    public function resend(Request $request): JsonResponse
    {
        // Resend uses the same logic as send
        return $this->send($request);
    }

    /**
     * Check if OTP is still valid
     */
    public function check(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'type' => 'required|in:registration,forgot_password,email_verification',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $otp = Otp::forEmail($request->email)
            ->ofType($request->type)
            ->valid()
            ->latest()
            ->first();

        if (! $otp) {
            return response()->json([
                'success' => false,
                'message' => 'No valid OTP found',
                'has_valid_otp' => false,
            ]);
        }

        return response()->json([
            'success' => true,
            'has_valid_otp' => true,
            'expires_at' => $otp->expires_at->toISOString(),
            'expires_in_seconds' => Carbon::now()->diffInSeconds($otp->expires_at, false),
        ]);
    }
}
