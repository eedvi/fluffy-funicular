<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['cash', 'transfer', 'card'];

        return [
            'payment_number' => Payment::generatePaymentNumber(),
            'loan_id' => Loan::factory(),
            'branch_id' => Branch::factory(),
            'amount' => fake()->randomFloat(2, 100, 2000),
            'payment_date' => now(),
            'payment_method' => fake()->randomElement($paymentMethods),
            'status' => 'completed',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
