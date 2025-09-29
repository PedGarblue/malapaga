<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ParticipationController;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('events', EventController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('participations', ParticipationController::class);
});

// Legacy user route (keeping for backward compatibility)
Route::get('/user-legacy', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
