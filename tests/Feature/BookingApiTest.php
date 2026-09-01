<?php

namespace Tests\Feature;

use App\Models\AvailableSlot;
use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BookingApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_服務列表回傳所有服務(): void
    {
        Service::factory()->count(2)->create();

        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonStructure([
                ['id', 'name', 'duration_blocks', 'price'],
            ]);
    }

    public function test_沒有服務時回傳空陣列(): void
    {
        $this->getJson('/api/services')
            ->assertOk()
            ->assertJsonCount(0);
    }

    public function test_已被預約的時段不會出現(): void
    {
        $available = AvailableSlot::factory()->create(['date' => today()->addDay()]);
        AvailableSlot::factory()->booked()->create(['date' => today()->addDay()]);

        $this->getJson('/api/slots')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $available->id);
    }

    public function test_過去日期的時段不會出現(): void
    {
        AvailableSlot::factory()->create(['date' => today()->subDay()]);
        $future = AvailableSlot::factory()->create(['date' => today()->addDay()]);

        $this->getJson('/api/slots')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $future->id);
    }

    /**
     * 守住 `date >= today` 的邊界：若條件誤寫成 `>`，今天的時段會整批消失。
     */
    public function test_今天的時段仍然可以預約(): void
    {
        $today = AvailableSlot::factory()->create(['date' => today()]);

        $this->getJson('/api/slots')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $today->id);
    }

    public function test_時段依日期與開始時間排序(): void
    {
        $third = AvailableSlot::factory()->create([
            'date' => today()->addDays(2), 'start_time' => '09:00:00',
        ]);
        $second = AvailableSlot::factory()->create([
            'date' => today()->addDay(), 'start_time' => '15:00:00',
        ]);
        $first = AvailableSlot::factory()->create([
            'date' => today()->addDay(), 'start_time' => '09:00:00',
        ]);

        $this->getJson('/api/slots')
            ->assertOk()
            ->assertJsonPath('0.id', $first->id)
            ->assertJsonPath('1.id', $second->id)
            ->assertJsonPath('2.id', $third->id);
    }

    public function test_沒有可預約時段時回傳空陣列(): void
    {
        AvailableSlot::factory()->booked()->create();

        $this->getJson('/api/slots')
            ->assertOk()
            ->assertJsonCount(0);
    }
}
