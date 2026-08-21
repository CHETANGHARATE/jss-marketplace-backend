<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbandonedCartReminder extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'cart_total',
        'item_count',
        'cart_snapshot',
        'reminder_stage',
        'status',
        'abandoned_at',
        'last_reminded_at',
    ];

    protected function casts(): array
    {
        return [
            'cart_total' => 'decimal:2',
            'item_count' => 'integer',
            'cart_snapshot' => 'array',
            'reminder_stage' => 'integer',
            'abandoned_at' => 'datetime',
            'last_reminded_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
