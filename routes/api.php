<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BookingController;
use Illuminate\Support\Facades\Route; // 記得引入 Controller

// 帳號：throttle 的額度定義在 AppServiceProvider 的具名限流器。
Route::post('/register', [AuthController::class, 'register'])->middleware('throttle:register');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:login');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // 預約管理：user_id 一律取自 token，只能查看/操作自己的預約
    Route::get('/appointments', [BookingController::class, 'index']);
    Route::post('/appointments', [BookingController::class, 'store']);
    Route::get('/appointments/{appointment}', [BookingController::class, 'show']);
    Route::post('/appointments/{appointment}/cancel', [BookingController::class, 'cancel']);
});

// 我們的預約系統公開 API
Route::get('/services', [BookingController::class, 'getServices']);
Route::get('/slots', [BookingController::class, 'getAvailableSlots']);
