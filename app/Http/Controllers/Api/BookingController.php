<?php

namespace App\Http\Controllers\Api;

use App\Http\Requests\StoreAppointmentRequest;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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

    /**
     * 建立一筆預約。
     *
     * 併發防護分成兩層：
     *   1. transaction 內以 lockForUpdate 鎖住該時段，讓同時進來的請求排隊檢查 is_booked。
     *   2. appointments.available_slot_id 的 unique constraint 作為最後防線，
     *      即使鎖失效（例如換成不支援列鎖的引擎）也不會寫出兩筆重複預約。
     */
    public function store(StoreAppointmentRequest $request): JsonResponse
    {
        try {
            $appointment = DB::transaction(function () use ($request) {
                $slot = AvailableSlot::whereKey($request->integer('available_slot_id'))
                    ->lockForUpdate()
                    ->first();

                if ($slot === null || $slot->is_booked) {
                    return null;
                }

                $appointment = Appointment::create([
                    'user_id' => $request->user()->id,
                    'service_id' => $request->integer('service_id'),
                    'available_slot_id' => $slot->id,
                    'status' => 'pending',
                    'meta_data' => $request->input('meta_data'),
                ]);

                $slot->update(['is_booked' => true]);

                return $appointment;
            });
        } catch (QueryException $e) {
            // 撞到 unique constraint：代表另一個請求剛剛搶先訂走了。
            if ($this->isUniqueViolation($e)) {
                return $this->slotTakenResponse();
            }

            throw $e;
        }

        if ($appointment === null) {
            return $this->slotTakenResponse();
        }

        return response()->json(
            $appointment->load(['service', 'availableSlot']),
            JsonResponse::HTTP_CREATED,
        );
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }

    private function slotTakenResponse(): JsonResponse
    {
        return response()->json(
            ['message' => '這個時段已經被預約了，請重新選擇。'],
            JsonResponse::HTTP_CONFLICT,
        );
    }
}
