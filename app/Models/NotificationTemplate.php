<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'template_key',
        'event_name',
        'channel',
        'language',
        'subject',
        'body',
        'variables',
        'dlt_template_id',
        'whatsapp_template_name',
        'is_active',
        'is_system_locked',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
            'is_system_locked' => 'boolean',
        ];
    }
}
