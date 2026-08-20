<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SellerBid extends Model
{
    use HasFactory;

    protected $fillable = [
        'buyer_requirement_id',
        'seller_id',
        'bid_unit_price',
        'moq',
        'lead_time_days',
        'shipping_cost',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'bid_unit_price' => 'decimal:2',
            'moq' => 'integer',
            'lead_time_days' => 'integer',
            'shipping_cost' => 'decimal:2',
        ];
    }

    public function requirement(): BelongsTo
    {
        return $this->belongsTo(BuyerRequirement::class, 'buyer_requirement_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
