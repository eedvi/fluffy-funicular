<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sale;

class SaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $saleCounter = 1;

        // Sale 1: Completed sale with customer - Item 6
        $sale1Price = 45000.00;
        $sale1Discount = 5000.00;

        Sale::create([
            'sale_number' => 'S-' . now()->format('Ymd') . '-' . str_pad($saleCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 1, // Customer associated with sale
            'item_id' => 6,
            'sale_price' => $sale1Price,
            'discount' => $sale1Discount,
            'final_price' => $sale1Price - $sale1Discount, // 40,000
            'payment_method' => 'cash',
            'sale_date' => now()->subDays(3),
            'status' => 'delivered',
            'notes' => 'Venta completada con descuento por pago en efectivo',
        ]);

        // Sale 2: Completed sale without customer - Item 7
        $sale2Price = 28000.00;
        $sale2Discount = 0.00;

        Sale::create([
            'sale_number' => 'S-' . now()->format('Ymd') . '-' . str_pad($saleCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => null, // No customer associated
            'item_id' => 7,
            'sale_price' => $sale2Price,
            'discount' => $sale2Discount,
            'final_price' => $sale2Price - $sale2Discount, // 28,000
            'payment_method' => 'card',
            'sale_date' => now()->subDays(8),
            'status' => 'delivered',
            'notes' => 'Venta a cliente sin registro',
        ]);

        // Sale 3: Pending sale with customer - Item 8
        $sale3Price = 35000.00;
        $sale3Discount = 2000.00;

        Sale::create([
            'sale_number' => 'S-' . now()->format('Ymd') . '-' . str_pad($saleCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => 3, // Customer associated with sale
            'item_id' => 8,
            'sale_price' => $sale3Price,
            'discount' => $sale3Discount,
            'final_price' => $sale3Price - $sale3Discount, // 33,000
            'payment_method' => 'transfer',
            'sale_date' => now(),
            'status' => 'pending',
            'notes' => 'Venta pendiente de confirmación de transferencia',
        ]);

        // Sale 4: Completed sale without customer and discount - Item 9
        $sale4Price = 52000.00;
        $sale4Discount = 7000.00;

        Sale::create([
            'sale_number' => 'S-' . now()->format('Ymd') . '-' . str_pad($saleCounter++, 4, '0', STR_PAD_LEFT),
            'customer_id' => null, // No customer associated
            'item_id' => 9,
            'sale_price' => $sale4Price,
            'discount' => $sale4Discount,
            'final_price' => $sale4Price - $sale4Discount, // 45,000
            'payment_method' => 'cash',
            'sale_date' => now()->subDays(15),
            'status' => 'delivered',
            'notes' => 'Venta completada con descuento especial',
        ]);
    }
}
