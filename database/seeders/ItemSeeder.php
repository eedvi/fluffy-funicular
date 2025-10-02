<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $items = [
            // Joyería
            [
                'name' => 'Anillo de Oro 18K con Diamante',
                'category' => 'Joyería',
                'description' => 'Anillo de oro 18 kilates con diamante de 0.5 quilates',
                'condition' => 'excellent',
                'brand' => 'Tiffany & Co.',
                'appraised_value' => 85000,
                'market_value' => 95000,
                'status' => 'available',
                'acquired_date' => now()->subDays(10),
            ],
            [
                'name' => 'Collar de Perlas Naturales',
                'category' => 'Joyería',
                'description' => 'Collar de perlas naturales cultivadas de 8mm',
                'condition' => 'good',
                'appraised_value' => 35000,
                'market_value' => 42000,
                'status' => 'available',
                'acquired_date' => now()->subDays(5),
            ],
            [
                'name' => 'Reloj Rolex Submariner',
                'category' => 'Joyería',
                'description' => 'Reloj Rolex Submariner acero inoxidable',
                'condition' => 'excellent',
                'brand' => 'Rolex',
                'model' => 'Submariner 116610LN',
                'serial_number' => 'Z891234',
                'appraised_value' => 450000,
                'market_value' => 520000,
                'status' => 'available',
                'acquired_date' => now()->subDays(15),
            ],

            // Electrónica
            [
                'name' => 'iPhone 15 Pro Max 256GB',
                'category' => 'Electrónica',
                'description' => 'iPhone 15 Pro Max color titanio natural',
                'condition' => 'excellent',
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro Max',
                'serial_number' => 'FMDN1234ABC',
                'appraised_value' => 650000,
                'market_value' => 720000,
                'status' => 'available',
                'acquired_date' => now()->subDays(3),
            ],
            [
                'name' => 'MacBook Pro M3 14"',
                'category' => 'Electrónica',
                'description' => 'MacBook Pro 14 pulgadas con chip M3 Pro, 18GB RAM, 512GB SSD',
                'condition' => 'excellent',
                'brand' => 'Apple',
                'model' => 'MacBook Pro 14" M3',
                'serial_number' => 'C02YN1234567',
                'appraised_value' => 1200000,
                'market_value' => 1400000,
                'status' => 'available',
                'acquired_date' => now()->subDays(7),
            ],
            [
                'name' => 'PlayStation 5',
                'category' => 'Electrónica',
                'description' => 'Consola PlayStation 5 con lector de discos',
                'condition' => 'good',
                'brand' => 'Sony',
                'model' => 'CFI-1215A',
                'appraised_value' => 280000,
                'market_value' => 320000,
                'status' => 'available',
                'acquired_date' => now()->subDays(20),
            ],

            // Herramientas
            [
                'name' => 'Taladro Percutor Dewalt',
                'category' => 'Herramientas',
                'description' => 'Taladro percutor inalámbrico 20V con 2 baterías',
                'condition' => 'good',
                'brand' => 'Dewalt',
                'model' => 'DCD996',
                'appraised_value' => 45000,
                'market_value' => 55000,
                'status' => 'available',
                'acquired_date' => now()->subDays(12),
            ],
            [
                'name' => 'Soldadora Inverter',
                'category' => 'Herramientas',
                'description' => 'Soldadora inverter 200A profesional',
                'condition' => 'excellent',
                'brand' => 'Lincoln',
                'appraised_value' => 120000,
                'market_value' => 145000,
                'status' => 'available',
                'acquired_date' => now()->subDays(8),
            ],

            // Otros
            [
                'name' => 'Bicicleta Mountain Bike',
                'category' => 'Otros',
                'description' => 'Bicicleta de montaña rodado 29 con suspensión completa',
                'condition' => 'good',
                'brand' => 'Trek',
                'model' => 'Fuel EX 8',
                'appraised_value' => 250000,
                'market_value' => 290000,
                'status' => 'available',
                'acquired_date' => now()->subDays(6),
            ],
            [
                'name' => 'Guitarra Eléctrica Fender',
                'category' => 'Otros',
                'description' => 'Guitarra eléctrica Fender Stratocaster Americana',
                'condition' => 'excellent',
                'brand' => 'Fender',
                'model' => 'American Professional II Stratocaster',
                'appraised_value' => 180000,
                'market_value' => 210000,
                'status' => 'available',
                'acquired_date' => now()->subDays(4),
            ],
        ];

        foreach ($items as $item) {
            \App\Models\Item::create($item);
        }
    }
}
