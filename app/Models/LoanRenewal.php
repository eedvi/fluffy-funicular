<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanRenewal extends Model
{
    protected $fillable = [
        'loan_id',
        'previous_due_date',
        'new_due_date',
        'extension_days',
        'renewal_fee',
        'interest_rate',
        'interest_amount',
        'notes',
        'processed_by',
    ];

    protected $casts = [
        'previous_due_date' => 'date',
        'new_due_date' => 'date',
        'renewal_fee' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_amount' => 'decimal:2',
    ];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
