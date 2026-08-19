<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderReturn extends Model
{
    use HasFactory;

    protected $table = 'order_returns';

    protected $fillable = [
        'return_number',
        'order_id',
        'order_item_id',
        'user_id',
        'reason',
        'notes',
        'evidence_urls',
        'pickup_address_snapshot',
        'status',
        'refund_amount',
        'courier_name',
        'tracking_number',
        'rejection_reason',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'evidence_urls' => 'array',
            'pickup_address_snapshot' => 'array',
            'refund_amount' => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
}
