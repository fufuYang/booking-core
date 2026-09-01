<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Emma Test',
            'email' => 'test@example.com',
        ]);

        $this->call([
            ServiceSeeder::class,
            AvailableSlotSeeder::class,
        ]);
    }
}
