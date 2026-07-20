<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VendorStore extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'store_name',
        'slug',
        'store_email',
        'store_phone',
        'logo',
        'banner',
        'description',
        'address',
        'city',
        'state',
        'pincode',
        'kyc_status',
        'kyc_documents',
        'status',
        'commission_rate',
    ];

    protected function casts(): array
    {
        return [
            'kyc_documents' => 'array',
            'commission_rate' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(VendorWallet::class, 'vendor_store_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'seller_id', 'user_id');
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class, 'vendor_store_id');
    }
}
