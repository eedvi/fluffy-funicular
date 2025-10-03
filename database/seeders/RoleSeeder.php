<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get all permissions
        $allPermissions = Permission::all();

        // Create Admin role with all permissions
        $adminRole = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $adminRole->syncPermissions($allPermissions);

        // Create Gerente (Manager) role
        $gerenteRole = Role::firstOrCreate(['name' => 'Gerente', 'guard_name' => 'web']);

        // Gerente permissions: view all, create/edit loans, items, customers
        $gerentePermissions = Permission::whereIn('name', [
            // Customer permissions
            'view_customer',
            'view_any_customer',
            'create_customer',
            'update_customer',

            // Item permissions
            'view_item',
            'view_any_item',
            'create_item',
            'update_item',

            // Loan permissions
            'view_loan',
            'view_any_loan',
            'create_loan',
            'update_loan',

            // Payment permissions
            'view_payment',
            'view_any_payment',
            'create_payment',
            'update_payment',

            // Sale permissions
            'view_sale',
            'view_any_sale',
            'create_sale',
            'update_sale',

            // Branch permissions (view only)
            'view_branch',
            'view_any_branch',

            // Widget permissions
            'widget_LoanStatsWidget',
            'widget_LoansChartWidget',
            'widget_RevenueChartWidget',

            // Page permissions
            'page_Reports',
            'page_AppraisalCalculator',
        ])->get();

        $gerenteRole->syncPermissions($gerentePermissions);

        // Create Cajero (Cashier) role
        $cajeroRole = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);

        // Cajero permissions: create loans/sales/payments only, view necessary data
        $cajeroPermissions = Permission::whereIn('name', [
            // Customer permissions (view only)
            'view_customer',
            'view_any_customer',

            // Item permissions (view only)
            'view_item',
            'view_any_item',

            // Loan permissions (create and view)
            'view_loan',
            'view_any_loan',
            'create_loan',

            // Payment permissions (create and view)
            'view_payment',
            'view_any_payment',
            'create_payment',

            // Sale permissions (create and view)
            'view_sale',
            'view_any_sale',
            'create_sale',

            // Branch permissions (view only)
            'view_branch',
            'view_any_branch',

            // Widget permissions
            'widget_LoanStatsWidget',

            // Page permissions
            'page_AppraisalCalculator',
        ])->get();

        $cajeroRole->syncPermissions($cajeroPermissions);

        // Assign Admin role to user ID 1
        $adminUser = User::find(1);
        if ($adminUser) {
            $adminUser->assignRole('Admin');
            $this->command->info('Admin role assigned to user ID 1');
        } else {
            $this->command->warn('User ID 1 not found. Please create a user and assign the Admin role manually.');
        }

        $this->command->info('Roles and permissions have been seeded successfully!');
        $this->command->info('Created roles: Admin, Gerente, Cajero');
    }
}
