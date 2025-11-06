<?php

namespace App\Observers;

use App\Models\Loan;

class LoanObserver
{
    /**
     * Handle the Loan "creating" event.
     */
    public function creating(Loan $loan): void
    {
        // Validate that the item is available
        if ($loan->item && !in_array($loan->item->status, ['Disponible', 'Confiscado', 'available', 'forfeited'])) {
            throw new \Exception("El artículo no está disponible para préstamo. Estado actual: {$loan->item->status}");
        }

        // Set amount_paid to 0 if not set
        if (!isset($loan->amount_paid)) {
            $loan->amount_paid = 0;
        }

        // Initialize principal_remaining with loan_amount (full capital at start)
        if (!isset($loan->principal_remaining) && isset($loan->loan_amount)) {
            $loan->principal_remaining = $loan->loan_amount;
        }

        // Set balance_remaining to loan_amount if not set (for backward compatibility)
        if (!isset($loan->balance_remaining) && isset($loan->loan_amount)) {
            $loan->balance_remaining = $loan->loan_amount;
        }

        // Initialize minimum payment fields if minimum payment is required
        if ($loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0) {
            // Set next minimum payment date to 30 days from start date
            if (!isset($loan->next_minimum_payment_date) && isset($loan->start_date)) {
                $loan->next_minimum_payment_date = \Carbon\Carbon::parse($loan->start_date)->addDays(30);
            }

            // Initialize risk tracking fields
            if (!isset($loan->is_at_risk)) {
                $loan->is_at_risk = false;
            }
            if (!isset($loan->consecutive_missed_payments)) {
                $loan->consecutive_missed_payments = 0;
            }
            if (!isset($loan->grace_period_days)) {
                $loan->grace_period_days = 5; // Default grace period
            }
        }
    }

    /**
     * Handle the Loan "created" event.
     */
    public function created(Loan $loan): void
    {
        // Update item status to "collateral"
        if ($loan->item && $loan->status === 'active') {
            $loan->item->update(['status' => 'collateral']);
        }

        // Generate installments if it's an installment plan
        if ($loan->isInstallmentPlan()) {
            $loan->createInstallments();
        }
    }

    /**
     * Handle the Loan "updated" event.
     */
    public function updated(Loan $loan): void
    {
        // If loan is marked as paid, update item status to available
        if ($loan->status === 'paid' && $loan->item) {
            $loan->item->update(['status' => 'available']);
        }

        // If loan is confiscated, update item status
        if ($loan->status === 'defaulted' && $loan->item && $loan->item->status !== 'forfeited') {
            $loan->item->update(['status' => 'forfeited']);
        }
    }

    /**
     * Handle the Loan "deleted" event.
     */
    public function deleted(Loan $loan): void
    {
        //
    }

    /**
     * Handle the Loan "restored" event.
     */
    public function restored(Loan $loan): void
    {
        //
    }

    /**
     * Handle the Loan "force deleted" event.
     */
    public function forceDeleted(Loan $loan): void
    {
        //
    }
}
