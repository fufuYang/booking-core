<?php

namespace Tests\Unit;

use App\Exceptions\AppointmentAlreadyCancelledException;
use App\Exceptions\SlotAlreadyBookedException;
use App\Models\Appointment;
use App\Models\AvailableSlot;
use App\Models\User;
use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\AvailableSlotRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Services\BookingService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

/**
 * Service 層的規則測試：Repository 全部換成假物件，完全不碰資料庫。
 * 這是把資料存取抽到 Repository 契約後才做得到的。
 */
class BookingServiceTest extends TestCase
{
    private ServiceRepositoryInterface&MockInterface $services;

    private AvailableSlotRepositoryInterface&MockInterface $slots;

    private AppointmentRepositoryInterface&MockInterface $appointments;

    private BookingService $booking;

    protected function setUp(): void
    {
        parent::setUp();

        $this->services = Mockery::mock(ServiceRepositoryInterface::class);
        $this->slots = Mockery::mock(AvailableSlotRepositoryInterface::class);
        $this->appointments = Mockery::mock(AppointmentRepositoryInterface::class);

        $this->booking = new BookingService(
            $this->services,
            $this->slots,
            $this->appointments,
        );

        // 沒有真實資料庫，transaction 直接執行 callback 即可。
        DB::shouldReceive('transaction')->andReturnUsing(fn (callable $callback) => $callback());
    }

    public function test_時段已被預約時拋出例外且不會寫入(): void
    {
        $this->slots->shouldReceive('findForUpdate')
            ->once()
            ->with(7)
            ->andReturn(new AvailableSlot(['is_booked' => true]));

        $this->appointments->shouldNotReceive('create');
        $this->slots->shouldNotReceive('markAsBooked');

        $this->expectException(SlotAlreadyBookedException::class);

        $this->booking->book(new User(['id' => 1]), serviceId: 1, slotId: 7);
    }

    public function test_找不到時段時拋出例外(): void
    {
        $this->slots->shouldReceive('findForUpdate')->once()->andReturn(null);
        $this->appointments->shouldNotReceive('create');

        $this->expectException(SlotAlreadyBookedException::class);

        $this->booking->book(new User(['id' => 1]), serviceId: 1, slotId: 99);
    }

    /**
     * 「從今天起」這條規則屬於 Service 層，Repository 只負責照日期查詢。
     */
    public function test_可預約時段以今天為查詢起點(): void
    {
        $this->slots->shouldReceive('availableFrom')
            ->once()
            ->with(today()->toDateString())
            ->andReturn(new Collection);

        $this->assertCount(0, $this->booking->listAvailableSlots());
    }

    public function test_服務列表直接來自_repository(): void
    {
        $expected = new Collection;

        $this->services->shouldReceive('all')->once()->andReturn($expected);

        $this->assertSame($expected, $this->booking->listServices());
    }

    public function test_使用者可以查詢自己的預約列表(): void
    {
        $expected = new Collection;

        $this->appointments->shouldReceive('forUser')
            ->once()
            ->with(1)
            ->andReturn($expected);

        $this->assertSame($expected, $this->booking->listUserAppointments(new User(['id' => 1])));
    }

    public function test_已取消的預約無法再次取消(): void
    {
        $appointment = new Appointment(['status' => 'cancelled']);

        $this->expectException(AppointmentAlreadyCancelledException::class);

        $this->booking->cancel($appointment);
    }
}
