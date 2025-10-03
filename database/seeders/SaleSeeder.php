<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Item;
use Faker\Factory as Faker;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get available items and customers
        $availableItems = Item::where('status', 'available')->get();
        $customers = Customer::all();

        if ($availableItems->isEmpty()) {
            $this->command->warn('No items available to create sales.');
            return;
        }

        $saleCounter = 1;
        $paymentMethods = ['cash', 'card', 'transfer'];
        $statuses = ['pending', 'delivered', 'cancelled'];
        $usedItems = [];

        // Create sales (up to available items count)
        $saleCount = min(10, $availableItems->count());

        for ($i = 0; $i < $saleCount; $i++) {
            // Get an item that hasn't been used yet
            $remainingItems = $availableItems->reject(fn($item) => in_array($item->id, $usedItems));
            if ($remainingItems->isEmpty()) {
                break;
            }
            $item = $remainingItems->random();
            $usedItems[] = $item->id;

            $status = $faker->randomElement($statuses);

            // 60% chance of having a customer associated
            $customer = $faker->boolean(60) && !$customers->isEmpty() ? $faker->randomElement($customers) : null;

            // Calculate sale date based on status
            switch ($status) {
                case 'pending':
                    $saleDate = now()->subDays($faker->numberBetween(0, 3));
                    break;
                case 'delivered':
                    $saleDate = now()->subDays($faker->numberBetween(1, 45));
                    break;
                case 'cancelled':
                    $saleDate = now()->subDays($faker->numberBetween(5, 30));
                    break;
            }

            // Sale price is typically market value or slightly above
            $salePrice = round($item->market_value * $faker->randomFloat(2, 0.95, 1.15), -2);

            // 50% chance of discount, if any, 5-20% of sale price
            $discount = $faker->boolean(50) ? round($salePrice * $faker->randomFloat(2, 0.05, 0.20), -2) : 0;
            $finalPrice = $salePrice - $discount;

            // Payment method preferences
            $paymentMethod = $faker->randomElement($paymentMethods);

            // Generate notes based on status and conditions
            if ($status === 'delivered') {
                if ($discount > 0) {
                    $notes = 'Venta completada con descuento';
                } else {
                    $notes = 'Venta completada sin descuento';
                }
                if ($customer) {
                    $notes .= ' a cliente registrado';
                }
            } elseif ($status === 'pending') {
                $notes = match($paymentMethod) {
                    'transfer' => 'Venta pendiente de confirmación de transferencia',
                    'card' => 'Venta pendiente de confirmación de pago con tarjeta',
                    'cash' => 'Venta pendiente de retiro',
                };
            } else { // cancelled
                $notes = 'Venta cancelada por el cliente';
            }

            Sale::create([
                'sale_number' => 'S-' . $saleDate->format('Ymd') . '-' . str_pad($saleCounter++, 4, '0', STR_PAD_LEFT),
                'customer_id' => $customer?->id,
                'item_id' => $item->id,
                'sale_price' => $salePrice,
                'discount' => $discount,
                'final_price' => $finalPrice,
                'payment_method' => $paymentMethod,
                'sale_date' => $saleDate,
                'status' => $status,
                'notes' => $notes,
                'branch_id' => $item->branch_id, // Use item's branch
            ]);
        }
    }
}
