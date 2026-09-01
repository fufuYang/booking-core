<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'duration_blocks',
        'price',
    ];

    // 關聯：這個服務包含哪些預約單？
    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
