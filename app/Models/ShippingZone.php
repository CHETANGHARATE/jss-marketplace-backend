<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingZone extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'countries',
        'states',
        'pincodes',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'countries' => 'array',
            'states' => 'array',
            'pincodes' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function methods(): HasMany
    {
        return $this->hasMany(ShippingMethod::class, 'shipping_zone_id');
    }
}
