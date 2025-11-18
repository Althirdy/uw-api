<?php

use App\Http\Controllers\Api\V1\Yolo\YoloAccidentController;
use Illuminate\Support\Facades\Route;

// YOLO Detection Routes
Route::post('yolo/accidents/snapshot', [YoloAccidentController::class, 'ProcessSnapShot']);
