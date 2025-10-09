<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\InterestCharge;
use App\Models\Item;
use App\Models\Loan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InterestChargeTest extends TestCase
{
    use RefreshDatabase;

    protected Branch $branch;
    protected Customer $customer;
    protected Item $item;
    protected Loan $loan;
    protected User $user;

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

        $this->customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address' => '123 Test St',
            'identity_type' => 'DPI',
            'identity_number' => '12345678',
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
            'status' => 'overdue',
            'branch_id' => $this->branch->id,
            'current_balance' => 550,
        ]);

        $this->user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
            'branch_id' => $this->branch->id,
            'is_active' => true,
        ]);
        $this->user->assignRole('Admin');
    }

    #[Test]
    public function it_can_create_an_interest_charge()
    {
        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'notes' => 'Test interest charge',
            'is_applied' => true,
        ]);

        $this->assertDatabaseHas('interest_charges', [
            'loan_id' => $this->loan->id,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);
    }

    #[Test]
    public function it_has_relationship_with_loan()
    {
        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertInstanceOf(Loan::class, $interestCharge->loan);
        $this->assertEquals($this->loan->id, $interestCharge->loan->id);
    }

    #[Test]
    public function loan_has_relationship_with_interest_charges()
    {
        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $this->loan->interestCharges);
        $this->assertEquals(1, $this->loan->interestCharges->count());
        $this->assertEquals($interestCharge->id, $this->loan->interestCharges->first()->id);
    }

    #[Test]
    public function it_calculates_balance_after_correctly()
    {
        $balanceBefore = 550;
        $interestAmount = 8.33;
        $expectedBalanceAfter = 558.33;

        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => $interestAmount,
            'balance_before' => $balanceBefore,
            'balance_after' => $expectedBalanceAfter,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertEquals($expectedBalanceAfter, $interestCharge->balance_after);
    }

    #[Test]
    public function it_stores_charge_type_correctly()
    {
        $chargeTypes = ['daily', 'overdue', 'penalty', 'late_fee'];

        foreach ($chargeTypes as $type) {
            $interestCharge = InterestCharge::create([
                'loan_id' => $this->loan->id,
                'charge_date' => now(),
                'days_overdue' => 5,
                'interest_rate' => 10,
                'principal_amount' => 500,
                'interest_amount' => 8.33,
                'balance_before' => 550,
                'balance_after' => 558.33,
                'charge_type' => $type,
                'is_applied' => true,
            ]);

            $this->assertEquals($type, $interestCharge->charge_type);
        }
    }

    #[Test]
    public function it_can_store_optional_notes()
    {
        $notes = 'This is a test note for interest charge';

        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'notes' => $notes,
            'is_applied' => true,
        ]);

        $this->assertEquals($notes, $interestCharge->notes);
    }

    #[Test]
    public function it_casts_charge_date_properly()
    {
        $chargeDate = now();

        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => $chargeDate,
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $interestCharge->charge_date);
        $this->assertEquals($chargeDate->format('Y-m-d'), $interestCharge->charge_date->format('Y-m-d'));
    }

    #[Test]
    public function it_casts_monetary_values_to_decimal()
    {
        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10.50,
            'principal_amount' => 500.75,
            'interest_amount' => 8.33,
            'balance_before' => 550.50,
            'balance_after' => 558.83,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertEquals('10.50', $interestCharge->interest_rate);
        $this->assertEquals('500.75', $interestCharge->principal_amount);
        $this->assertEquals('8.33', $interestCharge->interest_amount);
        $this->assertEquals('550.50', $interestCharge->balance_before);
        $this->assertEquals('558.83', $interestCharge->balance_after);
    }

    #[Test]
    public function it_can_have_multiple_interest_charges_for_same_loan()
    {
        $charge1 = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $charge2 = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now()->addDay(),
            'days_overdue' => 6,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 558.33,
            'balance_after' => 566.66,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertEquals(2, $this->loan->interestCharges()->count());
        $this->assertTrue($this->loan->interestCharges->contains($charge1));
        $this->assertTrue($this->loan->interestCharges->contains($charge2));
    }

    #[Test]
    public function it_tracks_is_applied_status()
    {
        $appliedCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $unappliedCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 558.33,
            'balance_after' => 566.66,
            'charge_type' => 'overdue',
            'is_applied' => false,
        ]);

        $this->assertTrue($appliedCharge->is_applied);
        $this->assertFalse($unappliedCharge->is_applied);
    }

    #[Test]
    public function it_calculates_daily_interest_correctly()
    {
        $principalAmount = 500;
        $interestRate = 10; // 10% annual
        $daysOverdue = 5;

        // Formula: (principal × rate% / 100) / 30 × days
        $dailyInterest = ($principalAmount * $interestRate / 100) / 30;
        $totalInterest = $dailyInterest * $daysOverdue;
        $expectedInterest = round($totalInterest, 2); // 8.33

        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => $daysOverdue,
            'interest_rate' => $interestRate,
            'principal_amount' => $principalAmount,
            'interest_amount' => $expectedInterest,
            'balance_before' => 550,
            'balance_after' => 550 + $expectedInterest,
            'charge_type' => 'daily',
            'is_applied' => true,
        ]);

        $this->assertEquals($expectedInterest, (float) $interestCharge->interest_amount);
    }

    #[Test]
    public function it_can_waive_interest_charge_by_marking_as_not_applied()
    {
        $interestCharge = InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $this->assertTrue($interestCharge->is_applied);

        // Waive the charge
        $interestCharge->update(['is_applied' => false, 'notes' => 'Waived by manager']);

        $this->assertFalse($interestCharge->fresh()->is_applied);
        $this->assertStringContainsString('Waived', $interestCharge->fresh()->notes);
    }

    #[Test]
    public function it_calculates_total_interest_charged_for_loan()
    {
        InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now(),
            'days_overdue' => 5,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 8.33,
            'balance_before' => 550,
            'balance_after' => 558.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        InterestCharge::create([
            'loan_id' => $this->loan->id,
            'charge_date' => now()->addDay(),
            'days_overdue' => 6,
            'interest_rate' => 10,
            'principal_amount' => 500,
            'interest_amount' => 10.00,
            'balance_before' => 558.33,
            'balance_after' => 568.33,
            'charge_type' => 'overdue',
            'is_applied' => true,
        ]);

        $totalInterestCharged = $this->loan->interestCharges()->sum('interest_amount');

        $this->assertEquals(18.33, $totalInterestCharged);
    }
}
