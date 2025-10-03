<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaymentProcessingTest extends TestCase
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

        $this->user = User::factory()->create();
        $this->branch = Branch::factory()->create();
        $this->customer = Customer::factory()->create();
        $this->item = Item::factory()->create(['status' => 'available']);

        $this->loan = Loan::factory()->create([
            'customer_id' => $this->customer->id,
            'item_id' => $this->item->id,
            'branch_id' => $this->branch->id,
            'loan_amount' => 1000.00,
            'interest_rate' => 10.00,
            'interest_rate_overdue' => 15.00,
            'interest_amount' => 100.00,
            'total_amount' => 1100.00,
            'amount_paid' => 0.00,
            'status' => 'active',
        ]);
    }

    public function test_it_can_create_a_payment(): void
    {
        $this->actingAs($this->user);

        $paymentData = [
            'payment_number' => Payment::generatePaymentNumber(),
            'loan_id' => $this->loan->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => 'Partial payment',
        ];

        $payment = Payment::create($paymentData);

        $this->assertDatabaseHas('payments', [
            'loan_id' => $this->loan->id,
            'amount' => 500.00,
            'payment_method' => 'cash',
        ]);
    }

    public function test_it_supports_different_payment_methods(): void
    {
        $this->actingAs($this->user);

        $paymentMethods = ['cash', 'transfer', 'card'];

        foreach ($paymentMethods as $index => $method) {
            $payment = Payment::create([
                'payment_number' => 'P-TEST-PM-' . now()->format('YmdHis') . '-' . $index,
                'loan_id' => $this->loan->id,
                'branch_id' => $this->branch->id,
                'amount' => 100.00,
                'payment_date' => now(),
                'payment_method' => $method,
                'status' => 'completed',
            ]);

            $this->assertEquals($method, $payment->payment_method);
        }
    }

    public function test_it_tracks_payment_history(): void
    {
        $this->actingAs($this->user);

        for ($i = 1; $i <= 3; $i++) {
            Payment::create([
                'payment_number' => 'P-TEST-HIST-' . now()->format('YmdHis') . '-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'loan_id' => $this->loan->id,
                'branch_id' => $this->branch->id,
                'amount' => 100.00 * $i,
                'payment_date' => now()->addDays($i),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
            usleep(1000);
        }

        $this->assertEquals(3, $this->loan->payments()->count());
        $this->assertEquals(600.00, $this->loan->payments()->sum('amount'));
    }

    public function test_it_can_add_payment_notes(): void
    {
        $this->actingAs($this->user);

        $notes = 'Customer paid early with bonus';

        $payment = Payment::create([
            'payment_number' => Payment::generatePaymentNumber(),
            'loan_id' => $this->loan->id,
            'branch_id' => $this->branch->id,
            'amount' => 500.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $payment->notes);
    }

    public function test_it_belongs_to_loan(): void
    {
        $this->actingAs($this->user);

        $payment = Payment::factory()->create([
            'loan_id' => $this->loan->id,
        ]);

        $this->assertInstanceOf(Loan::class, $payment->loan);
        $this->assertEquals($this->loan->id, $payment->loan->id);
    }

    public function test_it_generates_unique_payment_numbers(): void
    {
        $this->actingAs($this->user);

        $paymentNumber1 = Payment::generatePaymentNumber();
        $paymentNumber2 = Payment::generatePaymentNumber();

        $this->assertStringStartsWith('P-', $paymentNumber1);
        $this->assertStringStartsWith('P-', $paymentNumber2);
    }

    public function test_it_can_filter_payments_by_date(): void
    {
        $this->actingAs($this->user);

        Payment::create([
            'payment_number' => 'P-TEST-DATE-1-' . now()->format('YmdHis'),
            'loan_id' => Loan::factory()->create()->id,
            'branch_id' => $this->branch->id,
            'amount' => 100.00,
            'payment_date' => now()->subDays(10),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        usleep(1000);

        Payment::create([
            'payment_number' => 'P-TEST-DATE-2-' . now()->format('YmdHis'),
            'loan_id' => Loan::factory()->create()->id,
            'branch_id' => $this->branch->id,
            'amount' => 100.00,
            'payment_date' => now()->subDays(5),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);
        usleep(1000);

        Payment::create([
            'payment_number' => 'P-TEST-DATE-3-' . now()->format('YmdHis'),
            'loan_id' => Loan::factory()->create()->id,
            'branch_id' => $this->branch->id,
            'amount' => 100.00,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $recentPayments = Payment::where('payment_date', '>=', now()->subDays(7))->get();

        $this->assertCount(2, $recentPayments);
    }
}
