<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoanTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $branch = Branch::factory()->create();
        $customer = Customer::factory()->create();
        $item = Item::factory()->create(['status' => 'available']);

        $loan = Loan::create([
            'loan_number' => 'L-' . now()->format('Ymd') . '-0001',
            'customer_id' => $customer->id,
            'item_id' => $item->id,
            'branch_id' => $branch->id,
            'loan_amount' => 1000.00,
            'interest_rate' => 10.00,
            'interest_rate_overdue' => 15.00,
            'interest_amount' => 100.00,
            'total_amount' => 1100.00,
            'amount_paid' => 0.00,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->assertEquals(1000.00, $loan->loan_amount);
        $this->assertEquals(10.00, $loan->interest_rate);
        $this->assertEquals('active', $loan->status);
    }

    public function test_it_belongs_to_customer(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $loan->customer()
        );
    }

    public function test_it_belongs_to_item(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $loan->item()
        );
    }

    public function test_it_belongs_to_branch(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $loan->branch()
        );
    }

    public function test_it_has_payments_relationship(): void
    {
        $loan = Loan::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $loan->payments()
        );
    }

    public function test_it_detects_overdue_loans(): void
    {
        $overdueLoan = Loan::factory()->create([
            'due_date' => now()->subDays(10),
            'status' => 'active',
        ]);

        $activeLoan = Loan::factory()->create([
            'due_date' => now()->addDays(10),
            'status' => 'active',
        ]);

        $this->assertTrue($overdueLoan->is_overdue);
        $this->assertFalse($activeLoan->is_overdue);
    }

    public function test_it_casts_dates_properly(): void
    {
        $loan = Loan::factory()->create([
            'start_date' => '2025-01-01',
            'due_date' => '2025-02-01',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $loan->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $loan->due_date);
    }

    public function test_it_casts_monetary_values_to_decimal(): void
    {
        $loan = Loan::factory()->create([
            'loan_amount' => 1500.50,
            'interest_rate' => 12.75,
        ]);

        // Decimal cast may return string, so check numeric equality
        $this->assertEquals('1500.50', (string)$loan->loan_amount);
        $this->assertEquals('12.75', (string)$loan->interest_rate);
    }

    public function test_it_uses_activity_log(): void
    {
        $loan = Loan::factory()->create();

        $this->assertNotEmpty($loan->activities);
    }

    public function test_it_soft_deletes(): void
    {
        $loan = Loan::factory()->create();
        $id = $loan->id;

        $loan->delete();

        $this->assertSoftDeleted('loans', ['id' => $id]);
    }

    public function test_it_tracks_balance_remaining(): void
    {
        $loan = Loan::factory()->create([
            'loan_amount' => 1000.00,
            'interest_amount' => 100.00,
            'total_amount' => 1100.00,
            'amount_paid' => 0.00,
        ]);

        // balance_remaining is a computed column: total_amount - amount_paid
        // We need to refresh to get the computed value
        $loan->refresh();

        $this->assertEquals(1100.00, $loan->balance_remaining);
    }
}
