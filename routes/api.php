<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TurfController;
use App\Http\Controllers\Api\AvailabilityController;

Route::prefix('v1')->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('request-otp', [AuthController::class, 'requestOtp'])->middleware('throttle:5,1');
        Route::post('verify-otp', [AuthController::class, 'verifyOtp'])->middleware('throttle:10,1');
    });

    Route::get('areas', [TurfController::class, 'areas']);
    Route::get('turfs', [TurfController::class, 'index']);
    Route::get('turfs/{slug}', [TurfController::class, 'show']);
    Route::get('pitches/{id}/availability', [AvailabilityController::class, 'show']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
    });
});