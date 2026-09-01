<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'service_id',
        'status',
        'meta_data',
    ];

    // 告訴 Laravel：取出 meta_data 時自動轉成陣列，存入時自動轉回 JSON！
    protected function casts(): array
    {
        return [
            'meta_data' => 'array',
        ];
    }

    // 關聯：這筆預約是誰的？
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // 關聯：這筆預約是做什麼服務？
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
