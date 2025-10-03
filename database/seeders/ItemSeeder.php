<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Item;
use App\Models\Branch;
use Faker\Factory as Faker;

class ItemSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create();

        // Get all branches
        $branches = Branch::all()->pluck('id')->toArray();

        // Define items by category with realistic brands
        $itemsData = [
            // Joyería
            [
                'category' => 'Joyería',
                'items' => [
                    ['name' => 'Anillo de Oro 18K', 'brands' => ['Tiffany & Co.', 'Cartier', 'Bulgari', 'Van Cleef'], 'value_range' => [80000, 200000]],
                    ['name' => 'Collar de Perlas', 'brands' => ['Mikimoto', 'Tiffany & Co.', null], 'value_range' => [35000, 120000]],
                    ['name' => 'Reloj de Lujo', 'brands' => ['Rolex', 'Omega', 'Tag Heuer', 'Breitling', 'Patek Philippe'], 'value_range' => [250000, 800000]],
                    ['name' => 'Pulsera de Oro', 'brands' => ['Cartier', 'Bulgari', null], 'value_range' => [45000, 150000]],
                    ['name' => 'Aros de Diamantes', 'brands' => ['Tiffany & Co.', 'Cartier', null], 'value_range' => [60000, 180000]],
                ],
            ],
            // Electrónica
            [
                'category' => 'Electrónica',
                'items' => [
                    ['name' => 'iPhone', 'brands' => ['Apple'], 'models' => ['iPhone 15 Pro Max', 'iPhone 15 Pro', 'iPhone 14 Pro'], 'value_range' => [500000, 850000]],
                    ['name' => 'MacBook', 'brands' => ['Apple'], 'models' => ['MacBook Pro M3 14"', 'MacBook Air M2', 'MacBook Pro M3 16"'], 'value_range' => [800000, 1800000]],
                    ['name' => 'PlayStation', 'brands' => ['Sony'], 'models' => ['PlayStation 5', 'PlayStation 5 Digital'], 'value_range' => [280000, 380000]],
                    ['name' => 'Samsung Galaxy', 'brands' => ['Samsung'], 'models' => ['S24 Ultra', 'S23 Ultra', 'Z Fold 5'], 'value_range' => [450000, 750000]],
                    ['name' => 'Tablet iPad', 'brands' => ['Apple'], 'models' => ['iPad Pro 12.9"', 'iPad Air', 'iPad Pro 11"'], 'value_range' => [400000, 900000]],
                    ['name' => 'Smart TV', 'brands' => ['Samsung', 'LG', 'Sony'], 'models' => ['OLED 55"', 'QLED 65"', '4K 50"'], 'value_range' => [300000, 800000]],
                ],
            ],
            // Herramientas
            [
                'category' => 'Herramientas',
                'items' => [
                    ['name' => 'Taladro Percutor', 'brands' => ['Dewalt', 'Bosch', 'Makita', 'Milwaukee'], 'value_range' => [40000, 80000]],
                    ['name' => 'Soldadora Inverter', 'brands' => ['Lincoln', 'Miller', 'Esab'], 'value_range' => [100000, 180000]],
                    ['name' => 'Amoladora Angular', 'brands' => ['Bosch', 'Dewalt', 'Makita'], 'value_range' => [35000, 70000]],
                    ['name' => 'Compresor de Aire', 'brands' => ['Stanley', 'California Air Tools', 'Schulz'], 'value_range' => [80000, 150000]],
                    ['name' => 'Set de Herramientas', 'brands' => ['Stanley', 'Craftsman', 'Milwaukee'], 'value_range' => [50000, 120000]],
                ],
            ],
            // Otros
            [
                'category' => 'Otros',
                'items' => [
                    ['name' => 'Bicicleta Mountain Bike', 'brands' => ['Trek', 'Giant', 'Specialized', 'Cannondale'], 'value_range' => [200000, 450000]],
                    ['name' => 'Guitarra Eléctrica', 'brands' => ['Fender', 'Gibson', 'Ibanez', 'PRS'], 'value_range' => [150000, 350000]],
                    ['name' => 'Cámara Digital', 'brands' => ['Canon', 'Nikon', 'Sony'], 'models' => ['EOS R6', 'Z6 II', 'A7 IV'], 'value_range' => [180000, 450000]],
                    ['name' => 'Consola DJ', 'brands' => ['Pioneer', 'Numark', 'Denon'], 'value_range' => [120000, 300000]],
                ],
            ],
        ];

        $conditions = ['excellent', 'good', 'fair'];
        $statuses = ['available', 'on_loan', 'sold'];

        // Create 20 items distributed across categories and branches
        $itemCount = 0;
        $targetCount = 20;

        foreach ($itemsData as $categoryData) {
            foreach ($categoryData['items'] as $itemTemplate) {
                if ($itemCount >= $targetCount) break 2;

                $brand = $faker->randomElement($itemTemplate['brands']);
                $model = isset($itemTemplate['models']) ? $faker->randomElement($itemTemplate['models']) : null;

                $appraisedValue = $faker->numberBetween(
                    $itemTemplate['value_range'][0],
                    $itemTemplate['value_range'][1]
                );
                $marketValue = round($appraisedValue * $faker->randomFloat(2, 1.1, 1.3), -3);

                $description = $itemTemplate['name'];
                if ($brand) $description .= " marca {$brand}";
                if ($model) $description .= " modelo {$model}";
                $description .= ". " . $faker->sentence(6);

                Item::create([
                    'name' => $itemTemplate['name'] . ($brand ? " {$brand}" : ''),
                    'category' => $categoryData['category'],
                    'description' => $description,
                    'condition' => $faker->randomElement($conditions),
                    'brand' => $brand,
                    'model' => $model,
                    'serial_number' => $faker->optional(0.7)->bothify('??####????'),
                    'appraised_value' => $appraisedValue,
                    'market_value' => $marketValue,
                    'status' => $faker->randomElement($statuses),
                    'acquired_date' => $faker->dateTimeBetween('-60 days', 'now'),
                    'branch_id' => $faker->randomElement($branches),
                ]);

                $itemCount++;
            }
        }
    }
}
