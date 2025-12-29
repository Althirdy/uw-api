<?php

use App\Http\Controllers\Operator\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->group(function () {
    Route::get('users', [UserController::class, 'index'])->name('users');
    
    // Suspension routes - MUST be before resource routes to avoid conflicts
    Route::get('user/{user}/available-punishments', [UserController::class, 'getAvailablePunishments'])->name('user.available-punishments');
    Route::post('user/{user}/suspend', [UserController::class, 'applySuspension'])->name('user.suspend');
    Route::patch('user/{user}/revoke-suspension', [UserController::class, 'revokeSuspension'])->name('user.revoke-suspension');
    
    Route::patch('user/{user}/archive', [UserController::class, 'archive'])->name('user.archive');
    Route::resource('user', UserController::class);
});