<?php

namespace App\Http\Controllers\Api;

use App\Models\AvailableSlot;
use App\Models\Service;

// 移除 extends Controller，讓它變成一個乾淨的純 PHP 類別
class BookingController
{
    // 取得所有服務項目
    public function getServices()
    {
        $services = Service::all();

        return response()->json($services);
    }

    // 取得未來可預約的時段
    public function getAvailableSlots()
    {
        $slots = AvailableSlot::where('is_booked', false)
            ->where('date', '>=', now()->toDateString())
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();

        return response()->json($slots);
    }
}
