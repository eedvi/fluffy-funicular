<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InterestCharge extends Model
{
    protected $fillable = [
        'loan_id',
        'charge_date',
        'days_overdue',
        'interest_rate',
        'principal_amount',
        'interest_amount',
        'balance_before',
        'balance_after',
        'charge_type',
        'notes',
        'is_applied',
    ];

    protected $casts = [
        'charge_date' => 'date',
        'interest_rate' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'is_applied' => 'boolean',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }
}
