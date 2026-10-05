<?php

namespace App\Repositories\Contracts;

use App\Models\Service;
use Illuminate\Database\Eloquent\Collection;

interface ServiceRepositoryInterface
{
    /**
     * 取得所有服務項目。
     *
     * @return Collection<int, Service>
     */
    public function all(): Collection;
}
