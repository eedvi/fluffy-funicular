<?php

namespace App\Observers;

use App\Models\Sale;

class SaleObserver
{
    /**
     * Handle the Sale "creating" event.
     */
    public function creating(Sale $sale): void
    {
        // Validate that the item is available for sale
        if ($sale->item && !in_array($sale->item->status, ['available', 'forfeited'])) {
            throw new \Exception("El artículo no está disponible para venta. Estado actual: {$sale->item->status}");
        }
    }

    /**
     * Handle the Sale "created" event.
     */
    public function created(Sale $sale): void
    {
        // Update item status to "sold" if sale is delivered
        if ($sale->item && $sale->status === 'delivered') {
            $sale->item->update(['status' => 'sold']);
        }
    }

    /**
     * Handle the Sale "updated" event.
     */
    public function updated(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "deleted" event.
     */
    public function deleted(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "restored" event.
     */
    public function restored(Sale $sale): void
    {
        //
    }

    /**
     * Handle the Sale "force deleted" event.
     */
    public function forceDeleted(Sale $sale): void
    {
        //
    }
}
