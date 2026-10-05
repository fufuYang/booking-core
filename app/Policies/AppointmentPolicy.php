<?php

namespace App\Policies;

use App\Models\Appointment;
use App\Models\User;

class AppointmentPolicy
{
    /**
     * 只有預約本人可以查看該筆預約詳情。
     */
    public function view(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }

    /**
     * 只有預約本人可以取消該筆預約。
     */
    public function cancel(User $user, Appointment $appointment): bool
    {
        return $user->id === $appointment->user_id;
    }
}
