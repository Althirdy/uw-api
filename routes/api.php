<?php

use Illuminate\Support\Facades\Route;

/**
 * API Version 1 Routes
 * Base URL: /api/v1
 */
Route::prefix('v1')->group(function () {
    // Auth & OTP routes
    require __DIR__.'/api/v1/auth.php';

    // Citizen concern management routes
    require __DIR__.'/api/v1/concerns.php';

    // Contact management routes
    require __DIR__.'/api/v1/contacts.php';

    // Accident/Report routes
    require __DIR__.'/api/v1/accidents.php';

    // YOLO detection routes
    require __DIR__.'/api/v1/yolo.php';

    Route::post('/ocr/national-id', [App\Http\Controllers\Api\V1\Auth\IdVerificationController::class, 'scanIdFront']);

});



/**
 * Legacy Routes (for backward compatibility)
 * These will be deprecated in future versions
 *
 * @deprecated Use /api/v1 endpoints instead
 */
Route::prefix('legacy')->group(function () {
    // Keep old OTP routes
    Route::prefix('otp')->group(function () {
        Route::post('/send', [App\Http\Controllers\Api\Auth\OtpController::class, 'send']);
        Route::post('/verify', [App\Http\Controllers\Api\Auth\OtpController::class, 'verify']);
        Route::post('/resend', [App\Http\Controllers\Api\Auth\OtpController::class, 'resend']);
        Route::post('/check', [App\Http\Controllers\Api\Auth\OtpController::class, 'check']);
    });

    // Keep old auth routes
    Route::post('/register', [App\Http\Controllers\Api\Auth\AuthController::class, 'register']);
    Route::post('/login', [App\Http\Controllers\Api\Auth\AuthController::class, 'login']);
    Route::post('/login/purok-leader', [App\Http\Controllers\Api\Auth\AuthController::class, 'loginPurokLeader']);
    Route::post('/verify-registration-info', [App\Http\Controllers\Api\Auth\AuthController::class, 'verifyRegistrationInfo']);
    Route::post('/logout', [App\Http\Controllers\Api\Auth\AuthController::class, 'logout'])
        ->middleware(['auth:sanctum', 'ability.access']);
    Route::post('/refresh-token', [App\Http\Controllers\Api\Auth\AuthController::class, 'refreshToken'])
        ->middleware('auth:sanctum');

    Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
        Route::prefix('auth')->group(function () {
            Route::get('user', [App\Http\Controllers\Api\Auth\AuthController::class, 'user']);
        });
    });

    // Keep old YOLO route
    Route::post('yolo/accidents/snapshot', [App\Http\Controllers\Api\Yolo\YoloAccidentController::class, 'ProcessSnapShot']);

    // Keep old heatmap route
    Route::get('contacts/heatmap', [App\Http\Controllers\Operator\ContactController::class, 'heatMapContacts']);

    // // Keep old citizen concern routes
    // Route::middleware(['auth:sanctum'])->group(function () {
    //     Route::apiResource('citizen/manual-concerns', \App\Http\Controllers\Api\Citizen\Concern\ManualConcernController::class);
    // });

});

/**
 * Health Check Endpoint
 */
Route::get('health', function () {
    return response()->json([
        'status' => 'OK',
        'version' => 'v1',
        'timestamp' => now()->toISOString(),
    ], 200);
});
