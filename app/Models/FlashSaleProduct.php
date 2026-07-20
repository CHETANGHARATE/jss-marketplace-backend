<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FlashSaleProduct extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'flash_price',
        'quantity_limit',
        'sold_quantity',
    ];

    protected function casts(): array
    {
        return [
            'flash_price' => 'decimal:2',
        ];
    }

    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
