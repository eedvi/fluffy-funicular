<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Loan extends Model
{
    use SoftDeletes, LogsActivity;

    protected $fillable = [
        'loan_number',
        'customer_id',
        'item_id',
        'loan_amount',
        'interest_rate',
        'interest_rate_overdue',
        'loan_term_days',
        'start_date',
        'due_date',
        'interest_amount',
        'total_amount',
        'amount_paid',
        'balance_remaining',
        'status',
        'paid_date',
        'forfeited_date',
        'notes',
        'branch_id',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_rate_overdue' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'forfeited_date' => 'date',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['loan_number', 'customer_id', 'item_id', 'loan_amount', 'interest_rate', 'due_date', 'status'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relationships
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function renewals(): HasMany
    {
        return $this->hasMany(LoanRenewal::class);
    }

    public function interestCharges(): HasMany
    {
        return $this->hasMany(InterestCharge::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'overdue')->orWhere(function($q) {
            $q->where('status', 'active')->where('due_date', '<', now());
        });
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, ['active', 'pending']) && $this->due_date < now();
    }

    // Static methods
    public static function generateLoanNumber(): string
    {
        $prefix = 'L-';
        $date = now()->format('Ymd');
        $lastLoan = static::whereDate('created_at', today())->latest()->first();
        $sequence = $lastLoan ? (int) substr($lastLoan->loan_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }
}
