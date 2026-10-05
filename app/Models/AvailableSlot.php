<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class AvailableSlot extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'start_time',
        'is_booked',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'is_booked' => 'boolean',
        ];
    }

    // 關聯：這個時段被哪一筆預約佔用？（未被預約時為 null）
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }
}
