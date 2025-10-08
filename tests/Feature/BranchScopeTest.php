<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class BranchScopeTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $gerenteUser;
    protected User $cajeroUser;
    protected Branch $branch1;
    protected Branch $branch2;

    protected function setUp(): void
    {
        parent::setUp();

        // Create branches
        $this->branch1 = Branch::create([
            'name' => 'Branch 1',
            'code' => 'BR1',
            'address' => 'Address 1',
            'phone' => '1111111111',
            'email' => 'branch1@test.com',
            'is_active' => true,
        ]);

        $this->branch2 = Branch::create([
            'name' => 'Branch 2',
            'code' => 'BR2',
            'address' => 'Address 2',
            'phone' => '2222222222',
            'email' => 'branch2@test.com',
            'is_active' => true,
        ]);

        // Create roles with permissions
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // Create users with different roles
        $this->adminUser = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);
        $this->adminUser->assignRole('Admin');

        $this->gerenteUser = User::create([
            'name' => 'Gerente User',
            'email' => 'gerente@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);
        $this->gerenteUser->assignRole('Gerente');

        $this->cajeroUser = User::create([
            'name' => 'Cajero User',
            'email' => 'cajero@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch1->id,
            'is_active' => true,
        ]);
        $this->cajeroUser->assignRole('Cajero');
    }

    #[Test]
    public function admin_can_see_all_branches_items()
    {
        // Create items in both branches
        $item1 = Item::create([
            'name' => 'Item Branch 1',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 1000,
            'status' => 'Disponible',
            'branch_id' => $this->branch1->id,
        ]);

        $item2 = Item::create([
            'name' => 'Item Branch 2',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 2000,
            'status' => 'Disponible',
            'branch_id' => $this->branch2->id,
        ]);

        // Login as admin
        $this->actingAs($this->adminUser);

        // Admin should see all items
        $items = Item::all();
        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('id', $item1->id));
        $this->assertTrue($items->contains('id', $item2->id));
    }

    #[Test]
    public function gerente_can_see_all_branches_items()
    {
        // Create items in both branches
        $item1 = Item::create([
            'name' => 'Item Branch 1',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 1000,
            'status' => 'Disponible',
            'branch_id' => $this->branch1->id,
        ]);

        $item2 = Item::create([
            'name' => 'Item Branch 2',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 2000,
            'status' => 'Disponible',
            'branch_id' => $this->branch2->id,
        ]);

        // Login as gerente
        $this->actingAs($this->gerenteUser);

        // Gerente should see all items (has view_all_branches permission)
        $items = Item::all();
        $this->assertCount(2, $items);
        $this->assertTrue($items->contains('id', $item1->id));
        $this->assertTrue($items->contains('id', $item2->id));
    }

    #[Test]
    public function cajero_can_only_see_their_branch_items()
    {
        // Create items in both branches
        $item1 = Item::create([
            'name' => 'Item Branch 1',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 1000,
            'status' => 'Disponible',
            'branch_id' => $this->branch1->id,
        ]);

        $item2 = Item::create([
            'name' => 'Item Branch 2',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 2000,
            'status' => 'Disponible',
            'branch_id' => $this->branch2->id,
        ]);

        // Login as cajero (branch 1)
        $this->actingAs($this->cajeroUser);

        // Cajero should only see items from their branch
        $items = Item::all();
        $this->assertCount(1, $items);
        $this->assertTrue($items->contains('id', $item1->id));
        $this->assertFalse($items->contains('id', $item2->id));
    }

    #[Test]
    public function cajero_can_only_see_their_branch_loans()
    {
        // Create a customer
        $customer = \App\Models\Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@test.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'id_type' => 'DPI',
            'id_number' => '12345678',
        ]);

        // Create items (Disponible for loans)
        $item1 = Item::create([
            'name' => 'Item Branch 1',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 1000,
            'status' => 'Disponible',
            'branch_id' => $this->branch1->id,
        ]);

        $item2 = Item::create([
            'name' => 'Item Branch 2',
            'category' => 'Joyería',
            'condition' => 'Excelente',
            'appraised_value' => 2000,
            'status' => 'Disponible',
            'branch_id' => $this->branch2->id,
        ]);

        // Create loans (without balance_remaining - it's a generated column)
        $loan1 = Loan::create([
            'loan_number' => 'L-001',
            'customer_id' => $customer->id,
            'item_id' => $item1->id,
            'loan_amount' => 500,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'interest_amount' => 50,
            'total_amount' => 550,
            'status' => 'Activo',
            'branch_id' => $this->branch1->id,
        ]);

        $loan2 = Loan::create([
            'loan_number' => 'L-002',
            'customer_id' => $customer->id,
            'item_id' => $item2->id,
            'loan_amount' => 1000,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'interest_amount' => 100,
            'total_amount' => 1100,
            'status' => 'Activo',
            'branch_id' => $this->branch2->id,
        ]);

        // Login as cajero (branch 1)
        $this->actingAs($this->cajeroUser);

        // Cajero should only see loans from their branch
        $loans = Loan::all();
        $this->assertCount(1, $loans);
        $this->assertTrue($loans->contains('id', $loan1->id));
        $this->assertFalse($loans->contains('id', $loan2->id));
    }

    #[Test]
    public function gerente_has_view_all_branches_permission()
    {
        $this->actingAs($this->gerenteUser);
        $this->assertTrue($this->gerenteUser->can('view_all_branches'));
    }

    #[Test]
    public function cajero_does_not_have_view_all_branches_permission()
    {
        $this->actingAs($this->cajeroUser);
        $this->assertFalse($this->cajeroUser->can('view_all_branches'));
    }

    #[Test]
    public function admin_has_view_all_branches_permission()
    {
        $this->actingAs($this->adminUser);
        $this->assertTrue($this->adminUser->can('view_all_branches'));
    }
}
