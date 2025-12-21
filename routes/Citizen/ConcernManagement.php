<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('citizen/manual-concerns', \App\Http\Controllers\Api\Citizen\Concern\ManualConcernController::class);
});
