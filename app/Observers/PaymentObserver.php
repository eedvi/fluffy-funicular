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
        //
    }

    /**
     * Handle the Payment "deleted" event.
     */
    public function deleted(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "restored" event.
     */
    public function restored(Payment $payment): void
    {
        //
    }

    /**
     * Handle the Payment "force deleted" event.
     */
    public function forceDeleted(Payment $payment): void
    {
        //
    }
}
