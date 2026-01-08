<?php

use App\Http\Controllers\Api\V1\Yolo\YoloAccidentController;
use App\Http\Controllers\Operator\CCTVController;
use Illuminate\Support\Facades\Route;

// YOLO Detection Routes
Route::post('yolo/process-snapshot', [YoloAccidentController::class, 'ProcessSnapShot']);

Route::get('yolo/active-cctvs', [CCTVController::class, 'getActiveCCTVs']);

// Get CCTVs that have YOLO detection enabled (used by Python YOLO script)
// Protected with API key authentication
Route::get('yolo/enabled-cctvs', [CCTVController::class, 'getYoloEnabledCCTVs'])->middleware('api.key');