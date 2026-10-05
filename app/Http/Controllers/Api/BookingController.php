<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAppointmentRequest;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;

/**
 * 只負責 HTTP 進出：接收請求、交給 Service 層、把結果包成 JSON。
 * 業務規則在 App\Services\BookingService，資料存取在 App\Repositories。
 */
class BookingController
{
    public function __construct(
        private readonly BookingService $booking,
    ) {}

    // 取得所有服務項目
    public function getServices(): JsonResponse
    {
        return response()->json($this->booking->listServices());
    }

    // 取得未來可預約的時段
    public function getAvailableSlots(): JsonResponse
    {
        return response()->json($this->booking->listAvailableSlots());
    }

    // 建立一筆預約；時段被搶走時 Service 會拋出例外並自行轉成 409
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        $appointment = $this->booking->book(
            user: $request->user(),
            serviceId: $request->integer('service_id'),
            slotId: $request->integer('available_slot_id'),
            metaData: $request->input('meta_data'),
        );

        return response()->json($appointment, JsonResponse::HTTP_CREATED);
    }
}
