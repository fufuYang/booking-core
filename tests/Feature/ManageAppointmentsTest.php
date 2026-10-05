<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ManageAppointmentsTest extends TestCase
{
    use RefreshDatabase;

    public function test_未登入無法查詢預約列表(): void
    {
        $this->getJson('/api/appointments')->assertUnauthorized();
    }

    public function test_未登入無法查看預約詳情(): void
    {
        $this->getJson('/api/appointments/1')->assertUnauthorized();
    }

    public function test_未登入無法取消預約(): void
    {
        $this->postJson('/api/appointments/1/cancel')->assertUnauthorized();
    }


    public function test_登入者可以查詢自己的預約列表(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $appointments = Appointment::factory()->count(2)->create([
            'user_id' => $user->id,
        ]);

        $response = $this->getJson('/api/appointments')
            ->assertOk()
            ->assertJsonCount(2);

        $response->assertJsonStructure([
            '*' => [
                'id',
                'user_id',
                'service_id',
                'available_slot_id',
                'status',
                'service',
                'available_slot',
            ],
        ]);
    }

    public function test_預約列表不會包含其他使用者的預約(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        Appointment::factory()->create(['user_id' => $userA->id]);
        Appointment::factory()->create(['user_id' => $userB->id]);

        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/appointments')
            ->assertOk()
            ->assertJsonCount(1);

        $this->assertSame($userA->id, $response->json('0.user_id'));
    }

    public function test_登入者可以查看自己單筆預約詳情(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $appointment = Appointment::factory()->create(['user_id' => $user->id]);

        $this->getJson("/api/appointments/{$appointment->id}")
            ->assertOk()
            ->assertJsonPath('id', $appointment->id)
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonStructure(['service', 'available_slot']);
    }

    public function test_無法查看其他使用者的預約詳情(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $appointment = Appointment::factory()->create(['user_id' => $userA->id]);

        Sanctum::actingAs($userB);

        $this->getJson("/api/appointments/{$appointment->id}")
            ->assertForbidden();
    }

    public function test_不存在的預約詳情回傳_404(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->getJson('/api/appointments/999999')
            ->assertNotFound();
    }

    public function test_登入者可以取消自己的預約並釋放時段(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $slot = AvailableSlot::factory()->booked()->create();
        $appointment = Appointment::factory()->create([
            'user_id' => $user->id,
            'available_slot_id' => $slot->id,
            'status' => 'pending',
        ]);

        $this->postJson("/api/appointments/{$appointment->id}/cancel")
            ->assertOk()
            ->assertJsonPath('status', 'cancelled');

        $this->assertDatabaseHas('appointments', [
            'id' => $appointment->id,
            'status' => 'cancelled',
        ]);

        $this->assertDatabaseHas('available_slots', [
            'id' => $slot->id,
            'is_booked' => false,
        ]);
    }

    public function test_無法取消其他使用者的預約(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $appointment = Appointment::factory()->create(['user_id' => $userA->id]);

        Sanctum::actingAs($userB);

        $this->postJson("/api/appointments/{$appointment->id}/cancel")
            ->assertForbidden();
    }

    public function test_已取消的預約無法再次取消(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $appointment = Appointment::factory()->create([
            'user_id' => $user->id,
            'status' => 'cancelled',
        ]);

        $this->postJson("/api/appointments/{$appointment->id}/cancel")
            ->assertConflict()
            ->assertJsonPath('message', '此預約已經被取消。');
    }

    public function test_時段被取消釋出後可以被重新預約(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $service = Service::factory()->create();
        $slot = AvailableSlot::factory()->create(['is_booked' => false]);

        // 1. User A 預約時段
        Sanctum::actingAs($userA);
        $appointmentA = $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'available_slot_id' => $slot->id,
        ])->assertCreated()->json();

        // 2. User A 取消預約
        $this->postJson("/api/appointments/{$appointmentA['id']}/cancel")
            ->assertOk();

        // 3. User B 前來預約同一個時段（驗證不會撞到 DB 的 unique constraint）
        Sanctum::actingAs($userB);
        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'available_slot_id' => $slot->id,
        ])
            ->assertCreated()
            ->assertJsonPath('user_id', $userB->id)
            ->assertJsonPath('available_slot_id', $slot->id);

        // 總共應有 2 筆 appointments：一筆 cancelled，一筆 pending
        $this->assertDatabaseCount('appointments', 2);
    }
}
