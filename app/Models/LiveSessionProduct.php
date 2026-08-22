<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiveSessionProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'live_session_id',
        'product_id',
        'is_pinned',
        'special_live_price',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_pinned' => 'boolean',
            'special_live_price' => 'decimal:2',
            'sort_order' => 'integer',
        ];
    }

    public function liveSession(): BelongsTo
    {
        return $this->belongsTo(LiveSession::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
