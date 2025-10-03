<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;
    protected Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->item = Item::factory()->create(['status' => 'available']);
    }

    public function test_it_can_create_a_loan(): void
    {
        $this->actingAs($this->user);

        $loanData = [
            'loan_number' => Loan::generateLoanNumber(),
            'customer_id' => $this->customer->id,
            'item_id' => $this->item->id,
            'branch_id' => $this->branch->id,
            'loan_amount' => 800.00,
            'interest_rate' => 10.00,
            'interest_rate_overdue' => 15.00,
            'interest_amount' => 80.00,
            'total_amount' => 880.00,
            'amount_paid' => 0.00,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'active',
        ];

        $loan = Loan::create($loanData);

        $this->assertDatabaseHas('loans', [
            'customer_id' => $this->customer->id,
            'item_id' => $this->item->id,
            'loan_amount' => 800.00,
            'status' => 'active',
        ]);
    }

    public function test_it_can_update_loan_status(): void
    {
        $this->actingAs($this->user);

        $loan = Loan::factory()->create([
            'status' => 'active',
        ]);

        $loan->update(['status' => 'paid']);

        $this->assertDatabaseHas('loans', [
            'id' => $loan->id,
            'status' => 'paid',
        ]);
    }

    public function test_it_detects_overdue_loans(): void
    {
        $this->actingAs($this->user);

        $overdueLoan = Loan::factory()->create([
            'due_date' => now()->subDays(10),
            'status' => 'active',
        ]);

        $this->assertTrue($overdueLoan->is_overdue);
    }

    public function test_it_can_extend_loan_due_date(): void
    {
        $this->actingAs($this->user);

        $loan = Loan::factory()->create([
            'due_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $newDueDate = now()->addDays(40);
        $loan->update(['due_date' => $newDueDate]);

        $this->assertEquals($newDueDate->format('Y-m-d'), $loan->due_date->format('Y-m-d'));
    }

    public function test_it_tracks_loan_balance(): void
    {
        $this->actingAs($this->user);

        $loan = Loan::factory()->create([
            'loan_amount' => 1000.00,
            'interest_rate' => 10.00,
            'interest_rate_overdue' => 15.00,
            'interest_amount' => 100.00,
            'total_amount' => 1100.00,
            'amount_paid' => 0.00,
        ]);

        $loan->refresh();

        $this->assertEquals(1100.00, $loan->balance_remaining);
    }

    public function test_it_can_retrieve_active_loans(): void
    {
        $this->actingAs($this->user);

        Loan::factory()->count(3)->create(['status' => 'active']);
        Loan::factory()->count(2)->create(['status' => 'paid']);

        $activeLoans = Loan::where('status', 'active')->get();

        $this->assertCount(3, $activeLoans);
    }

    public function test_it_generates_unique_loan_numbers(): void
    {
        $this->actingAs($this->user);

        $loanNumber1 = Loan::generateLoanNumber();
        $loanNumber2 = Loan::generateLoanNumber();

        // Both should start with L-
        $this->assertStringStartsWith('L-', $loanNumber1);
        $this->assertStringStartsWith('L-', $loanNumber2);
    }

    public function test_it_belongs_to_customer_and_item(): void
    {
        $this->actingAs($this->user);

        $loan = Loan::factory()->create();

        $this->assertInstanceOf(Customer::class, $loan->customer);
        $this->assertInstanceOf(Item::class, $loan->item);
        $this->assertInstanceOf(Branch::class, $loan->branch);
    }
}
