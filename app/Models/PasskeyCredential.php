<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PasskeyCredential extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credential_id',
        'public_key',
        'sign_count',
        'transports',
        'device_name',
        'last_used_at',
    ];

    protected function casts(): array
    {
        return [
            'sign_count' => 'integer',
            'transports' => 'array',
            'last_used_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
