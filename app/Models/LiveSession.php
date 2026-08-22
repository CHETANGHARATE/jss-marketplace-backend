<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class LiveSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'seller_id',
        'title',
        'slug',
        'description',
        'thumbnail',
        'stream_url',
        'status',
        'scheduled_at',
        'started_at',
        'ended_at',
        'viewers_count',
        'likes_count',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'viewers_count' => 'integer',
            'likes_count' => 'integer',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (LiveSession $session) {
            if (empty($session->slug)) {
                $session->slug = Str::slug($session->title) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(LiveSessionProduct::class, 'live_session_id')->orderBy('sort_order', 'asc');
    }
}
