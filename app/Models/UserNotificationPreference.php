<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserNotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'email_enabled',
        'sms_enabled',
        'whatsapp_enabled',
        'in_app_enabled',
        'order_updates',
        'price_alerts',
        'stock_alerts',
        'store_updates',
        'promotions',
        'abandoned_cart',
        'preferred_language',
    ];

    protected function casts(): array
    {
        return [
            'email_enabled' => 'boolean',
            'sms_enabled' => 'boolean',
            'whatsapp_enabled' => 'boolean',
            'in_app_enabled' => 'boolean',
            'order_updates' => 'boolean',
            'price_alerts' => 'boolean',
            'stock_alerts' => 'boolean',
            'store_updates' => 'boolean',
            'promotions' => 'boolean',
            'abandoned_cart' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
