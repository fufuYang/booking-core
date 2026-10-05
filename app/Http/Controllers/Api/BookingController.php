<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Services\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

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

    // 取得當前使用者的預約清單
    public function index(Request $request): JsonResponse
    {
        return response()->json($this->booking->listUserAppointments($request->user()));
    }

    // 取得單筆預約詳情（僅限本人查看）
    public function show(Appointment $appointment): JsonResponse
    {
        Gate::authorize('view', $appointment);

        return response()->json($appointment->load(['service', 'availableSlot']));
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

    // 取消預約並釋出時段（僅限本人取消）
    public function cancel(Appointment $appointment): JsonResponse
    {
        Gate::authorize('cancel', $appointment);

        $cancelled = $this->booking->cancel($appointment);

        return response()->json($cancelled);
    }
}
