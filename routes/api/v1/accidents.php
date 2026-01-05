<?php

use App\Http\Controllers\Api\V1\Operator\AccidentController;
use Illuminate\Support\Facades\Route;

// Accident/Report Routes
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    Route::prefix('accidents')->group(function () {
        Route::get('/', [AccidentController::class, 'index']);
        Route::get('/{accident}', [AccidentController::class, 'show']);
        Route::patch('/{accident}/status', [AccidentController::class, 'updateStatus']);
    });
});
