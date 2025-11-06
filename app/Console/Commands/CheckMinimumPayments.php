<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class CheckMinimumPayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:check-minimum-payments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for overdue minimum payments and mark loans as at-risk';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue minimum payments...');

        // Find all active loans that require minimum payment
        $loans = Loan::where('requires_minimum_payment', true)
            ->whereIn('status', [Loan::STATUS_ACTIVE, Loan::STATUS_OVERDUE])
            ->whereNotNull('next_minimum_payment_date')
            ->where('principal_remaining', '>', 0)
            ->get();

        $markedAtRisk = 0;
        $gracePeriodExpired = 0;

        foreach ($loans as $loan) {
            // Skip if loan is already fully paid
            if ($loan->principal_remaining <= 0) {
                continue;
            }

            // Check if minimum payment is overdue
            if ($loan->isMinimumPaymentOverdue()) {
                if (!$loan->is_at_risk) {
                    // Mark as at-risk for the first time
                    $loan->markAsAtRisk();
                    $markedAtRisk++;

                    $this->warn("Loan {$loan->loan_number} marked as at-risk. Grace period ends: {$loan->grace_period_end_date->format('Y-m-d')}");
                } elseif ($loan->isGracePeriodExpired()) {
                    // Grace period has expired - increment missed payments counter
                    $loan->consecutive_missed_payments++;
                    $loan->grace_period_end_date = now()->addDays($loan->grace_period_days);
                    $loan->saveQuietly();
                    $gracePeriodExpired++;

                    $this->error("Loan {$loan->loan_number} grace period expired. Consecutive missed payments: {$loan->consecutive_missed_payments}");
                }
            }
        }

        $this->info("Processing complete:");
        $this->info("- Total loans checked: {$loans->count()}");
        $this->info("- Newly marked as at-risk: {$markedAtRisk}");
        $this->info("- Grace periods expired: {$gracePeriodExpired}");

        return Command::SUCCESS;
    }
}
