<?php

use App\Http\Controllers\Api\BookingController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route; // 記得引入 Controller

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// 我們的預約系統 API
Route::get('/services', [BookingController::class, 'getServices']);
Route::get('/slots', [BookingController::class, 'getAvailableSlots']);
