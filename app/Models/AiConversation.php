<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiConversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'user_message',
        'assistant_message',
        'intent_data',
        'recommended_product_ids',
    ];

    protected function casts(): array
    {
        return [
            'intent_data' => 'array',
            'recommended_product_ids' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
