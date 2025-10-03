<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Loan>
 */
class LoanFactory extends Factory
{
    protected $model = Loan::class;

    protected static $counter = 0;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $loanAmount = fake()->randomFloat(2, 500, 5000);
        $interestRate = fake()->randomFloat(2, 5, 20);
        $interest = $loanAmount * ($interestRate / 100);
        $total = $loanAmount + $interest;

        return [
            'loan_number' => 'L-TEST-' . now()->format('YmdHis') . '-' . str_pad(++self::$counter, 6, '0', STR_PAD_LEFT),
            'customer_id' => Customer::factory(),
            'item_id' => Item::factory()->create(['status' => 'available']),
            'branch_id' => Branch::factory(),
            'loan_amount' => $loanAmount,
            'interest_rate' => $interestRate,
            'interest_rate_overdue' => $interestRate * 1.5, // 1.5x the normal rate
            'interest_amount' => $interest,
            'total_amount' => $total,
            'amount_paid' => 0,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'active',
        ];
    }
}
