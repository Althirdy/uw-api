<?php

use App\Http\Controllers\Api\Auth\ApiLoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::post('/login', [ApiLoginController::class, 'login']);

// Logout requires access token
Route::post('/logout', [ApiLoginController::class, 'logout'])
    ->middleware(['auth:sanctum', 'ability.access']);

// Refresh token endpoint - only accepts refresh tokens
Route::post('/refresh-token', [ApiLoginController::class, 'refreshToken'])
    ->middleware('auth:sanctum');

// Protected routes - require access token
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('user', [ApiLoginController::class, 'user']);
    });
});


Route::get('health', function () {
    return response()->json(['status' => 'OK'], 200);
});


Route::post('yolo/accidents/snapshot', [App\Http\Controllers\Api\Yolo\YoloAccidentController::class, 'ProcessSnapShot']);

//========HeatmapContacts========//

Route::get('contacts/heatmap', [App\Http\Controllers\Operator\ContactController::class, 'heatMapContacts']);

//========Email Test (Simple)========//
Route::get('test-email', function () {
    try {
        $to = 'althirdysanger@gmail.com'; // Change this to your email
        $from = config('mail.from.address');
        $subject = 'Test Email from UrbanWatch';
        $message = 'Hello! This is a test email from UrbanWatch to verify Resend is working correctly.';

        \Illuminate\Support\Facades\Mail::raw($message, function ($mail) use ($to, $subject) {
            $mail->to($to)
                 ->subject($subject);
        });

        return response()->json([
            'success' => true,
            'message' => 'Email sent successfully',
            'details' => [
                'to' => $to,
                'from' => $from,
                'subject' => $subject
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send email',
            'error' => $e->getMessage()
        ], 500);
    }
});

//========OTP Email Test========//
// Preview OTP email in browser
Route::get('preview-otp', function () {
    return new \App\Mail\OtpMail('123456', 'John Doe');
});

// Send OTP email
Route::get('test-otp', function () {
    try {
        $to = 'althirdysanger@gmail.com'; // Change this to your email
        $otp = rand(100000, 999999); // Generate random 6-digit OTP
        
        \Illuminate\Support\Facades\Mail::to($to)->send(
            new \App\Mail\OtpMail((string)$otp, 'Test User')
        );

        return response()->json([
            'success' => true,
            'message' => 'OTP email sent successfully',
            'details' => [
                'to' => $to,
                'otp' => $otp
            ]
        ]);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Failed to send OTP email',
            'error' => $e->getMessage()
        ], 500);
    }
});

require __DIR__ . '/Citizen/ConcernManagement.php';
