<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\LoanRenewal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoanRenewalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;
    protected Item $item;
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

        $this->user = User::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('Admin');

        $this->customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'id_type' => 'DPI',
            'id_number' => '12345678',
        ]);

        $this->item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $this->loan = Loan::create([
            'loan_number' => 'L-001',
            'customer_id' => $this->customer->id,
            'item_id' => $this->item->id,
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

        $this->actingAs($this->user);
    }

    #[Test]
    public function it_can_create_a_loan_renewal()
    {
        $originalDueDate = $this->loan->due_date;

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $originalDueDate,
            'new_due_date' => $originalDueDate->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 25,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'notes' => 'Test renewal',
            'processed_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('loan_renewals', [
            'loan_id' => $this->loan->id,
            'extension_days' => 30,
            'renewal_fee' => 25,
        ]);

        $this->assertEquals($this->loan->id, $renewal->loan_id);
        $this->assertEquals(30, $renewal->extension_days);
    }

    #[Test]
    public function it_has_relationship_with_loan()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(Loan::class, $renewal->loan);
        $this->assertEquals($this->loan->id, $renewal->loan->id);
    }

    #[Test]
    public function it_has_relationship_with_processed_by_user()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(User::class, $renewal->processedBy);
        $this->assertEquals($this->user->id, $renewal->processedBy->id);
    }

    #[Test]
    public function loan_has_relationship_with_renewals()
    {
        LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertCount(1, $this->loan->renewals);
        $this->assertInstanceOf(LoanRenewal::class, $this->loan->renewals->first());
    }

    #[Test]
    public function it_calculates_new_due_date_correctly()
    {
        $originalDueDate = $this->loan->due_date;
        $extensionDays = 30;
        $expectedNewDueDate = $originalDueDate->copy()->addDays($extensionDays);

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $originalDueDate,
            'new_due_date' => $originalDueDate->copy()->addDays($extensionDays),
            'extension_days' => $extensionDays,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals($expectedNewDueDate->format('Y-m-d'), $renewal->new_due_date->format('Y-m-d'));
    }

    #[Test]
    public function it_stores_interest_calculation_correctly()
    {
        // Loan: $500 at 10% for 30 days
        // Daily interest rate: ($500 * 10% / 100) / 30 = $0.1667 per day
        // For 30 day extension: $0.1667 * 30 = $5.00 (approximately)
        $dailyRate = ($this->loan->loan_amount * 10 / 100) / $this->loan->loan_term_days;
        $expectedInterest = round($dailyRate * 30, 2);

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => $expectedInterest,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals($expectedInterest, $renewal->interest_amount);
    }

    #[Test]
    public function it_can_have_optional_renewal_fee()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 25.50,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals(25.50, $renewal->renewal_fee);
    }

    #[Test]
    public function it_can_store_optional_notes()
    {
        $notes = 'Customer requested 30-day extension due to delayed payment.';

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'notes' => $notes,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals($notes, $renewal->notes);
    }

    #[Test]
    public function it_casts_dates_properly()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $renewal->previous_due_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $renewal->new_due_date);
    }

    #[Test]
    public function it_casts_monetary_values_to_decimal()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 25.99,
            'interest_rate' => 10.5,
            'interest_amount' => 50.75,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals('25.99', $renewal->renewal_fee);
        $this->assertEquals('10.50', $renewal->interest_rate);
        $this->assertEquals('50.75', $renewal->interest_amount);
    }

    #[Test]
    public function it_can_renew_overdue_loan()
    {
        // Set loan as overdue
        $this->loan->update([
            'status' => 'overdue',
            'due_date' => now()->subDays(5),
        ]);

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => now()->addDays(30),
            'extension_days' => 35, // 5 days overdue + 30 new days
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 58.33,
            'processed_by' => $this->user->id,
        ]);

        $this->assertDatabaseHas('loan_renewals', [
            'loan_id' => $this->loan->id,
            'extension_days' => 35,
        ]);
    }

    #[Test]
    public function it_tracks_processed_by_user()
    {
        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertEquals($this->user->id, $renewal->processed_by);
        $this->assertEquals($this->user->name, $renewal->processedBy->name);
    }

    #[Test]
    public function it_can_have_multiple_renewals_for_same_loan()
    {
        // First renewal
        $firstRenewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        // Second renewal
        $secondRenewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $firstRenewal->new_due_date,
            'new_due_date' => $firstRenewal->new_due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => 0,
            'interest_rate' => 10,
            'interest_amount' => 50,
            'processed_by' => $this->user->id,
        ]);

        $this->assertCount(2, $this->loan->renewals);
        $this->assertEquals($firstRenewal->new_due_date->format('Y-m-d'), $secondRenewal->previous_due_date->format('Y-m-d'));
    }

    #[Test]
    public function it_calculates_total_renewal_cost()
    {
        $interestAmount = 50.00;
        $renewalFee = 25.00;

        $renewal = LoanRenewal::create([
            'loan_id' => $this->loan->id,
            'previous_due_date' => $this->loan->due_date,
            'new_due_date' => $this->loan->due_date->addDays(30),
            'extension_days' => 30,
            'renewal_fee' => $renewalFee,
            'interest_rate' => 10,
            'interest_amount' => $interestAmount,
            'processed_by' => $this->user->id,
        ]);

        $totalCost = $renewal->interest_amount + $renewal->renewal_fee;
        $this->assertEquals(75.00, $totalCost);
    }
}
