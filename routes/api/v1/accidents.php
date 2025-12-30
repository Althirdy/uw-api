<?php

use App\Http\Controllers\Api\V1\Operator\AccidentController;
use Illuminate\Support\Facades\Route;

// Public Heatmap Route (accessible by citizens without authentication)
// Returns only verified/resolved incidents with location, severity, and type
// Privacy: No images or personal details are returned
Route::get('incidents/heatmap', [AccidentController::class, 'heatmap']);

// Accident/Report Routes (protected - requires authentication)
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    Route::prefix('accidents')->group(function () {
        Route::get('/', [AccidentController::class, 'index']);
        Route::get('/{accident}', [AccidentController::class, 'show']);
        Route::patch('/{accident}/status', [AccidentController::class, 'updateStatus']);
    });
});
