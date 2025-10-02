<?php

namespace App\Console\Commands;

use App\Models\Loan;
use App\Notifications\LoanReminderNotification;
use Illuminate\Console\Command;

class SendLoanReminders extends Command
{
    protected $signature = 'loans:send-reminders';

    protected $description = 'Send email reminders to customers for loans expiring soon';

    public function handle()
    {
        $this->info('Sending loan reminders...');

        // Find loans expiring in 3 days
        $upcomingLoans = Loan::where('status', 'active')
            ->whereDate('due_date', '=', now()->addDays(3)->startOfDay())
            ->where('balance_remaining', '>', 0)
            ->with('customer')
            ->get();

        if ($upcomingLoans->isEmpty()) {
            $this->info('No loans expiring in 3 days.');
            return 0;
        }

        $this->info("Found {$upcomingLoans->count()} loan(s) expiring in 3 days.");

        $sentCount = 0;

        foreach ($upcomingLoans as $loan) {
            if ($loan->customer && $loan->customer->email) {
                $loan->customer->notify(new LoanReminderNotification($loan, 3));
                $sentCount++;
                $this->line("Reminder sent to {$loan->customer->full_name} for loan {$loan->loan_number}");
            } else {
                $this->warn("No email for customer on loan {$loan->loan_number}");
            }
        }

        $this->newLine();
        $this->info("Successfully sent {$sentCount} reminder(s).");

        return 0;
    }
}
