<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoanPolicyTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $gerente;
    protected User $cajero;
    protected Branch $branch;
    protected Loan $loan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\RoleSeeder::class);

        $this->branch = Branch::create([
            'name' => 'Test Branch',
            'code' => 'TB1',
            'address' => 'Test Address',
            'phone' => '1234567890',
            'email' => 'test@branch.com',
            'is_active' => true,
        ]);

        // Create users with different roles
        $this->admin = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->admin->assignRole('Admin');

        $this->gerente = User::create([
            'name' => 'Gerente User',
            'email' => 'gerente@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->gerente->assignRole('Gerente');

        $this->cajero = User::create([
            'name' => 'Cajero User',
            'email' => 'cajero@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->cajero->assignRole('Cajero');

        // Create test loan
        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'id_type' => 'DPI',
            'id_number' => '12345678',
        ]);

        $item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $this->loan = Loan::create([
            'loan_number' => 'L-001',
            'customer_id' => $customer->id,
            'item_id' => $item->id,
            'loan_amount' => 500,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'interest_amount' => 50,
            'total_amount' => 550,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);
    }

    #[Test]
    public function admin_can_view_any_loans()
    {
        $this->assertTrue($this->admin->can('viewAny', Loan::class));
    }

    #[Test]
    public function gerente_can_view_any_loans()
    {
        $this->assertTrue($this->gerente->can('viewAny', Loan::class));
    }

    #[Test]
    public function cajero_can_view_any_loans()
    {
        $this->assertTrue($this->cajero->can('viewAny', Loan::class));
    }

    #[Test]
    public function admin_can_view_loan()
    {
        $this->assertTrue($this->admin->can('view', $this->loan));
    }

    #[Test]
    public function gerente_can_view_loan()
    {
        $this->assertTrue($this->gerente->can('view', $this->loan));
    }

    #[Test]
    public function cajero_can_view_loan()
    {
        $this->assertTrue($this->cajero->can('view', $this->loan));
    }

    #[Test]
    public function admin_can_create_loan()
    {
        $this->assertTrue($this->admin->can('create', Loan::class));
    }

    #[Test]
    public function gerente_can_create_loan()
    {
        $this->assertTrue($this->gerente->can('create', Loan::class));
    }

    #[Test]
    public function cajero_can_create_loan()
    {
        $this->assertTrue($this->cajero->can('create', Loan::class));
    }

    #[Test]
    public function admin_can_update_any_loan()
    {
        $this->assertTrue($this->admin->can('update', $this->loan));
    }

    #[Test]
    public function gerente_can_update_any_loan()
    {
        $this->assertTrue($this->gerente->can('update', $this->loan));
    }

    #[Test]
    public function cajero_can_update_loan_created_today()
    {
        // Loan was created today in setUp
        $this->assertTrue($this->cajero->can('update', $this->loan));
    }

    #[Test]
    public function cajero_cannot_update_old_loan()
    {
        // Create an old loan
        $customer = Customer::create([
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'email' => 'jane@example.com',
            'phone' => '0987654321',
            'address' => '456 Test Ave',
            'id_type' => 'DPI',
            'id_number' => '87654321',
        ]);

        $item = Item::create([
            'name' => 'Old Item',
            'category' => 'Electrónica',
            'condition' => 'good',
            'appraised_value' => 500,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $oldLoan = Loan::create([
            'loan_number' => 'L-002',
            'customer_id' => $customer->id,
            'item_id' => $item->id,
            'loan_amount' => 300,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now()->subDays(10),
            'due_date' => now()->addDays(20),
            'interest_amount' => 30,
            'total_amount' => 330,
            'status' => 'active',
            'branch_id' => $this->branch->id,
        ]);

        // Manually update created_at to 10 days ago
        $oldLoan->created_at = now()->subDays(10);
        $oldLoan->saveQuietly();
        $oldLoan->refresh();

        $this->assertFalse($this->cajero->can('update', $oldLoan));
    }

    #[Test]
    public function admin_can_delete_loan()
    {
        $this->assertTrue($this->admin->can('delete', $this->loan));
    }

    #[Test]
    public function gerente_cannot_delete_loan()
    {
        $this->assertFalse($this->gerente->can('delete', $this->loan));
    }

    #[Test]
    public function cajero_cannot_delete_loan()
    {
        $this->assertFalse($this->cajero->can('delete', $this->loan));
    }

    #[Test]
    public function admin_can_delete_any_loans()
    {
        $this->assertTrue($this->admin->can('deleteAny', Loan::class));
    }

    #[Test]
    public function gerente_cannot_delete_any_loans()
    {
        $this->assertFalse($this->gerente->can('deleteAny', Loan::class));
    }

    #[Test]
    public function cajero_cannot_delete_any_loans()
    {
        $this->assertFalse($this->cajero->can('deleteAny', Loan::class));
    }

    #[Test]
    public function admin_can_force_delete_loan()
    {
        $this->assertTrue($this->admin->can('forceDelete', $this->loan));
    }

    #[Test]
    public function gerente_cannot_force_delete_loan()
    {
        $this->assertFalse($this->gerente->can('forceDelete', $this->loan));
    }

    #[Test]
    public function cajero_cannot_force_delete_loan()
    {
        $this->assertFalse($this->cajero->can('forceDelete', $this->loan));
    }

    #[Test]
    public function admin_can_restore_loan()
    {
        $this->assertTrue($this->admin->can('restore', $this->loan));
    }

    #[Test]
    public function gerente_cannot_restore_loan()
    {
        $this->assertFalse($this->gerente->can('restore', $this->loan));
    }

    #[Test]
    public function cajero_cannot_restore_loan()
    {
        $this->assertFalse($this->cajero->can('restore', $this->loan));
    }
}
