<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Otp extends Model
{
    protected $fillable = [
        'email',
        'phone',
        'otp',
        'type',
        'purpose',
        'req_id',
        'attempts',
        'used',
        'expires_at',
    ];

    protected $casts = [
        'used' => 'boolean',
        'attempts' => 'integer',
        'expires_at' => 'datetime',
    ];
}
