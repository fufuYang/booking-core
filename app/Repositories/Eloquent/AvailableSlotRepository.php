<?php

namespace App\Repositories\Eloquent;

use App\Models\AvailableSlot;
use App\Repositories\Contracts\AvailableSlotRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AvailableSlotRepository implements AvailableSlotRepositoryInterface
{
    /**
     * @return Collection<int, AvailableSlot>
     */
    public function availableFrom(string $date): Collection
    {
        return AvailableSlot::where('is_booked', false)
            ->where('date', '>=', $date)
            ->orderBy('date')
            ->orderBy('start_time')
            ->get();
    }

    public function findForUpdate(int $id): ?AvailableSlot
    {
        return AvailableSlot::whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function markAsBooked(AvailableSlot $slot): void
    {
        $slot->update(['is_booked' => true]);
    }

    public function release(AvailableSlot $slot): void
    {
        $slot->update(['is_booked' => false]);
    }
}
