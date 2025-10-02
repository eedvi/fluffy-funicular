<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $customers = [
            [
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'gender' => 'male',
                'date_of_birth' => '1985-05-15',
                'identity_type' => 'dni',
                'identity_number' => '35123456',
                'address' => 'Av. Corrientes 1234',
                'city' => 'Buenos Aires',
                'state' => 'CABA',
                'postal_code' => '1043',
                'country' => 'Argentina',
                'phone' => '011-4567-8901',
                'mobile' => '11-2345-6789',
                'email' => 'juan.perez@email.com',
                'occupation' => 'Comerciante',
                'monthly_income' => 150000,
                'credit_limit' => 50000,
                'credit_score' => 750,
                'is_active' => true,
                'registration_date' => now()->subMonths(6),
            ],
            [
                'first_name' => 'María',
                'last_name' => 'González',
                'gender' => 'female',
                'date_of_birth' => '1990-08-22',
                'identity_type' => 'dni',
                'identity_number' => '38456789',
                'address' => 'Calle San Martín 567',
                'city' => 'Córdoba',
                'state' => 'Córdoba',
                'postal_code' => '5000',
                'country' => 'Argentina',
                'phone' => '0351-456-7890',
                'mobile' => '351-234-5678',
                'email' => 'maria.gonzalez@email.com',
                'occupation' => 'Empleada',
                'monthly_income' => 120000,
                'credit_limit' => 30000,
                'credit_score' => 680,
                'is_active' => true,
                'registration_date' => now()->subMonths(4),
            ],
            [
                'first_name' => 'Carlos',
                'last_name' => 'Rodríguez',
                'gender' => 'male',
                'date_of_birth' => '1978-12-10',
                'identity_type' => 'dni',
                'identity_number' => '28789012',
                'address' => 'Av. Libertador 890',
                'city' => 'Rosario',
                'state' => 'Santa Fe',
                'postal_code' => '2000',
                'country' => 'Argentina',
                'mobile' => '341-567-8901',
                'email' => 'carlos.rodriguez@email.com',
                'occupation' => 'Independiente',
                'monthly_income' => 180000,
                'credit_limit' => 70000,
                'credit_score' => 820,
                'is_active' => true,
                'registration_date' => now()->subMonths(8),
            ],
        ];

        foreach ($customers as $customer) {
            \App\Models\Customer::create($customer);
        }
    }
}
