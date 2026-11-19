<?php

use App\Http\Controllers\Api\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TurfController;
use App\Http\Controllers\Api\AvailabilityController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\OwnerController;

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
        Route::post('bookings', [BookingController::class, 'store']);
        Route::get('bookings', [BookingController::class, 'index']);
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::post('bookings/{reference}/pay-test', [PaymentController::class, 'payTest']);
        Route::post('bookings/{reference}/cancel', [PaymentController::class, 'cancel']);
            
            Route::middleware('role:turf')->prefix('turf')->group(function () {
            Route::get('turfs', [OwnerController::class, 'turfs']);
            Route::get('bookings', [OwnerController::class, 'bookings']);
            Route::post('bookings', [OwnerController::class, 'storeManual']);
            Route::post('bookings/{reference}/cancel', [OwnerController::class, 'cancel']);
        });
    });
});