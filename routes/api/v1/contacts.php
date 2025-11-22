<?php

use App\Http\Controllers\Api\V1\Operator\ContactController;
use Illuminate\Support\Facades\Route;

// Contact Routes
Route::middleware(['auth:sanctum', 'ability.access'])->group(function () {
    // Get contacts for heatmap (public access)
    Route::get('contacts/heatmap', [ContactController::class, 'heatmap']);
    
    // CRUD operations for contacts
    Route::apiResource('contacts', ContactController::class);
});
