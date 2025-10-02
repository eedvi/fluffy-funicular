<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create admin role
        $adminRole = Role::create(['name' => 'admin']);

        // Create permissions
        $permissions = [
            'view_customers',
            'create_customers',
            'edit_customers',
            'delete_customers',
            'view_items',
            'create_items',
            'edit_items',
            'delete_items',
            'view_loans',
            'create_loans',
            'edit_loans',
            'delete_loans',
            'view_payments',
            'create_payments',
            'edit_payments',
            'delete_payments',
            'view_sales',
            'create_sales',
            'edit_sales',
            'delete_sales',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Assign all permissions to admin role
        $adminRole->givePermissionTo(Permission::all());

        // Create admin user
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);

        // Assign admin role to user
        $admin->assignRole('admin');
    }
}
