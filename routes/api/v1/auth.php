<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\Auth\OtpController;
use Illuminate\Support\Facades\Route;

//========OTP Routes========//
Route::prefix('otp')->group(function () {
    Route::post('/send', [OtpController::class, 'send']);
    Route::post('/verify', [OtpController::class, 'verify']);
    Route::post('/resend', [OtpController::class, 'resend']);
    Route::post('/check', [OtpController::class, 'check']);
});

//========Auth Routes========//
Route::post('/register', [AuthController::class, 'register']);
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
