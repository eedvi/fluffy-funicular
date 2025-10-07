<?php

namespace App\Console\Commands;

use App\Models\Loan;
use Illuminate\Console\Command;

class UpdateOverdueLoans extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:update-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update loan status to overdue for loans past their due date';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Checking for overdue loans...');

        // Find all active loans that are past due date
        $overdueLoans = Loan::where('status', 'Activo')
            ->where('due_date', '<', now())
            ->get();

        $count = $overdueLoans->count();

        if ($count === 0) {
            $this->info('No overdue loans found.');
            return 0;
        }

        // Update each loan to overdue status
        foreach ($overdueLoans as $loan) {
            $loan->update(['status' => 'Vencido']);

            // Log activity
            activity()
                ->performedOn($loan)
                ->withProperties([
                    'old_status' => 'Activo',
                    'new_status' => 'Vencido',
                    'due_date' => $loan->due_date->format('Y-m-d'),
                ])
                ->log("Préstamo marcado como vencido automáticamente");

            $this->line("Loan #{$loan->loan_number} marked as overdue");
        }

        $this->info("Updated {$count} loan(s) to overdue status.");

        return 0;
    }
}
