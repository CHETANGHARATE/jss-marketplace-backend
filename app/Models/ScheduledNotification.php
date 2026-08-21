<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScheduledNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'event_key',
        'target_audience',
        'target_user_ids',
        'channels',
        'message',
        'action_url',
        'scheduled_for',
        'recurrence',
        'status',
        'total_recipients',
        'successful_deliveries',
        'failed_deliveries',
        'processed_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_user_ids' => 'array',
            'channels' => 'array',
            'scheduled_for' => 'datetime',
            'processed_at' => 'datetime',
            'total_recipients' => 'integer',
            'successful_deliveries' => 'integer',
            'failed_deliveries' => 'integer',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
