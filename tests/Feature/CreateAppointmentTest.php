<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CreateAppointmentTest extends TestCase
{
    use RefreshDatabase;

    public function test_登入後可以建立預約(): void
    {
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $slot = AvailableSlot::factory()->create();

        Sanctum::actingAs($user);

        $this->postJson('/api/appointments', [
            'service_id' => $service->id,
            'available_slot_id' => $slot->id,
        ])
            ->assertCreated()
            ->assertJsonPath('user_id', $user->id)
            ->assertJsonPath('service_id', $service->id)
            ->assertJsonPath('available_slot_id', $slot->id)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('appointments', [
            'user_id' => $user->id,
            'available_slot_id' => $slot->id,
            'status' => 'pending',
        ]);
    }

    public function test_建立預約後時段會被標記為已預約(): void
    {
        $slot = AvailableSlot::factory()->create();

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => $slot->id,
        ])->assertCreated();

        $this->assertTrue($slot->fresh()->is_booked);

        // 訂走之後就不該再出現在可預約清單裡。
        $this->getJson('/api/slots')->assertOk()->assertJsonCount(0);
    }

    public function test_未登入不能建立預約(): void
    {
        $slot = AvailableSlot::factory()->create();

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => $slot->id,
        ])->assertUnauthorized();

        $this->assertDatabaseCount('appointments', 0);
        $this->assertFalse($slot->fresh()->is_booked);
    }

    /**
     * user_id 一律取自 token，避免有人冒用他人身分下單。
     */
    public function test_前端傳入的_user_id_會被忽略(): void
    {
        $actor = User::factory()->create();
        $victim = User::factory()->create();

        Sanctum::actingAs($actor);

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => AvailableSlot::factory()->create()->id,
            'user_id' => $victim->id,
        ])
            ->assertCreated()
            ->assertJsonPath('user_id', $actor->id);

        $this->assertDatabaseMissing('appointments', ['user_id' => $victim->id]);
    }

    public function test_已被預約的時段會回傳_409(): void
    {
        $slot = AvailableSlot::factory()->booked()->create();
        Appointment::factory()->create(['available_slot_id' => $slot->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => $slot->id,
        ])->assertConflict();

        $this->assertDatabaseCount('appointments', 1);
    }

    /**
     * is_booked 與 appointments 不同步時（例如手動改資料），
     * unique constraint 仍要把第二筆預約擋下來，而不是寫進去。
     */
    public function test_時段狀態不同步時仍不會產生重複預約(): void
    {
        $slot = AvailableSlot::factory()->create();
        Appointment::factory()->create(['available_slot_id' => $slot->id]);

        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => $slot->id,
        ])->assertConflict();

        $this->assertDatabaseCount('appointments', 1);
    }

    public function test_meta_data_會存成陣列(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $response = $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => AvailableSlot::factory()->create()->id,
            'meta_data' => ['phone' => '0912345678', 'note' => '第一次來'],
        ])->assertCreated();

        $appointment = Appointment::find($response->json('id'));

        $this->assertSame(
            ['phone' => '0912345678', 'note' => '第一次來'],
            $appointment->meta_data,
        );
    }

    public function test_缺少必填欄位會回傳_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_id', 'available_slot_id']);
    }

    public function test_不存在的服務或時段會回傳_422(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => 999999,
            'available_slot_id' => 999999,
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['service_id', 'available_slot_id']);

        $this->assertDatabaseCount('appointments', 0);
    }

    public function test_meta_data_必須是陣列(): void
    {
        Sanctum::actingAs(User::factory()->create());

        $this->postJson('/api/appointments', [
            'service_id' => Service::factory()->create()->id,
            'available_slot_id' => AvailableSlot::factory()->create()->id,
            'meta_data' => 'not-an-array',
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['meta_data']);
    }
}
