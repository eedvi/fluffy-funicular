<?php

namespace Database\Seeders;

use App\Models\Branch;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class BranchSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create main branch
        Branch::create([
            'name' => 'Sucursal Principal',
            'code' => 'MAIN',
            'phone' => '555-0100',
            'email' => 'principal@pawnshop.com',
            'address' => 'Calle Principal #123',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '12345',
            'is_active' => true,
            'is_main' => true,
            'notes' => 'Sucursal principal del negocio',
        ]);

        // Create additional branches
        Branch::create([
            'name' => 'Sucursal Norte',
            'code' => 'NORTE',
            'phone' => '555-0101',
            'email' => 'norte@pawnshop.com',
            'address' => 'Avenida Norte #456',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '12346',
            'is_active' => true,
            'is_main' => false,
            'notes' => 'Sucursal zona norte',
        ]);

        Branch::create([
            'name' => 'Sucursal Sur',
            'code' => 'SUR',
            'phone' => '555-0102',
            'email' => 'sur@pawnshop.com',
            'address' => 'Avenida Sur #789',
            'city' => 'Ciudad',
            'state' => 'Estado',
            'postal_code' => '12347',
            'is_active' => true,
            'is_main' => false,
            'notes' => 'Sucursal zona sur',
        ]);
    }
}
