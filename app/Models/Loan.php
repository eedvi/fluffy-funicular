<?php

namespace App\Models;

use App\Models\Scopes\BranchScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

#[ScopedBy([BranchScope::class])]
class Loan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    // Status constants (English for DB, Spanish for display)
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_FORFEITED = 'forfeited';
    public const STATUS_PENDING = 'pending';

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
        'principal_remaining',
        'status',
        'paid_date',
        'forfeited_date',
        'notes',
        'branch_id',
        // Minimum payment fields
        'minimum_monthly_payment',
        'requires_minimum_payment',
        'next_minimum_payment_date',
        'last_minimum_payment_date',
        'is_at_risk',
        'grace_period_end_date',
        'consecutive_missed_payments',
        'grace_period_days',
        // Installment plan fields
        'payment_plan_type',
        'number_of_installments',
        'installment_amount',
        'installment_frequency_days',
        'late_fee_percentage',
    ];

    protected $casts = [
        'loan_amount' => 'decimal:2',
        'interest_rate' => 'decimal:2',
        'interest_rate_overdue' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'principal_remaining' => 'decimal:2',
        'minimum_monthly_payment' => 'decimal:2',
        'start_date' => 'date',
        'due_date' => 'date',
        'paid_date' => 'date',
        'forfeited_date' => 'date',
        'next_minimum_payment_date' => 'date',
        'last_minimum_payment_date' => 'date',
        'grace_period_end_date' => 'date',
        'requires_minimum_payment' => 'boolean',
        'is_at_risk' => 'boolean',
        // Installment plan casts
        'installment_amount' => 'decimal:2',
        'late_fee_percentage' => 'decimal:2',
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

    public function installments(): HasMany
    {
        return $this->hasMany(Installment::class)->orderBy('installment_number');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', self::STATUS_OVERDUE)->orWhere(function($q) {
            $q->where('status', self::STATUS_ACTIVE)->where('due_date', '<', now());
        });
    }

    // Accessors
    public function getIsOverdueAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_PENDING]) && $this->due_date < now();
    }

    // Static methods
    public static function generateLoanNumber(): string
    {
        $prefix = 'L-';
        $date = now()->format('Ymd');
        // Use withoutGlobalScopes and withTrashed to include deleted records for unique number
        $lastLoan = static::withoutGlobalScopes()
            ->withTrashed()
            ->whereDate('created_at', today())
            ->latest('loan_number')
            ->first();
        $sequence = $lastLoan ? (int) substr($lastLoan->loan_number, -4) + 1 : 1;

        return $prefix . $date . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    public static function calculateLoanAmounts(float $loanAmount, float $interestRate): array
    {
        $interestAmount = $loanAmount * ($interestRate / 100);
        $totalAmount = $loanAmount + $interestAmount;

        return [
            'interest_amount' => round($interestAmount, 2),
            'total_amount' => round($totalAmount, 2),
            // balance_remaining should be the capital, not total with interest
            'balance_remaining' => round($loanAmount, 2),
        ];
    }

    public static function calculateDueDate(string $startDate, int $loanTermDays): string
    {
        return \Carbon\Carbon::parse($startDate)->addDays($loanTermDays)->format('Y-m-d');
    }

    /**
     * Recalculate interest based on remaining principal
     * This method implements the logic where interest is recalculated on the remaining balance
     */
    public function recalculateInterest(): void
    {
        // Calculate interest on the remaining principal
        $this->interest_amount = round($this->principal_remaining * ($this->interest_rate / 100), 2);

        // Update total amount (principal + interest)
        $this->total_amount = round($this->principal_remaining + $this->interest_amount, 2);

        // Update balance_remaining to match total_amount for consistency
        $this->balance_remaining = $this->principal_remaining;
    }

    /**
     * Apply a payment to the loan
     * Payments are applied first to interest, then to principal
     *
     * @param float $paymentAmount The amount being paid
     * @return array Details of how the payment was applied
     */
    public function applyPayment(float $paymentAmount): array
    {
        $appliedToInterest = 0;
        $appliedToPrincipal = 0;

        // Step 1: Pay interest first
        if ($this->interest_amount > 0) {
            $appliedToInterest = min($paymentAmount, $this->interest_amount);
            $paymentAmount -= $appliedToInterest;
        }

        // Step 2: Apply remaining payment to principal
        if ($paymentAmount > 0) {
            $appliedToPrincipal = min($paymentAmount, $this->principal_remaining);
            $this->principal_remaining -= $appliedToPrincipal;
        }

        // Step 3: Recalculate interest on new principal
        $this->recalculateInterest();

        return [
            'applied_to_interest' => round($appliedToInterest, 2),
            'applied_to_principal' => round($appliedToPrincipal, 2),
            'new_principal' => round($this->principal_remaining, 2),
            'new_interest' => round($this->interest_amount, 2),
            'new_total' => round($this->total_amount, 2),
        ];
    }

    /**
     * Check if the loan requires minimum monthly payments
     */
    public function requiresMinimumPayment(): bool
    {
        return $this->requires_minimum_payment && $this->minimum_monthly_payment > 0;
    }

    /**
     * Check if minimum payment is overdue
     */
    public function isMinimumPaymentOverdue(): bool
    {
        if (!$this->requiresMinimumPayment()) {
            return false;
        }

        return $this->next_minimum_payment_date && $this->next_minimum_payment_date->isPast();
    }

    /**
     * Calculate next minimum payment date (30 days from reference date)
     */
    public function calculateNextMinimumPaymentDate(\DateTime $fromDate = null): \DateTime
    {
        $fromDate = $fromDate ?? $this->last_minimum_payment_date ?? $this->start_date;
        return \Carbon\Carbon::parse($fromDate)->addDays(30);
    }

    /**
     * Mark loan as at risk (minimum payment not made)
     */
    public function markAsAtRisk(): void
    {
        $this->is_at_risk = true;
        $this->grace_period_end_date = now()->addDays($this->grace_period_days);
        $this->consecutive_missed_payments++;
        $this->save();
    }

    /**
     * Clear at-risk status (minimum payment made)
     */
    public function clearAtRiskStatus(): void
    {
        $this->is_at_risk = false;
        $this->grace_period_end_date = null;
        $this->consecutive_missed_payments = 0;
        $this->last_minimum_payment_date = now();
        $this->next_minimum_payment_date = $this->calculateNextMinimumPaymentDate(now());
        $this->save();
    }

    /**
     * Check if grace period has expired
     */
    public function isGracePeriodExpired(): bool
    {
        return $this->is_at_risk &&
               $this->grace_period_end_date &&
               $this->grace_period_end_date->isPast();
    }

    /**
     * Validate if a payment amount meets the minimum payment requirement
     */
    public function meetsMinimumPayment(float $amount): bool
    {
        if (!$this->requiresMinimumPayment()) {
            return true; // No minimum payment required
        }

        return $amount >= $this->minimum_monthly_payment;
    }

    /**
     * Check if the loan uses installment payment plan
     */
    public function isInstallmentPlan(): bool
    {
        return $this->payment_plan_type === 'installments';
    }

    /**
     * Generate amortization schedule for installment plan
     * Uses French amortization system (equal installment payments)
     */
    public function generateAmortizationSchedule(): array
    {
        if (!$this->isInstallmentPlan() || !$this->number_of_installments) {
            return [];
        }

        $schedule = [];
        $principal = $this->loan_amount;
        $monthlyRate = ($this->interest_rate / 100) / 12; // Tasa mensual
        $numberOfPayments = $this->number_of_installments;

        // Calcular cuota fija usando fórmula de amortización francesa
        // PMT = P * [r(1 + r)^n] / [(1 + r)^n - 1]
        if ($monthlyRate > 0) {
            $installmentAmount = $principal *
                ($monthlyRate * pow(1 + $monthlyRate, $numberOfPayments)) /
                (pow(1 + $monthlyRate, $numberOfPayments) - 1);
        } else {
            // Si no hay interés, simplemente dividir el principal
            $installmentAmount = $principal / $numberOfPayments;
        }

        $installmentAmount = round($installmentAmount, 2);
        $remainingBalance = $principal;

        // Generar el plan de cuotas
        for ($i = 1; $i <= $numberOfPayments; $i++) {
            // Calcular interés sobre el saldo restante
            $interestAmount = round($remainingBalance * $monthlyRate, 2);

            // Calcular capital amortizado en esta cuota
            $principalAmount = round($installmentAmount - $interestAmount, 2);

            // Ajustar última cuota para evitar diferencias por redondeo
            if ($i === $numberOfPayments) {
                $principalAmount = $remainingBalance;
                $installmentAmount = $principalAmount + $interestAmount;
            }

            // Actualizar saldo restante
            $remainingBalance -= $principalAmount;
            $remainingBalance = max(0, round($remainingBalance, 2));

            // Calcular fecha de vencimiento
            $dueDate = \Carbon\Carbon::parse($this->start_date)
                ->addDays(($i * $this->installment_frequency_days));

            $schedule[] = [
                'installment_number' => $i,
                'due_date' => $dueDate,
                'amount' => $installmentAmount,
                'principal_amount' => $principalAmount,
                'interest_amount' => $interestAmount,
                'balance_remaining' => $installmentAmount,
                'principal_remaining_after' => $remainingBalance,
            ];
        }

        return $schedule;
    }

    /**
     * Create installment records from amortization schedule
     */
    public function createInstallments(): void
    {
        if (!$this->isInstallmentPlan()) {
            return;
        }

        // Eliminar cuotas existentes si las hay
        $this->installments()->delete();

        $schedule = $this->generateAmortizationSchedule();

        foreach ($schedule as $installmentData) {
            $this->installments()->create([
                'installment_number' => $installmentData['installment_number'],
                'due_date' => $installmentData['due_date'],
                'amount' => $installmentData['amount'],
                'principal_amount' => $installmentData['principal_amount'],
                'interest_amount' => $installmentData['interest_amount'],
                'paid_amount' => 0,
                'balance_remaining' => $installmentData['balance_remaining'],
                'late_fee' => 0,
                'days_overdue' => 0,
                'status' => Installment::STATUS_PENDING,
            ]);
        }
    }

    /**
     * Get next unpaid installment
     */
    public function getNextInstallment(): ?Installment
    {
        return $this->installments()
            ->where('status', '!=', Installment::STATUS_PAID)
            ->orderBy('installment_number')
            ->first();
    }

    /**
     * Get all overdue installments
     */
    public function getOverdueInstallments()
    {
        return $this->installments()
            ->overdue()
            ->get();
    }

    /**
     * Calculate total debt including late fees from overdue installments
     */
    public function getTotalDebtWithLateFees(): float
    {
        if (!$this->isInstallmentPlan()) {
            return $this->total_amount;
        }

        $totalDebt = $this->installments()
            ->where('status', '!=', Installment::STATUS_PAID)
            ->get()
            ->sum(function ($installment) {
                return $installment->balance_remaining + $installment->late_fee;
            });

        return round($totalDebt, 2);
    }
}
