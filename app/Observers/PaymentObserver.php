<?php

namespace App\Observers;

use App\Models\Payment;
use App\Notifications\PaymentReceivedNotification;

class PaymentObserver
{
    /**
     * Handle the Payment "created" event.
     */
    public function created(Payment $payment): void
    {
        // Update loan balance if payment is completed
        if ($payment->status === 'completed' && $payment->loan) {
            $this->updateLoanBalance($payment->loan);
        }

        // Send payment confirmation email to customer
        if ($payment->loan && $payment->loan->customer && $payment->loan->customer->email) {
            $payment->loan->customer->notify(new PaymentReceivedNotification($payment));
        }
    }

    /**
     * Handle the Payment "updated" event.
     */
    public function updated(Payment $payment): void
    {
        // Update loan balance if payment status or amount changed
        if ($payment->loan && ($payment->wasChanged('status') || $payment->wasChanged('amount'))) {
            $this->updateLoanBalance($payment->loan);
        }
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        // Update loan balance when payment is deleted
        if ($payment->loan) {
            $this->updateLoanBalance($payment->loan);
        }
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        // Update loan balance when payment is restored
        if ($payment->loan) {
            $this->updateLoanBalance($payment->loan);
        }
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        // Update loan balance when payment is force deleted
        if ($payment->loan) {
            $this->updateLoanBalance($payment->loan);
        }
    }

    /**
     * Update loan balance based on completed payments
     * NEW LOGIC: Recalculates interest on remaining principal after each payment
     * Payments are applied first to interest, then to principal
     */
    private function updateLoanBalance($loan): void
    {
        // Check if this is an installment plan
        if ($loan->isInstallmentPlan()) {
            $this->updateInstallmentLoanBalance($loan);
            return;
        }

        // Original logic for minimum payment plans
        // Reset loan to initial state
        $loan->principal_remaining = $loan->loan_amount;
        $loan->interest_amount = round($loan->loan_amount * ($loan->interest_rate / 100), 2);
        $loan->total_amount = $loan->principal_remaining + $loan->interest_amount;
        $loan->balance_remaining = $loan->principal_remaining;

        // Get all completed payments ordered by date
        $payments = $loan->payments()
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get();

        $totalPaid = 0;
        $lastPaymentDate = null;
        $latestMinimumPaymentMet = false;

        // Apply each payment sequentially
        foreach ($payments as $payment) {
            $loan->applyPayment($payment->amount);
            $totalPaid += $payment->amount;
            $lastPaymentDate = $payment->payment_date;

            // Check if this payment meets minimum payment requirement
            if ($loan->requiresMinimumPayment() && $loan->meetsMinimumPayment($payment->amount)) {
                $latestMinimumPaymentMet = true;
            }
        }

        // Update amount_paid
        $loan->amount_paid = $totalPaid;

        // Handle minimum payment tracking
        if ($loan->requiresMinimumPayment() && $latestMinimumPaymentMet && $lastPaymentDate) {
            // Clear at-risk status since minimum payment was met
            if ($loan->is_at_risk) {
                $loan->is_at_risk = false;
                $loan->grace_period_end_date = null;
                $loan->consecutive_missed_payments = 0;
            }

            // Update minimum payment dates
            $loan->last_minimum_payment_date = $lastPaymentDate;
            $loan->next_minimum_payment_date = $loan->calculateNextMinimumPaymentDate(\Carbon\Carbon::parse($lastPaymentDate));
        }

        // Update loan status based on principal remaining
        if ($loan->principal_remaining <= 0) {
            $loan->status = 'paid';
            $loan->paid_date = now();
        } elseif ($loan->status === 'paid' && $loan->principal_remaining > 0) {
            // If balance is restored (payment deleted/cancelled), revert to active or overdue
            $loan->status = $loan->due_date < now() ? 'overdue' : 'active';
            $loan->paid_date = null;
        }

        $loan->saveQuietly(); // Save without triggering observers
    }

    /**
     * Update installment loan balance
     * Applies payments to installments in order
     */
    private function updateInstallmentLoanBalance($loan): void
    {
        // Reset all installments to initial state
        foreach ($loan->installments as $installment) {
            $installment->paid_amount = 0;
            $installment->balance_remaining = $installment->amount;
            $installment->updateStatus();
        }

        // Get all completed payments ordered by date
        $payments = $loan->payments()
            ->where('status', 'completed')
            ->orderBy('payment_date')
            ->orderBy('created_at')
            ->get();

        $totalPaid = 0;

        // Apply each payment to installments in order
        foreach ($payments as $payment) {
            $remainingAmount = $payment->amount;
            $totalPaid += $payment->amount;

            // Get unpaid installments in order
            $unpaidInstallments = $loan->installments()
                ->where('status', '!=', 'paid')
                ->orderBy('installment_number')
                ->get();

            foreach ($unpaidInstallments as $installment) {
                if ($remainingAmount <= 0) {
                    break;
                }

                // Calculate how much to apply to this installment (including late fees)
                $installmentBalance = $installment->balance_remaining + $installment->late_fee;
                $amountToApply = min($remainingAmount, $installmentBalance);

                // Apply payment to installment
                $installment->registerPayment($amountToApply);
                $remainingAmount -= $amountToApply;
            }
        }

        // Update loan totals
        $loan->amount_paid = $totalPaid;

        // Calculate remaining balance from unpaid installments
        $remainingBalance = $loan->installments()
            ->where('status', '!=', 'paid')
            ->sum('balance_remaining');

        $loan->balance_remaining = $remainingBalance;
        $loan->total_amount = $loan->installments()->sum('amount');

        // Update loan status
        $allPaid = $loan->installments()->where('status', '!=', 'paid')->count() === 0;
        if ($allPaid && $remainingBalance <= 0) {
            $loan->status = 'paid';
            $loan->paid_date = now();
        } elseif ($loan->status === 'paid' && $remainingBalance > 0) {
            // If balance is restored, check if any installments are overdue
            $hasOverdue = $loan->installments()->overdue()->count() > 0;
            $loan->status = $hasOverdue ? 'overdue' : 'active';
            $loan->paid_date = null;
        }

        $loan->saveQuietly(); // Save without triggering observers
    }
}
