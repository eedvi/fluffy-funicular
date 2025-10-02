<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Loan;
use Carbon\Carbon;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $loanCounter = 1;

        // Loan 1: Active loan - Customer 1, Item 1 (Laptop)
        $loan1StartDate = now()->subDays(15);
        $loan1DueDate = $loan1StartDate->copy()->addDays(30);
        $loan1Amount = 50000.00; // ARS 50,000
        $loan1Interest = 10.00; // 10% interest
        $loan1InterestAmount = $loan1Amount * ($loan1Interest / 100);
        $loan1Total = $loan1Amount + $loan1InterestAmount;

        Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 1,
            'item_id' => 1,
            'loan_amount' => $loan1Amount,
            'interest_rate' => $loan1Interest,
            'interest_amount' => $loan1InterestAmount,
            'total_amount' => $loan1Total,
            'amount_paid' => 15000, // Partial payment made
            'loan_term_days' => 30,
            'start_date' => $loan1StartDate,
            'due_date' => $loan1DueDate,
            'status' => 'active',
            'paid_date' => null,
            'forfeited_date' => null,
            'notes' => 'Préstamo activo con pago parcial realizado',
        ]);

        // Loan 2: Active loan - Customer 2, Item 2 (Smartphone)
        $loan2StartDate = now()->subDays(7);
        $loan2DueDate = $loan2StartDate->copy()->addDays(45);
        $loan2Amount = 30000.00; // ARS 30,000
        $loan2Interest = 12.00; // 12% interest
        $loan2InterestAmount = $loan2Amount * ($loan2Interest / 100);
        $loan2Total = $loan2Amount + $loan2InterestAmount;

        Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 2,
            'item_id' => 2,
            'loan_amount' => $loan2Amount,
            'interest_rate' => $loan2Interest,
            'interest_amount' => $loan2InterestAmount,
            'total_amount' => $loan2Total,
            'amount_paid' => 0, // No payments made yet
            'loan_term_days' => 45,
            'start_date' => $loan2StartDate,
            'due_date' => $loan2DueDate,
            'status' => 'active',
            'paid_date' => null,
            'forfeited_date' => null,
            'notes' => 'Préstamo reciente sin pagos realizados',
        ]);

        // Loan 3: Paid loan - Customer 3, Item 3 (Tablet)
        $loan3StartDate = now()->subDays(60);
        $loan3DueDate = $loan3StartDate->copy()->addDays(30);
        $loan3PaidDate = now()->subDays(5);
        $loan3Amount = 25000.00; // ARS 25,000
        $loan3Interest = 8.00; // 8% interest
        $loan3InterestAmount = $loan3Amount * ($loan3Interest / 100);
        $loan3Total = $loan3Amount + $loan3InterestAmount;

        Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 3,
            'item_id' => 3,
            'loan_amount' => $loan3Amount,
            'interest_rate' => $loan3Interest,
            'interest_amount' => $loan3InterestAmount,
            'total_amount' => $loan3Total,
            'amount_paid' => $loan3Total, // Fully paid
            'loan_term_days' => 30,
            'start_date' => $loan3StartDate,
            'due_date' => $loan3DueDate,
            'status' => 'paid',
            'paid_date' => $loan3PaidDate,
            'forfeited_date' => null,
            'notes' => 'Préstamo pagado completamente',
        ]);

        // Loan 4: Overdue loan - Customer 1, Item 4 (Camera)
        $loan4StartDate = now()->subDays(75);
        $loan4DueDate = $loan4StartDate->copy()->addDays(30);
        $loan4Amount = 40000.00; // ARS 40,000
        $loan4Interest = 15.00; // 15% interest
        $loan4InterestAmount = $loan4Amount * ($loan4Interest / 100);
        $loan4Total = $loan4Amount + $loan4InterestAmount;

        Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 1,
            'item_id' => 4,
            'loan_amount' => $loan4Amount,
            'interest_rate' => $loan4Interest,
            'interest_amount' => $loan4InterestAmount,
            'total_amount' => $loan4Total,
            'amount_paid' => 10000, // Some payment made but still overdue
            'loan_term_days' => 30,
            'start_date' => $loan4StartDate,
            'due_date' => $loan4DueDate,
            'status' => 'overdue',
            'paid_date' => null,
            'forfeited_date' => null,
            'notes' => 'Préstamo vencido, cliente no ha completado el pago',
        ]);

        // Loan 5: Confiscated loan - Customer 2, Item 5 (Watch)
        $loan5StartDate = now()->subDays(120);
        $loan5DueDate = $loan5StartDate->copy()->addDays(30);
        $loan5ForfeitedDate = now()->subDays(10);
        $loan5Amount = 35000.00; // ARS 35,000
        $loan5Interest = 12.00; // 12% interest
        $loan5InterestAmount = $loan5Amount * ($loan5Interest / 100);
        $loan5Total = $loan5Amount + $loan5InterestAmount;

        Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 2,
            'item_id' => 5,
            'loan_amount' => $loan5Amount,
            'interest_rate' => $loan5Interest,
            'interest_amount' => $loan5InterestAmount,
            'total_amount' => $loan5Total,
            'amount_paid' => 5000, // Minor payment made before confiscation
            'loan_term_days' => 30,
            'start_date' => $loan5StartDate,
            'due_date' => $loan5DueDate,
            'status' => 'defaulted',
            'paid_date' => null,
            'forfeited_date' => $loan5ForfeitedDate,
            'notes' => 'Artículo confiscado por falta de pago',
        ]);
    }
}
