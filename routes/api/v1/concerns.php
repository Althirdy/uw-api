<?php

use App\Http\Controllers\Api\V1\Citizen\ConcernController;
use Illuminate\Support\Facades\Route;

// Citizen Concern Management Routes
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    Route::apiResource('concerns', ConcernController::class);
});
