<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\Otp;
use App\Models\User;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }

    /**
     * Send OTP
     * 
     * Send a One-Time Password (OTP) to the specified email address. OTPs are valid for 10 minutes.
     * Rate limited to 3 requests per email per 10 minutes.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The email address to send OTP to. Example: john.doe@example.com
     * @bodyParam type string required Type of OTP: registration, forgot_password, email_verification. Example: registration
     * @bodyParam name string optional Name of the recipient for email personalization. Example: John Doe
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "OTP sent successfully",
     *   "data": {
     *     "email": "john.doe@example.com",
     *     "expires_in": 10
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Email already registered"
     * }
     * 
     * @response 404 {
     *   "success": false,
     *   "message": "Email not found"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "email": ["The email field is required."]
     *   }
     * }
     * 
     * @response 429 {
     *   "success": false,
     *   "message": "Too many OTP requests. Please try again in 10 minutes."
     * }
     * 
     * @response 500 {
     *   "success": false,
     *   "message": "Failed to send OTP email. Please try again."
     * }
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

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'email' => $email,
                'expires_in' => 10, // minutes
            ],
        ]);
    }

    /**
     * Verify OTP
     * 
     * Verify the OTP code sent to the email. For forgot_password type, returns a reset token.
     * Rate limited to 5 verification attempts per email per 10 minutes.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The email address the OTP was sent to. Example: john.doe@example.com
     * @bodyParam otp string required The 6-digit OTP code received via email. Example: 123456
     * @bodyParam type string required Type of OTP: registration, forgot_password, email_verification. Example: registration
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "OTP verified successfully",
     *   "data": {
     *     "email": "john.doe@example.com",
     *     "type": "forgot_password",
     *     "token": "abcdefghijklmnopqrstuvwxyz1234567890"
     *   }
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Invalid or expired OTP"
     * }
     * 
     * @response 400 {
     *   "success": false,
     *   "message": "Incorrect OTP"
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {
     *     "otp": ["The otp field is required."]
     *   }
     * }
     * 
     * @response 429 {
     *   "success": false,
     *   "message": "Too many verification attempts. Please request a new OTP."
     * }
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

        $responseData = [
            'email' => $request->email,
            'type' => $request->type,
        ];

        // If forgot password, generate and return a reset token
        if ($request->type === 'forgot_password') {
            $token = \Illuminate\Support\Str::random(60);

            \Illuminate\Support\Facades\DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $request->email],
                [
                    'token' => \Illuminate\Support\Facades\Hash::make($token),
                    'created_at' => now(),
                ]
            );

            $responseData['token'] = $token;
        }

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully',
            'data' => $responseData,
        ]);
    }

    /**
     * Resend OTP
     * 
     * Resend the OTP to the specified email. Uses the same logic and rate limiting as the send endpoint.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The email address to resend OTP to. Example: john.doe@example.com
     * @bodyParam type string required Type of OTP: registration, forgot_password, email_verification. Example: registration
     * @bodyParam name string optional Name of the recipient for email personalization. Example: John Doe
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "OTP sent successfully",
     *   "data": {
     *     "email": "john.doe@example.com",
     *     "expires_in": 10
     *   }
     * }
     * 
     * @response 429 {
     *   "success": false,
     *   "message": "Too many OTP requests. Please try again in 10 minutes."
     * }
     */
    public function resend(Request $request): JsonResponse
    {
        // Resend uses the same logic as send
        return $this->send($request);
    }

    /**
     * Check OTP Status
     * 
     * Check if a valid OTP exists for the given email and type, and when it expires.
     *
     * @group Auth
     * @unauthenticated
     * 
     * @bodyParam email string required The email address to check. Example: john.doe@example.com
     * @bodyParam type string required Type of OTP: registration, forgot_password, email_verification. Example: registration
     * 
     * @response 200 {
     *   "success": true,
     *   "has_valid_otp": true,
     *   "expires_at": "2023-12-27T10:30:00.000000Z",
     *   "expires_in_seconds": 450
     * }
     * 
     * @response 200 {
     *   "success": false,
     *   "message": "No valid OTP found",
     *   "has_valid_otp": false
     * }
     * 
     * @response 422 {
     *   "success": false,
     *   "message": "Validation failed",
     *   "errors": {}
     * }
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
