<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Services\CreditScoreService;
use Illuminate\Console\Command;

class CalculateCustomerCreditScores extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'customers:calculate-credit-scores {--all : Calculate for all customers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate credit scores for customers with loan history';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            $this->info('Calculating customer credit scores...');

            $service = new CreditScoreService();

            // Get customers with at least one loan
            $query = Customer::whereHas('loans');

            // If --all flag is set, include all customers
            if ($this->option('all')) {
                $query = Customer::query();
            }

            $customers = $query->get();

            if ($customers->isEmpty()) {
                $this->info('No customers found to calculate credit scores.');
                return 0;
            }

            $bar = $this->output->createProgressBar($customers->count());
            $bar->start();

            $errorCount = 0;

            foreach ($customers as $customer) {
                try {
                    $service->updateCustomerCreditScore($customer);
                    $bar->advance();
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("\nFailed to calculate score for customer {$customer->id}: {$e->getMessage()}");
                }
            }

            $bar->finish();

            $this->newLine(2);
            $this->info("Successfully calculated credit scores for {$customers->count()} customer(s).");
            if ($errorCount > 0) {
                $this->warn("Failed to calculate {$errorCount} score(s).");
            }

            // Refresh data from database for accurate counts
            $excellent = Customer::where('credit_rating', 'excellent')->count();
            $good = Customer::where('credit_rating', 'good')->count();
            $fair = Customer::where('credit_rating', 'fair')->count();
            $poor = Customer::where('credit_rating', 'poor')->count();

            $this->newLine();
            $this->table(
                ['Rating', 'Count'],
                [
                    ['Excellent (750+)', $excellent],
                    ['Good (650-749)', $good],
                    ['Fair (550-649)', $fair],
                    ['Poor (<550)', $poor],
                ]
            );

            return 0;
        } catch (\Exception $e) {
            $this->error("Command failed: {$e->getMessage()}");
            return 1;
        }
    }
}
