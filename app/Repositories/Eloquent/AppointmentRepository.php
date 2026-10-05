<?php

namespace App\Repositories\Eloquent;

use App\Models\Appointment;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AppointmentRepository implements AppointmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Appointment
    {
        return Appointment::create($attributes);
    }

    /**
     * @return Collection<int, Appointment>
     */
    public function forUser(int $userId): Collection
    {
        return Appointment::where('user_id', $userId)
            ->with(['service', 'availableSlot'])
            ->latest('id')
            ->get();
    }

    public function find(int $id): ?Appointment
    {
        return Appointment::with(['service', 'availableSlot'])->find($id);
    }

    public function markAsCancelled(Appointment $appointment): void
    {
        $appointment->update(['status' => 'cancelled']);
    }
}
