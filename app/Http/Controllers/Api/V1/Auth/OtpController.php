<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Jobs\SendOtpJob;
use App\Models\Otp;
use App\Models\User;
use App\Services\MailService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;

class OtpController extends Controller
{
    protected MailService $mailService;

    public function __construct(MailService $mailService)
    {
        $this->mailService = $mailService;
    }
    
    public function requestOtp(Request $request): JsonResponse
    {
        $request->validate([
            'phone' => 'required|numeric|digits:11', // e.g., 09171234567
        ]);

        $phone = $request->phone;

        if(Cache::has('otp_lock_'.$phone)) {
           return response()->json(['error' => 'Please wait 60 seconds before requesting again.'], 429);
        }

        $code = rand(100000, 999999);
        Cache::put('otp_' . $phone, $code, 300); // OTP valid for 5 minutes
        Cache::put('otp_lock_' . $phone, true, 60);

        SendOtpJob::dispatch($phone, $code);
        
        return response()->json([
            'message' => 'OTP sent successfully!',
            // 'debug' => $code // remove this line in production!
        ]);
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
