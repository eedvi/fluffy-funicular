<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Item;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Item>
 */
class ItemFactory extends Factory
{
    protected $model = Item::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = ['Jewelry', 'Electronics', 'Tools', 'Other'];
        $statuses = ['available', 'collateral', 'sold', 'forfeited'];

        return [
            'branch_id' => Branch::factory(),
            'name' => fake()->words(3, true),
            'category' => fake()->randomElement($categories),
            'description' => fake()->sentence(),
            'appraised_value' => fake()->randomFloat(2, 100, 10000),
            'status' => fake()->randomElement($statuses),
        ];
    }
}
