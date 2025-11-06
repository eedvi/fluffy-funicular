<?php

namespace App\Console\Commands;

use App\Models\Installment;
use App\Models\Loan;
use Illuminate\Console\Command;
use Carbon\Carbon;

class CheckOverdueInstallments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'installments:check-overdue';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica cuotas vencidas y actualiza cargos por mora';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Verificando cuotas vencidas...');

        // Get all installments that are overdue
        $overdueInstallments = Installment::query()
            ->where('status', '!=', Installment::STATUS_PAID)
            ->where('due_date', '<', Carbon::now())
            ->with('loan')
            ->get();

        $updated = 0;
        $totalLateFees = 0;

        foreach ($overdueInstallments as $installment) {
            // Skip if loan is already paid or forfeited
            if (in_array($installment->loan->status, [Loan::STATUS_PAID, Loan::STATUS_FORFEITED])) {
                continue;
            }

            $previousStatus = $installment->status;
            $previousLateFee = $installment->late_fee;

            // Update status and calculate late fees
            $installment->updateStatus();

            if ($previousStatus !== $installment->status || $previousLateFee !== $installment->late_fee) {
                $updated++;
                $totalLateFees += $installment->late_fee;

                $this->line(sprintf(
                    'Cuota #%d del préstamo %s: %s días de mora, cargo: Q%.2f',
                    $installment->installment_number,
                    $installment->loan->loan_number,
                    $installment->days_overdue,
                    $installment->late_fee
                ));
            }

            // Update loan status to overdue if it has overdue installments
            if ($installment->loan->status === Loan::STATUS_ACTIVE) {
                $installment->loan->status = Loan::STATUS_OVERDUE;
                $installment->loan->saveQuietly();
            }
        }

        $this->info("Proceso completado.");
        $this->info("Cuotas actualizadas: {$updated}");
        $this->info("Total cargos por mora: Q" . number_format($totalLateFees, 2));

        return Command::SUCCESS;
    }
}
