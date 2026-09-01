<?php

namespace Database\Seeders;

use App\Models\Service;
use Illuminate\Database\Seeder;

class ServiceSeeder extends Seeder
{
    public function run(): void
    {
        Service::create([
            'name' => '60分鐘塔羅深度占卜',
            'duration_blocks' => 2, // 2塊 = 60分鐘
            'price' => 1200,
        ]);

        Service::create([
            'name' => '30分鐘快速解牌',
            'duration_blocks' => 1,
            'price' => 600,
        ]);
    }
}
