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

        // Set balance_remaining to total_amount if not set
        if (!isset($loan->balance_remaining) && isset($loan->total_amount)) {
            $loan->balance_remaining = $loan->total_amount;
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
