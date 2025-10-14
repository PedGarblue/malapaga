<?php

use App\Http\Controllers\Auth\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\ConsumerController;
use App\Http\Controllers\Api\EventController;
use App\Http\Controllers\Api\ItemController;
use App\Http\Controllers\Api\ParticipationController;
use App\Http\Controllers\Api\RateController;

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Public routes for rates (can be accessed without authentication)
Route::get('rates/latest', [RateController::class, 'latest']);
Route::apiResource('rates', RateController::class)->only(['index', 'show']);

// Protected routes that require authentication
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    Route::apiResource('events', EventController::class);
    Route::apiResource('consumers', ConsumerController::class);
    Route::apiResource('items', ItemController::class);
    Route::apiResource('participations', ParticipationController::class);
    Route::apiResource('rates', RateController::class)->except(['index', 'show']);
});

// Legacy user route (keeping for backward compatibility)
Route::get('/user-legacy', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
