<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Branch;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $faker = Faker::create('es_AR');

        // Get all branches
        $branches = Branch::all();

        // Create Admin user for Main branch
        $adminUser = User::create([
            'name' => 'Administrador Principal',
            'email' => 'admin@pawnshop.com',
            'password' => Hash::make('password'),
            'branch_id' => $branches->where('code', 'MAIN')->first()->id,
        ]);
        $adminUser->assignRole('Admin');

        // Create Gerente for Main branch
        $gerenteMain = User::create([
            'name' => 'Carlos Méndez',
            'email' => 'carlos.mendez@pawnshop.com',
            'password' => Hash::make('password'),
            'branch_id' => $branches->where('code', 'MAIN')->first()->id,
        ]);
        $gerenteMain->assignRole('Gerente');

        // Create Gerente for Norte branch
        $gerenteNorte = User::create([
            'name' => 'María Fernández',
            'email' => 'maria.fernandez@pawnshop.com',
            'password' => Hash::make('password'),
            'branch_id' => $branches->where('code', 'NORTE')->first()->id,
        ]);
        $gerenteNorte->assignRole('Gerente');

        // Create Cajero for Norte branch
        $cajeroNorte = User::create([
            'name' => 'Juan Rodríguez',
            'email' => 'juan.rodriguez@pawnshop.com',
            'password' => Hash::make('password'),
            'branch_id' => $branches->where('code', 'NORTE')->first()->id,
        ]);
        $cajeroNorte->assignRole('Cajero');

        // Create Cajero for Sur branch
        $cajeroSur = User::create([
            'name' => 'Ana López',
            'email' => 'ana.lopez@pawnshop.com',
            'password' => Hash::make('password'),
            'branch_id' => $branches->where('code', 'SUR')->first()->id,
        ]);
        $cajeroSur->assignRole('Cajero');
    }
}
