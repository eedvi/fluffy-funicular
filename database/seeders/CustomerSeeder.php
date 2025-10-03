<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Customer;
use App\Models\Branch;
use Faker\Factory as Faker;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_AR');

        // Get all branches
        $branches = Branch::all()->pluck('id')->toArray();

        // Argentine cities and their states
        $cities = [
            ['city' => 'Buenos Aires', 'state' => 'CABA', 'postal_code' => '1000'],
            ['city' => 'Córdoba', 'state' => 'Córdoba', 'postal_code' => '5000'],
            ['city' => 'Rosario', 'state' => 'Santa Fe', 'postal_code' => '2000'],
            ['city' => 'Mendoza', 'state' => 'Mendoza', 'postal_code' => '5500'],
            ['city' => 'La Plata', 'state' => 'Buenos Aires', 'postal_code' => '1900'],
        ];

        // Occupations
        $occupations = [
            'Comerciante',
            'Empleado/a',
            'Independiente',
            'Profesional',
            'Docente',
            'Técnico/a',
        ];

        // Create 20 customers
        for ($i = 0; $i < 20; $i++) {
            $gender = $faker->randomElement(['male', 'female']);
            $cityData = $faker->randomElement($cities);
            $dateOfBirth = $faker->dateTimeBetween('-65 years', '-18 years');

            // Generate DNI based on age (older people have lower DNI numbers)
            $age = date('Y') - $dateOfBirth->format('Y');
            if ($age > 50) {
                $dniPrefix = $faker->numberBetween(10, 25);
            } elseif ($age > 35) {
                $dniPrefix = $faker->numberBetween(25, 35);
            } else {
                $dniPrefix = $faker->numberBetween(35, 45);
            }
            $dni = $dniPrefix . $faker->numerify('######');

            $monthlyIncome = $faker->numberBetween(80000, 500000);
            $creditLimit = round($monthlyIncome * $faker->randomFloat(2, 0.2, 0.5), -3);
            $creditScore = $faker->numberBetween(550, 900);

            Customer::create([
                'first_name' => $gender === 'male' ? $faker->firstNameMale : $faker->firstNameFemale,
                'last_name' => $faker->lastName,
                'gender' => $gender,
                'date_of_birth' => $dateOfBirth->format('Y-m-d'),
                'identity_type' => 'dni',
                'identity_number' => $dni,
                'address' => $faker->streetAddress,
                'city' => $cityData['city'],
                'state' => $cityData['state'],
                'postal_code' => $cityData['postal_code'],
                'country' => 'Argentina',
                'phone' => $faker->optional(0.6)->phoneNumber,
                'mobile' => $faker->phoneNumber,
                'email' => $faker->optional(0.7)->email,
                'occupation' => $faker->randomElement($occupations),
                'monthly_income' => $monthlyIncome,
                'credit_limit' => $creditLimit,
                'credit_score' => $creditScore,
                'is_active' => $faker->boolean(95),
                'registration_date' => $faker->dateTimeBetween('-2 years', 'now'),
                'branch_id' => $faker->randomElement($branches),
            ]);
        }
    }
}
