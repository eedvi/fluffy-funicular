<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Payment;
use App\Models\Loan;
use Faker\Factory as Faker;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all loans
        $loans = Loan::all();

        if ($loans->isEmpty()) {
            $this->command->warn('No loans available to create payments.');
            return;
        }

        $paymentCounter = 1;
        $paymentMethods = ['cash', 'card', 'transfer'];
        $paymentStatuses = ['completed', 'pending', 'cancelled'];

        // Create payments for loans
        foreach ($loans as $loan) {
            // Skip loans without any payment (amount_paid = 0)
            if ($loan->amount_paid <= 0) {
                continue;
            }

            // Determine number of payments based on loan status
            if ($loan->status === 'paid') {
                // Paid loans: 1-4 payments that sum up to total
                $numberOfPayments = $faker->numberBetween(1, 4);
            } elseif ($loan->status === 'active' || $loan->status === 'overdue') {
                // Active/overdue loans: 1-3 partial payments
                $numberOfPayments = $faker->numberBetween(1, 3);
            } elseif ($loan->status === 'defaulted') {
                // Defaulted loans: typically 1 small payment
                $numberOfPayments = 1;
            } else {
                continue;
            }

            $remainingAmount = $loan->amount_paid;
            $paymentsCreated = 0;

            for ($i = 0; $i < $numberOfPayments; $i++) {
                if ($remainingAmount <= 0) break;

                // Calculate payment amount
                if ($i === $numberOfPayments - 1) {
                    // Last payment: use remaining amount
                    $paymentAmount = $remainingAmount;
                } else {
                    // Intermediate payment: random portion of remaining
                    $maxAmount = $remainingAmount * 0.7;
                    $minAmount = min($remainingAmount * 0.2, $remainingAmount);
                    $paymentAmount = round($faker->randomFloat(2, $minAmount, $maxAmount), -2);
                }

                $remainingAmount -= $paymentAmount;

                // Calculate payment date relative to loan start date
                $daysSinceLoanStart = $faker->numberBetween(1, max(1, now()->diffInDays($loan->start_date)));
                $paymentDate = $loan->start_date->copy()->addDays($daysSinceLoanStart);

                // Most payments are completed, some might be pending
                $paymentStatus = $i === $numberOfPayments - 1 && $faker->boolean(10) ? 'pending' : 'completed';

                // Generate notes based on context
                if ($loan->status === 'paid' && $i === $numberOfPayments - 1) {
                    $notes = 'Pago final - préstamo completamente saldado';
                } elseif ($paymentStatus === 'pending') {
                    $notes = 'Pago pendiente de confirmación';
                } else {
                    $notes = $faker->randomElement([
                        'Pago parcial realizado',
                        'Cuota abonada correctamente',
                        'Pago recibido',
                    ]);
                }

                Payment::create([
                    'payment_number' => 'P-' . $paymentDate->format('Ymd') . '-' . str_pad($paymentCounter++, 4, '0', STR_PAD_LEFT),
                    'loan_id' => $loan->id,
                    'amount' => $paymentAmount,
                    'payment_method' => $faker->randomElement($paymentMethods),
                    'payment_date' => $paymentDate,
                    'status' => $paymentStatus,
                    'notes' => $notes,
                    'branch_id' => $loan->branch_id, // Use loan's branch
                ]);

                $paymentsCreated++;
            }
        }
    }
}
