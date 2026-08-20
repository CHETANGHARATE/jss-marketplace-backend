<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProformaInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'proforma_number',
        'purchase_order_id',
        'buyer_id',
        'seller_id',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'buyer_details',
        'seller_details',
        'items_snapshot',
        'payment_instructions',
        'valid_until',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'shipping_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'buyer_details' => 'array',
            'seller_details' => 'array',
            'items_snapshot' => 'array',
            'valid_until' => 'date',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
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
