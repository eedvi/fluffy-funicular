<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RolePermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed roles and permissions
        $this->seed(\Database\Seeders\RoleSeeder::class);
    }

    #[Test]
    public function admin_role_has_all_permissions()
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $allPermissions = Permission::count();

        $this->assertNotNull($adminRole);
        $this->assertEquals($allPermissions, $adminRole->permissions()->count());
    }

    #[Test]
    public function gerente_role_has_loan_renewal_permissions()
    {
        $gerenteRole = Role::where('name', 'Gerente')->first();

        $this->assertNotNull($gerenteRole);
        $this->assertTrue($gerenteRole->hasPermissionTo('view_loanrenewal'));
        $this->assertTrue($gerenteRole->hasPermissionTo('view_any_loanrenewal'));
        $this->assertTrue($gerenteRole->hasPermissionTo('create_loanrenewal'));
        $this->assertTrue($gerenteRole->hasPermissionTo('update_loanrenewal'));
    }

    #[Test]
    public function cajero_role_has_loan_renewal_permissions()
    {
        $cajeroRole = Role::where('name', 'Cajero')->first();

        $this->assertNotNull($cajeroRole);
        $this->assertTrue($cajeroRole->hasPermissionTo('view_loanrenewal'));
        $this->assertTrue($cajeroRole->hasPermissionTo('view_any_loanrenewal'));
        $this->assertTrue($cajeroRole->hasPermissionTo('create_loanrenewal'));
        $this->assertTrue($cajeroRole->hasPermissionTo('update_loanrenewal'));
    }

    #[Test]
    public function all_loanrenewal_permissions_are_created()
    {
        $expectedPermissions = [
            'view_loanrenewal',
            'view_any_loanrenewal',
            'create_loanrenewal',
            'update_loanrenewal',
            'delete_loanrenewal',
            'delete_any_loanrenewal',
            'force_delete_loanrenewal',
            'force_delete_any_loanrenewal',
            'restore_loanrenewal',
            'restore_any_loanrenewal',
            'replicate_loanrenewal',
        ];

        foreach ($expectedPermissions as $permission) {
            $this->assertDatabaseHas('permissions', [
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->assertEquals(
            count($expectedPermissions),
            Permission::where('name', 'like', '%loanrenewal%')->count()
        );
    }

    #[Test]
    public function gerente_does_not_have_delete_loan_renewal_permissions()
    {
        $gerenteRole = Role::where('name', 'Gerente')->first();

        $this->assertFalse($gerenteRole->hasPermissionTo('delete_loanrenewal'));
        $this->assertFalse($gerenteRole->hasPermissionTo('delete_any_loanrenewal'));
        $this->assertFalse($gerenteRole->hasPermissionTo('force_delete_loanrenewal'));
    }

    #[Test]
    public function cajero_does_not_have_delete_loan_renewal_permissions()
    {
        $cajeroRole = Role::where('name', 'Cajero')->first();

        $this->assertFalse($cajeroRole->hasPermissionTo('delete_loanrenewal'));
        $this->assertFalse($cajeroRole->hasPermissionTo('delete_any_loanrenewal'));
        $this->assertFalse($cajeroRole->hasPermissionTo('force_delete_loanrenewal'));
    }

    #[Test]
    public function three_roles_exist()
    {
        $this->assertEquals(3, Role::count());

        $this->assertDatabaseHas('roles', ['name' => 'Admin']);
        $this->assertDatabaseHas('roles', ['name' => 'Gerente']);
        $this->assertDatabaseHas('roles', ['name' => 'Cajero']);
    }
}
