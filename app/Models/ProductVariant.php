<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'title',
        'price',
        'offer_price',
        'stock_quantity',
        'image',
        'attributes',
        'is_default',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'offer_price' => 'decimal:2',
            'stock_quantity' => 'integer',
            'attributes' => 'array',
            'is_default' => 'boolean',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
