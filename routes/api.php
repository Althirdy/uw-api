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


Route::post('yolo/accidents', [App\Http\Controllers\Api\Yolo\YoloAccidentController::class, 'ProcessSnapShot']);

//========HeatmapContacts========//

Route::get('contacts/heatmap', [App\Http\Controllers\Operator\ContactController::class, 'heatMapContacts']);



require __DIR__ . '/Citizen/ConcernManagement.php';
