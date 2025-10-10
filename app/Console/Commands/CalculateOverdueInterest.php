<?php

namespace App\Console\Commands;

use App\Models\InterestCharge;
use App\Models\Loan;
use App\Models\User;
use App\Notifications\LoanOverdueNotification;
use Illuminate\Console\Command;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;

class CalculateOverdueInterest extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'loans:calculate-overdue-interest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate and apply daily interest charges for overdue loans';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting overdue interest calculation...');

        // Find all overdue loans
        $overdueLoans = Loan::where('status', Loan::STATUS_ACTIVE)
            ->where('due_date', '<', now()->startOfDay())
            ->where('balance_remaining', '>', 0)
            ->with(['customer', 'item']) // Eager load to prevent N+1
            ->get();

        if ($overdueLoans->isEmpty()) {
            $this->info('No overdue loans found.');
            return 0;
        }

        $this->info("Found {$overdueLoans->count()} overdue loan(s).");

        $processedCount = 0;
        $totalInterestCharged = 0;

        foreach ($overdueLoans as $loan) {
            // Check if interest was already charged today
            $alreadyChargedToday = InterestCharge::where('loan_id', $loan->id)
                ->whereDate('charge_date', today())
                ->exists();

            if ($alreadyChargedToday) {
                $this->line("Loan {$loan->loan_number}: Already charged today, skipping.");
                continue;
            }

            // Calculate daily interest
            // Using interest_rate_overdue if set, otherwise use regular interest_rate
            $interestRate = $loan->interest_rate_overdue ?? $loan->interest_rate;

            // Daily interest = (balance * rate / 100) / 30 (monthly rate divided by 30 days)
            $dailyInterest = ($loan->balance_remaining * ($interestRate / 100)) / 30;

            // Calculate days overdue
            $daysOverdue = now()->startOfDay()->diffInDays($loan->due_date);

            // Create interest charge record
            InterestCharge::create([
                'loan_id' => $loan->id,
                'charge_date' => today(),
                'days_overdue' => $daysOverdue,
                'interest_rate' => $interestRate,
                'principal_amount' => $loan->balance_remaining,
                'interest_amount' => $dailyInterest,
                'notes' => "Interés diario automático por {$daysOverdue} día(s) de mora",
            ]);

            // Update loan balance
            $loan->update([
                'balance_remaining' => $loan->balance_remaining + $dailyInterest,
                'total_amount' => $loan->total_amount + $dailyInterest,
                'interest_amount' => $loan->interest_amount + $dailyInterest,
                'status' => Loan::STATUS_OVERDUE, // Update status to overdue if not already
            ]);

            $processedCount++;
            $totalInterestCharged += $dailyInterest;

            $this->line("Loan {$loan->loan_number}: Charged $" . number_format($dailyInterest, 2) . " (Rate: {$interestRate}%, {$daysOverdue} days overdue)");

            // Send notifications to admin users and customer
            $this->sendOverdueNotification($loan, $daysOverdue, $dailyInterest);
            $this->sendCustomerOverdueEmail($loan, $daysOverdue, $dailyInterest);
        }

        $this->newLine();
        $this->info("Successfully processed {$processedCount} loan(s).");
        $this->info("Total interest charged: $" . number_format($totalInterestCharged, 2));

        return 0;
    }

    /**
     * Send overdue loan notification to admin users
     */
    private function sendOverdueNotification(Loan $loan, int $daysOverdue, float $dailyInterest): void
    {
        // Get all users with permission to view loans (admins and managers)
        $users = User::role(['super_admin', 'Admin', 'Gerente'])->get();

        $notification = Notification::make()
            ->warning()
            ->title('Préstamo Vencido')
            ->body("El préstamo {$loan->loan_number} de {$loan->customer->full_name} está vencido por {$daysOverdue} día(s). Se aplicó un interés de $" . number_format($dailyInterest, 2))
            ->actions([
                Action::make('ver')
                    ->label('Ver Préstamo')
                    ->url(route('filament.admin.resources.loans.view', $loan->id))
                    ->markAsRead(),
            ]);

        foreach ($users as $user) {
            $notification->sendToDatabase($user);
        }
    }

    /**
     * Send overdue email to customer
     */
    private function sendCustomerOverdueEmail(Loan $loan, int $daysOverdue, float $dailyInterest): void
    {
        if ($loan->customer && $loan->customer->email) {
            $loan->customer->notify(new LoanOverdueNotification($loan, $daysOverdue, $dailyInterest));
            $this->line("  → Email sent to customer: {$loan->customer->email}");
        }
    }
}
