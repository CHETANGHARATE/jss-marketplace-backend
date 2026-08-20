<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'legal_business_name',
        'trade_name',
        'business_type',
        'gstin',
        'pan',
        'registered_address',
        'billing_address',
        'shipping_address',
        'state',
        'city',
        'pincode',
        'contact_person',
        'business_email',
        'business_phone',
        'website',
        'annual_turnover',
        'documents',
        'status',
        'rejection_reason',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'documents' => 'array',
            'verified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isVerified(): boolean
    {
        return $this->status === 'verified';
    }
}
