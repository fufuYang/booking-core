<?php

namespace App\Repositories\Contracts;

use App\Models\AvailableSlot;
use Illuminate\Database\Eloquent\Collection;

interface AvailableSlotRepositoryInterface
{
    /**
     * 取得指定日期（含）之後、尚未被預約的時段，依日期與開始時間排序。
     *
     * @return Collection<int, AvailableSlot>
     */
    public function availableFrom(string $date): Collection;

    /**
     * 取出時段並加上列鎖，必須在 transaction 內呼叫。
     */
    public function findForUpdate(int $id): ?AvailableSlot;

    /**
     * 將時段標記為已預約。
     */
    public function markAsBooked(AvailableSlot $slot): void;

    /**
     * 將時段標記為未預約（釋放時段）。
     */
    public function release(AvailableSlot $slot): void;
}
