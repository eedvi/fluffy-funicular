<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReportGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Branch $branch;
    protected Customer $customer;

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
    }

    #[Test]
    public function it_can_generate_active_loans_report_with_data()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $loan = Loan::create([
            'loan_number' => 'L-001',
            'customer_id' => $this->customer->id,
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

        $this->actingAs($this->user);

        $query = Loan::where('status', 'active')
            ->with(['customer', 'item', 'branch'])
            ->get();

        $this->assertCount(1, $query);
        $this->assertEquals('L-001', $query->first()->loan_number);
        $this->assertNotNull($query->first()->customer);
        $this->assertNotNull($query->first()->item);
    }

    #[Test]
    public function it_returns_empty_collection_for_active_loans_with_no_data()
    {
        $this->actingAs($this->user);

        $loans = Loan::where('status', 'active')
            ->with(['customer', 'item', 'branch'])
            ->get();

        $this->assertCount(0, $loans);
        $this->assertTrue($loans->isEmpty());
    }

    #[Test]
    public function it_can_generate_overdue_loans_report()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $loan = Loan::create([
            'loan_number' => 'L-002',
            'customer_id' => $this->customer->id,
            'item_id' => $item->id,
            'loan_amount' => 500,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now()->subDays(40),
            'due_date' => now()->subDays(10),
            'interest_amount' => 50,
            'total_amount' => 550,
            'status' => 'overdue',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);

        $loans = Loan::whereIn('status', ['active', 'overdue'])
            ->where('due_date', '<', now())
            ->with(['customer', 'item', 'branch'])
            ->get();

        $this->assertCount(1, $loans);
        $this->assertEquals('overdue', $loans->first()->status);
        $this->assertTrue($loans->first()->due_date->isPast());
    }

    #[Test]
    public function it_can_generate_sales_report()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $sale = Sale::create([
            'sale_number' => 'S-001',
            'customer_id' => $this->customer->id,
            'item_id' => $item->id,
            'sale_price' => 800,
            'discount' => 0,
            'final_price' => 800,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'delivered',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);

        $sales = Sale::whereBetween('sale_date', [now()->subDay(), now()->addDay()])
            ->with(['customer', 'item', 'branch'])
            ->get();

        $this->assertCount(1, $sales);
        $this->assertEquals(800, $sales->sum('final_price'));
        $this->assertEquals(0, $sales->sum('discount'));
    }

    #[Test]
    public function it_can_generate_payments_report()
    {
        $item = Item::create([
            'name' => 'Test Item',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $loan = Loan::create([
            'loan_number' => 'L-003',
            'customer_id' => $this->customer->id,
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

        $payment = Payment::create([
            'loan_id' => $loan->id,
            'payment_number' => 'P-001',
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);

        $payments = Payment::whereBetween('payment_date', [now()->subDay(), now()->addDay()])
            ->where('status', 'completed')
            ->with(['loan.customer', 'branch'])
            ->get();

        $this->assertCount(1, $payments);
        $this->assertEquals(100, $payments->sum('amount'));
        $this->assertNotNull($payments->first()->loan->customer);
    }

    #[Test]
    public function it_can_generate_inventory_report()
    {
        Item::create([
            'name' => 'Item 1',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'market_value' => 1200,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        Item::create([
            'name' => 'Item 2',
            'category' => 'Electrónica',
            'condition' => 'good',
            'appraised_value' => 500,
            'market_value' => 600,
            'status' => 'collateral',
            'branch_id' => $this->branch->id,
        ]);

        $this->actingAs($this->user);

        $items = Item::whereIn('status', ['available', 'collateral', 'forfeited'])
            ->with(['branch'])
            ->get();

        $this->assertCount(2, $items);
        $this->assertEquals(1500, $items->sum('appraised_value'));
        $this->assertEquals(1800, $items->sum('market_value'));

        $byCategory = $items->groupBy('category');
        $this->assertCount(2, $byCategory);
    }

    #[Test]
    public function it_filters_reports_by_branch()
    {
        $branch2 = Branch::create([
            'name' => 'Branch 2',
            'code' => 'TB2',
            'address' => 'Address 2',
            'phone' => '0987654321',
            'email' => 'branch2@test.com',
            'is_active' => true,
        ]);

        $item1 = Item::create([
            'name' => 'Item Branch 1',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $this->branch->id,
        ]);

        $item2 = Item::create([
            'name' => 'Item Branch 2',
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 2000,
            'status' => 'available',
            'branch_id' => $branch2->id,
        ]);

        $this->actingAs($this->user);

        // Filter by branch 1
        $items = Item::where('branch_id', $this->branch->id)->get();
        $this->assertCount(1, $items);
        $this->assertEquals('Item Branch 1', $items->first()->name);

        // Filter by branch 2
        $items = Item::where('branch_id', $branch2->id)->get();
        $this->assertCount(1, $items);
        $this->assertEquals('Item Branch 2', $items->first()->name);
    }

    #[Test]
    public function it_loads_relationships_eagerly_to_prevent_n_plus_one()
    {
        // Create multiple loans
        for ($i = 1; $i <= 5; $i++) {
            $item = Item::create([
                'name' => "Item $i",
                'category' => 'Joyería',
                'condition' => 'excellent',
                'appraised_value' => 1000,
                'status' => 'available',
                'branch_id' => $this->branch->id,
            ]);

            Loan::create([
                'loan_number' => "L-00$i",
                'customer_id' => $this->customer->id,
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

        $this->actingAs($this->user);

        // Enable query log
        \DB::enableQueryLog();

        $loans = Loan::where('status', 'active')
            ->with(['customer', 'item', 'branch'])
            ->get();

        // Access relationships to trigger queries if not eager loaded
        foreach ($loans as $loan) {
            $loan->customer->full_name;
            $loan->item->name;
            $loan->branch->name;
        }

        $queries = \DB::getQueryLog();
        \DB::disableQueryLog();

        // With eager loading: 1 query for loans + 1 for customers + 1 for items + 1 for branches = 4 queries
        // Without eager loading: 1 query for loans + 5*3 = 16 queries (N+1 problem)
        $this->assertLessThan(10, count($queries), 'N+1 query problem detected');
    }
}
