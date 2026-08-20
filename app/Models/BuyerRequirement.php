<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BuyerRequirement extends Model
{
    use HasFactory;

    protected $fillable = [
        'requirement_number',
        'user_id',
        'category_id',
        'title',
        'description',
        'quantity',
        'target_price',
        'delivery_pincode',
        'required_date',
        'attachments',
        'status',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'target_price' => 'decimal:2',
            'required_date' => 'date',
            'attachments' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function bids(): HasMany
    {
        return $this->hasMany(SellerBid::class)->latest();
    }
}
