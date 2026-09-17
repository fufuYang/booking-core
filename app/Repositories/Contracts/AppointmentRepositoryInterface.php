<?php

namespace App\Repositories\Contracts;

use App\Models\Appointment;

interface AppointmentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $attributes
     */
    public function create(array $attributes): Appointment;
}
