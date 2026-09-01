<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
