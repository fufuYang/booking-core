<?php

namespace Database\Factories;

use App\Models\AvailableSlot;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AvailableSlot>
 */
class AvailableSlotFactory extends Factory
{
    protected $model = AvailableSlot::class;

    public function definition(): array
    {
        return [
            'date' => today()->addDay(),
            'start_time' => '14:00:00',
            'is_booked' => false,
        ];
    }

    /**
     * 已被預約的時段。
     */
    public function booked(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_booked' => true,
        ]);
    }
}
