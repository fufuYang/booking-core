<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    protected $model = Service::class;

    public function definition(): array
    {
        return [
            'name' => fake()->words(3, true),
            'duration_blocks' => fake()->numberBetween(1, 4),
            'price' => fake()->numberBetween(300, 3000),
        ];
    }
}
