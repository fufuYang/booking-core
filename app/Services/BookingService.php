<?php

namespace App\Services;

use App\Exceptions\AppointmentAlreadyCancelledException;
use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Service;
use App\Models\User;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\AvailableSlotRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class BookingService
{
    public function __construct(
        private readonly ServiceRepositoryInterface $services,
        private readonly AvailableSlotRepositoryInterface $slots,
        private readonly AppointmentRepositoryInterface $appointments,
    ) {}

    /**
     * @return Collection<int, Appointment>
     */
    public function listUserAppointments(User $user): Collection
    {
        return $this->appointments->forUser($user->id);
    }

    /**
     * @return Collection<int, Service>
     */
    public function listServices(): Collection
    {
        return $this->services->all();
    }

    /**
     * @return Collection<int, AvailableSlot>
     */
    public function listAvailableSlots(): Collection
    {
        return $this->slots->availableFrom(now()->toDateString());
    }

    /**
     * 建立一筆預約。
     *
     * 併發防護分成兩層：
     *   1. transaction 內以列鎖鎖住該時段，讓同時進來的請求排隊檢查 is_booked。
     *   2. appointments.available_slot_id 的 unique constraint 作為最後防線，
     *      即使鎖失效（例如換成不支援列鎖的引擎）也不會寫出兩筆重複預約。
     *
     * @param  array<string, mixed>|null  $metaData
     *
     * @throws SlotAlreadyBookedException
     */
    public function book(User $user, int $serviceId, int $slotId, ?array $metaData = null): Appointment
    {
        try {
            $appointment = DB::transaction(function () use ($user, $serviceId, $slotId, $metaData) {
                $slot = $this->slots->findForUpdate($slotId);

                if ($slot === null || $slot->is_booked) {
                    return null;
                }

                $appointment = $this->appointments->create([
                    'user_id' => $user->id,
                    'service_id' => $serviceId,
                    'available_slot_id' => $slot->id,
                    'status' => 'pending',
                    'meta_data' => $metaData,
                ]);

                $this->slots->markAsBooked($slot);

                return $appointment;
            });
        } catch (QueryException $e) {
            // 撞到 unique constraint：代表另一個請求剛剛搶先訂走了。
            if ($this->isUniqueViolation($e)) {
                throw new SlotAlreadyBookedException;
            }

            throw $e;
        }

        if ($appointment === null) {
            throw new SlotAlreadyBookedException;
        }

        return $appointment->load(['service', 'availableSlot']);
    }

    /**
     * 取消預約並釋放時段。
     *
     * @throws AppointmentAlreadyCancelledException
     */
    public function cancel(Appointment $appointment): Appointment
    {
        if ($appointment->status === 'cancelled') {
            throw new AppointmentAlreadyCancelledException;
        }

        return DB::transaction(function () use ($appointment) {
            $slot = $this->slots->findForUpdate($appointment->available_slot_id);

            $this->appointments->markAsCancelled($appointment);

            if ($slot !== null) {
                $this->slots->release($slot);
            }

            $appointment->refresh();

            return $appointment->load(['service', 'availableSlot']);
        });
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return $e->getCode() === '23000';
    }
}
