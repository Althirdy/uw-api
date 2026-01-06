<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use App\Http\Controllers\Api\V1\Auth\PasswordResetController;
use Illuminate\Support\Facades\Route;

// ========OTP Routes (SMS-based)========//
Route::prefix('otp')->group(function () {
    Route::post('/request', [OtpController::class, 'requestOtp']);
    Route::post('/verify', [OtpController::class, 'verifyOtp']);
    Route::post('/resend', [OtpController::class, 'resendOtp']);
});

// ========Auth Routes========//
Route::post('/password/reset', [PasswordResetController::class, 'reset']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/ocr-front-id', [AuthController::class, 'scanFrontId']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/login/purok-leader', [AuthController::class, 'loginPurokLeader']);
Route::post('/verify-registration-info', [AuthController::class, 'verifyRegistrationInfo']);

// Logout requires access token
Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware(['auth:sanctum', 'ability.access']);

// Refresh token endpoint - only accepts refresh tokens
Route::post('/refresh-token', [AuthController::class, 'refreshToken'])
    ->middleware('auth:sanctum');

// Protected routes - require access token
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::get('user', [AuthController::class, 'user']);
    });
});
