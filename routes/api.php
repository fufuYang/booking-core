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
});

// 我們的預約系統 API
Route::get('/services', [BookingController::class, 'getServices']);
Route::get('/slots', [BookingController::class, 'getAvailableSlots']);

// 建立預約需要登入：user_id 一律取自 token，不接受前端傳入。
Route::post('/appointments', [BookingController::class, 'store'])
    ->middleware('auth:sanctum');
