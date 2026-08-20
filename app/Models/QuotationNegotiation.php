<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationNegotiation extends Model
{
    use HasFactory;

    protected $fillable = [
        'quotation_id',
        'user_id',
        'actor_type',
        'offer_price',
        'quantity',
        'message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'offer_price' => 'decimal:2',
            'quantity' => 'integer',
        ];
    }

    public function quotation(): BelongsTo
    {
        return $this->belongsTo(Quotation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
