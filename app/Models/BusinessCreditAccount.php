<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BusinessCreditAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'credit_limit',
        'available_credit',
        'used_credit',
        'repayment_due_days',
        'status',
        'admin_notes',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'credit_limit' => 'decimal:2',
            'available_credit' => 'decimal:2',
            'used_credit' => 'decimal:2',
            'repayment_due_days' => 'integer',
            'approved_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(BusinessCreditTransaction::class)->latest();
    }
}
