<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SampleRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'sample_request_number',
        'product_id',
        'buyer_id',
        'seller_id',
        'quantity',
        'sample_price',
        'shipping_address',
        'notes',
        'status',
        'courier_name',
        'tracking_number',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'sample_price' => 'decimal:2',
            'shipping_address' => 'array',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
}
