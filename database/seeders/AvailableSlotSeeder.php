<?php

namespace Database\Seeders;

use App\Models\AvailableSlot;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AvailableSlotSeeder extends Seeder
{
    public function run(): void
    {
        $tomorrow = Carbon::tomorrow()->format('Y-m-d');

        AvailableSlot::create([
            'date' => $tomorrow,
            'start_time' => '14:00:00',
            'is_booked' => false,
        ]);

        AvailableSlot::create([
            'date' => $tomorrow,
            'start_time' => '14:30:00',
            'is_booked' => false,
        ]);

        AvailableSlot::create([
            'date' => $tomorrow,
            'start_time' => '15:00:00',
            'is_booked' => false,
        ]);
    }
}
