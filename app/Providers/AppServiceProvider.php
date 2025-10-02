<?php

namespace App\Providers;

use App\Models\Loan;
use App\Models\Sale;
use App\Observers\LoanObserver;
use App\Observers\SaleObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Loan::observe(LoanObserver::class);
        Sale::observe(SaleObserver::class);
    }
}
