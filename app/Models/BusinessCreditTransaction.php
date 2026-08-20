<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BusinessCreditTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_credit_account_id',
        'type',
        'amount',
        'balance_after',
        'reference_type',
        'reference_id',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
        ];
    }

    public function creditAccount(): BelongsTo
    {
        return $this->belongsTo(BusinessCreditAccount::class, 'business_credit_account_id');
    }
}
