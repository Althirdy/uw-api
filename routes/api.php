<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\Api\Auth\OtpController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

//========OTP Routes========//
Route::prefix('otp')->group(function () {
    Route::post('/send', [OtpController::class, 'send']);
    Route::post('/verify', [OtpController::class, 'verify']);
    Route::post('/resend', [OtpController::class, 'resend']);
    Route::post('/check', [OtpController::class, 'check']);
});

Route::post('/login', [AuthController::class, 'login']);

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


Route::get('health', function () {
    return response()->json(['status' => 'OK'], 200);
});


Route::post('yolo/accidents/snapshot', [App\Http\Controllers\Api\Yolo\YoloAccidentController::class, 'ProcessSnapShot']);

//========HeatmapContacts========//

Route::get('contacts/heatmap', [App\Http\Controllers\Operator\ContactController::class, 'heatMapContacts']);


require __DIR__ . '/Citizen/ConcernManagement.php';
