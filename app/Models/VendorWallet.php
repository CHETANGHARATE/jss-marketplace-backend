<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VendorWallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'vendor_store_id',
        'balance',
        'pending_balance',
        'total_withdrawn',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:2',
            'pending_balance' => 'decimal:2',
            'total_withdrawn' => 'decimal:2',
        ];
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(VendorStore::class, 'vendor_store_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(VendorTransaction::class, 'vendor_wallet_id')->latest();
    }
}
