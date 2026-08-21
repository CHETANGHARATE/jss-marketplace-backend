<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'recipient_target',
        'channel',
        'event_key',
        'template_key',
        'idempotency_key',
        'subject',
        'message_content',
        'payload_data',
        'provider',
        'provider_message_id',
        'status',
        'error_message',
        'provider_response',
        'retry_count',
        'queued_at',
        'sent_at',
        'delivered_at',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload_data' => 'array',
            'provider_response' => 'array',
            'retry_count' => 'integer',
            'queued_at' => 'datetime',
            'sent_at' => 'datetime',
            'delivered_at' => 'datetime',
            'failed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
