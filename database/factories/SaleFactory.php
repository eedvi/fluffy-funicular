<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Sale>
 */
class SaleFactory extends Factory
{
    protected $model = Sale::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $paymentMethods = ['cash', 'transfer', 'card'];
        $salePrice = fake()->randomFloat(2, 500, 10000);
        $discount = 0;

        return [
            'sale_number' => Sale::generateSaleNumber(),
            'item_id' => Item::factory()->create(['status' => 'available']),
            'customer_id' => Customer::factory(),
            'branch_id' => Branch::factory(),
            'sale_price' => $salePrice,
            'discount' => $discount,
            'final_price' => $salePrice - $discount,
            'sale_date' => now(),
            'payment_method' => fake()->randomElement($paymentMethods),
            'status' => 'completed',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
