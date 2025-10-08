<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class SetupProduction extends Command
{
    protected $signature = 'app:setup-production';
    protected $description = 'Setup production environment: seeds, branch, and admin user';

    public function handle()
    {
        $this->info('Starting production setup...');

        // 1. Seed roles and permissions
        $this->info('Seeding roles and permissions...');
        $this->call('db:seed', ['--class' => 'RoleSeeder', '--force' => true]);

        // 2. Create default branch if it doesn't exist
        $branch = Branch::first();
        if (!$branch) {
            $this->info('Creating default branch...');
            $branch = Branch::create([
                'name' => 'Sucursal Central',
                'code' => 'CENTRAL',
                'address' => 'Dirección Principal',
                'phone' => '1234567890',
                'email' => 'central@pawnshop.com',
                'is_active' => true,
            ]);
            $this->info('Default branch created: ' . $branch->name);
        } else {
            $this->info('Branch already exists: ' . $branch->name);
        }

        // 3. Create admin user if it doesn't exist
        $adminEmail = 'admin@pawnshop.com';
        $user = User::where('email', $adminEmail)->first();

        if (!$user) {
            $this->info('Creating admin user...');
            $user = User::create([
                'name' => 'Admin User',
                'email' => $adminEmail,
                'password' => Hash::make('admin123'),
                'branch_id' => $branch->id,
                'is_active' => true,
            ]);

            $user->assignRole('Admin');

            $this->warn('========================================');
            $this->warn('ADMIN USER CREATED:');
            $this->warn('Email: ' . $adminEmail);
            $this->warn('Password: admin123');
            $this->warn('PLEASE CHANGE THIS PASSWORD IMMEDIATELY!');
            $this->warn('========================================');
        } else {
            $this->info('Admin user already exists: ' . $user->email);
        }

        $this->info('Production setup completed successfully!');

        return 0;
    }
}
