<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductLaunchSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'status',
        'subscribed_at',
        'notified_at',
    ];

    protected function casts(): array
    {
        return [
            'subscribed_at' => 'datetime',
            'notified_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
