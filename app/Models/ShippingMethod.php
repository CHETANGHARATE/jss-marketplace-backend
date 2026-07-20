<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingMethod extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipping_zone_id',
        'name',
        'code',
        'base_cost',
        'cost_per_kg',
        'free_shipping_threshold',
        'estimated_days',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'base_cost' => 'decimal:2',
            'cost_per_kg' => 'decimal:2',
            'free_shipping_threshold' => 'decimal:2',
            'estimated_days' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'shipping_zone_id');
    }
}
