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
     */
    private function updateLoanBalance($loan): void
    {
        // Calculate total amount paid from completed payments
        $totalPaid = $loan->payments()
            ->where('status', 'completed')
            ->sum('amount');

        // Update both amount_paid and balance_remaining
        $loan->amount_paid = $totalPaid;
        $loan->balance_remaining = $loan->total_amount - $totalPaid;

        // Update loan status based on balance
        if ($loan->balance_remaining <= 0) {
            $loan->status = 'paid';
            $loan->paid_date = now();
        } elseif ($loan->status === 'paid' && $loan->balance_remaining > 0) {
            // If balance is restored (payment deleted/cancelled), revert to active or overdue
            $loan->status = $loan->due_date < now() ? 'overdue' : 'active';
            $loan->paid_date = null;
        }

        $loan->saveQuietly(); // Save without triggering observers
    }
}
