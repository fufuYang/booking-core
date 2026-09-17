<?php

namespace App\Providers;

use App\Repositories\Contracts\AppointmentRepositoryInterface;
use App\Repositories\Contracts\AvailableSlotRepositoryInterface;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use App\Repositories\Eloquent\AppointmentRepository;
use App\Repositories\Eloquent\AvailableSlotRepository;
use App\Repositories\Eloquent\ServiceRepository;
use Illuminate\Support\ServiceProvider;

/**
 * 把 Repository 契約綁到 Eloquent 實作。
 * 之後要換資料來源或在測試裡替換成假物件，只需要改這裡的綁定。
 */
class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    public array $bindings = [
        ServiceRepositoryInterface::class => ServiceRepository::class,
        AvailableSlotRepositoryInterface::class => AvailableSlotRepository::class,
        AppointmentRepositoryInterface::class => AppointmentRepository::class,
    ];
}
