<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Installment extends Model
{
    use SoftDeletes;

    // Estados de la cuota
    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_OVERDUE = 'overdue';
    const STATUS_PARTIALLY_PAID = 'partially_paid';

    protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'amount',
        'principal_amount',
        'interest_amount',
        'paid_amount',
        'balance_remaining',
        'late_fee',
        'days_overdue',
        'status',
        'paid_date',
    ];

    protected $casts = [
        'due_date' => 'date',
        'paid_date' => 'date',
        'amount' => 'decimal:2',
        'principal_amount' => 'decimal:2',
        'interest_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'balance_remaining' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'days_overdue' => 'integer',
        'installment_number' => 'integer',
    ];

    /**
     * Relación con el préstamo
     */
    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    /**
     * Verifica si la cuota está vencida
     */
    public function isOverdue(): bool
    {
        return $this->status !== self::STATUS_PAID
            && Carbon::now()->isAfter($this->due_date);
    }

    /**
     * Calcula los días de mora
     */
    public function calculateDaysOverdue(): int
    {
        if (!$this->isOverdue()) {
            return 0;
        }

        return Carbon::now()->diffInDays($this->due_date);
    }

    /**
     * Calcula el cargo por mora
     */
    public function calculateLateFee(): float
    {
        if (!$this->isOverdue() || $this->status === self::STATUS_PAID) {
            return 0;
        }

        $daysOverdue = $this->calculateDaysOverdue();
        if ($daysOverdue <= 0) {
            return 0;
        }

        // Aplicar porcentaje de mora sobre el saldo pendiente
        $lateFeePercentage = $this->loan->late_fee_percentage ?? 5.00;
        return round($this->balance_remaining * ($lateFeePercentage / 100), 2);
    }

    /**
     * Actualiza el estado de la cuota
     */
    public function updateStatus(): void
    {
        if ($this->paid_amount >= $this->amount) {
            $this->status = self::STATUS_PAID;
            $this->paid_date = Carbon::now();
        } elseif ($this->paid_amount > 0) {
            $this->status = self::STATUS_PARTIALLY_PAID;
        } elseif ($this->isOverdue()) {
            $this->status = self::STATUS_OVERDUE;
        } else {
            $this->status = self::STATUS_PENDING;
        }

        // Actualizar días de mora y cargo por mora si está vencida
        if ($this->isOverdue()) {
            $this->days_overdue = $this->calculateDaysOverdue();
            $this->late_fee = $this->calculateLateFee();
        }

        $this->save();
    }

    /**
     * Registra un pago a la cuota
     */
    public function registerPayment(float $amount): void
    {
        $this->paid_amount += $amount;
        $this->balance_remaining = max(0, $this->amount - $this->paid_amount);
        $this->updateStatus();
    }

    /**
     * Scope para cuotas vencidas
     */
    public function scopeOverdue($query)
    {
        return $query->where('status', '!=', self::STATUS_PAID)
                    ->where('due_date', '<', Carbon::now());
    }

    /**
     * Scope para cuotas pendientes
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope para cuotas pagadas
     */
    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }
}
