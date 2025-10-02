<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Loan;
use App\Models\Customer;
use App\Models\Item;
use Carbon\Carbon;
use Faker\Factory as Faker;

class LoanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get available customers and items
        $customers = Customer::all();
        $availableItems = Item::where('status', 'available')->get();

        if ($customers->isEmpty() || $availableItems->isEmpty()) {
            $this->command->warn('No customers or items available to create loans.');
            return;
        }

        $loanCounter = 1;
        $statuses = ['active', 'paid', 'overdue', 'defaulted'];
        $loanTermOptions = [30, 45, 60, 90];
        $usedItems = [];

        // Create loans with various statuses (up to available items count)
        $loanCount = min(15, $availableItems->count());

        for ($i = 0; $i < $loanCount; $i++) {
            $customer = $faker->randomElement($customers);

            // Get an item that hasn't been used yet
            $remainingItems = $availableItems->reject(fn($item) => in_array($item->id, $usedItems));
            if ($remainingItems->isEmpty()) {
                break;
            }
            $item = $remainingItems->random();
            $usedItems[] = $item->id;

            $status = $faker->randomElement($statuses);

            // Calculate loan dates based on status
            switch ($status) {
                case 'active':
                    $startDate = now()->subDays($faker->numberBetween(5, 25));
                    $loanTerm = $faker->randomElement($loanTermOptions);
                    $dueDate = $startDate->copy()->addDays($loanTerm);
                    $paidDate = null;
                    $forfeitedDate = null;
                    break;

                case 'paid':
                    $startDate = now()->subDays($faker->numberBetween(40, 120));
                    $loanTerm = $faker->randomElement($loanTermOptions);
                    $dueDate = $startDate->copy()->addDays($loanTerm);
                    $paidDate = $startDate->copy()->addDays($faker->numberBetween($loanTerm - 10, $loanTerm + 5));
                    $forfeitedDate = null;
                    break;

                case 'overdue':
                    $startDate = now()->subDays($faker->numberBetween(40, 90));
                    $loanTerm = $faker->randomElement($loanTermOptions);
                    $dueDate = $startDate->copy()->addDays($loanTerm);
                    $paidDate = null;
                    $forfeitedDate = null;
                    break;

                case 'defaulted':
                    $startDate = now()->subDays($faker->numberBetween(90, 180));
                    $loanTerm = $faker->randomElement($loanTermOptions);
                    $dueDate = $startDate->copy()->addDays($loanTerm);
                    $paidDate = null;
                    $forfeitedDate = now()->subDays($faker->numberBetween(1, 30));
                    break;
            }

            // Calculate loan amount (50-70% of appraised value)
            $loanAmount = round($item->appraised_value * $faker->randomFloat(2, 0.5, 0.7), -2);
            $interestRate = $faker->randomFloat(2, 8, 20); // 8-20% interest
            $interestAmount = round($loanAmount * ($interestRate / 100), 2);
            $totalAmount = $loanAmount + $interestAmount;

            // Calculate amount paid based on status
            switch ($status) {
                case 'active':
                    $amountPaid = $faker->boolean(60) ? round($totalAmount * $faker->randomFloat(2, 0.1, 0.6), 2) : 0;
                    break;
                case 'paid':
                    $amountPaid = $totalAmount;
                    break;
                case 'overdue':
                    $amountPaid = round($totalAmount * $faker->randomFloat(2, 0, 0.5), 2);
                    break;
                case 'defaulted':
                    $amountPaid = round($totalAmount * $faker->randomFloat(2, 0, 0.3), 2);
                    break;
            }

            // Generate notes based on status
            $notes = match($status) {
                'active' => $amountPaid > 0 ? 'Préstamo activo con pagos realizados' : 'Préstamo activo sin pagos',
                'paid' => 'Préstamo completamente saldado',
                'overdue' => 'Préstamo vencido, pendiente de pago',
                'defaulted' => 'Artículo confiscado por falta de pago',
            };

            Loan::create([
                'loan_number' => 'L-' . $startDate->format('Ymd') . '-' . str_pad($loanCounter++, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer->id,
                'item_id' => $item->id,
                'loan_amount' => $loanAmount,
                'interest_rate' => $interestRate,
                'interest_amount' => $interestAmount,
                'total_amount' => $totalAmount,
                'amount_paid' => $amountPaid,
                'loan_term_days' => $loanTerm,
                'start_date' => $startDate,
                'due_date' => $dueDate,
                'status' => $status,
                'paid_date' => $paidDate,
                'forfeited_date' => $forfeitedDate,
                'notes' => $notes,
                'branch_id' => $item->branch_id, // Use item's branch
            ]);
        }
    }
}
