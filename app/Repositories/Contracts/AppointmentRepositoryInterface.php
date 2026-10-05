<?php

namespace App\Repositories\Contracts;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Collection;

interface AppointmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Appointment;

    /**
     * @return Collection<int, Appointment>
     */
    public function forUser(int $userId): Collection;

    public function find(int $id): ?Appointment;

    public function markAsCancelled(Appointment $appointment): void;
}

