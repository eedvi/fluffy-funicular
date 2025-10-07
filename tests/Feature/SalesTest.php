<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesTest extends TestCase
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

    public function test_it_can_create_a_sale(): void
    {
        $this->actingAs($this->user);

        $salePrice = 1200.00;
        $discount = 0.00;

        $saleData = [
            'sale_number' => Sale::generateSaleNumber(),
            'item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_price' => $salePrice,
            'discount' => $discount,
            'final_price' => $salePrice - $discount,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ];

        $sale = Sale::create($saleData);

        $this->assertDatabaseHas('sales', [
            'item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'sale_price' => 1200.00,
        ]);
    }

    public function test_it_supports_different_payment_methods(): void
    {
        $this->actingAs($this->user);

        $paymentMethods = ['cash', 'transfer', 'card'];

        foreach ($paymentMethods as $index => $method) {
            $item = Item::factory()->create(['status' => 'available']);

            $sale = Sale::create([
                'sale_number' => 'S-TEST-PM-' . now()->format('YmdHis') . '-' . $index,
                'item_id' => $item->id,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'sale_price' => 1000.00,
                'discount' => 0.00,
                'final_price' => 1000.00,
                'sale_date' => now(),
                'payment_method' => $method,
                'status' => 'completed',
            ]);

            $this->assertEquals($method, $sale->payment_method);
        }
    }

    public function test_it_can_apply_discount(): void
    {
        $this->actingAs($this->user);

        $salePrice = 1000.00;
        $discount = 100.00;

        $sale = Sale::create([
            'sale_number' => Sale::generateSaleNumber(),
            'item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_price' => $salePrice,
            'discount' => $discount,
            'final_price' => $salePrice - $discount,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
        ]);

        $this->assertEquals(900.00, $sale->final_price);
    }

    public function test_it_can_add_sale_notes(): void
    {
        $this->actingAs($this->user);

        $notes = 'Customer requested special packaging';

        $sale = Sale::create([
            'sale_number' => Sale::generateSaleNumber(),
            'item_id' => $this->item->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_price' => 1200.00,
            'discount' => 0.00,
            'final_price' => 1200.00,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'notes' => $notes,
        ]);

        $this->assertEquals($notes, $sale->notes);
    }

    public function test_it_tracks_sales_by_branch(): void
    {
        $this->actingAs($this->user);

        $branch2 = Branch::factory()->create();

        Sale::factory()->create([
            'branch_id' => $this->branch->id,
        ]);

        Sale::factory()->create([
            'branch_id' => $branch2->id,
        ]);

        $this->assertEquals(1, Sale::where('branch_id', $this->branch->id)->count());
        $this->assertEquals(1, Sale::where('branch_id', $branch2->id)->count());
    }

    public function test_it_tracks_customer_purchase_history(): void
    {
        $this->actingAs($this->user);

        for ($i = 0; $i < 3; $i++) {
            $item = Item::factory()->create(['status' => 'available']);

            Sale::create([
                'sale_number' => 'S-TEST-' . now()->format('YmdHis') . '-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'item_id' => $item->id,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'sale_price' => 1000.00 + ($i * 100),
                'discount' => 0.00,
                'final_price' => 1000.00 + ($i * 100),
                'sale_date' => now(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
            usleep(1000); // Wait 1ms between creations to ensure unique timestamps
        }

        $this->assertEquals(3, Sale::where('customer_id', $this->customer->id)->count());
        $this->assertEquals(3300.00, Sale::where('customer_id', $this->customer->id)->sum('final_price'));
    }

    public function test_it_generates_unique_sale_numbers(): void
    {
        $this->actingAs($this->user);

        $saleNumber1 = Sale::generateSaleNumber();
        $saleNumber2 = Sale::generateSaleNumber();

        $this->assertStringStartsWith('S-', $saleNumber1);
        $this->assertStringStartsWith('S-', $saleNumber2);
    }

    public function test_it_belongs_to_customer_and_item(): void
    {
        $this->actingAs($this->user);

        $sale = Sale::factory()->create();

        $this->assertInstanceOf(Customer::class, $sale->customer);
        $this->assertInstanceOf(Item::class, $sale->item);
        $this->assertInstanceOf(Branch::class, $sale->branch);
    }

    public function test_it_can_filter_sales_by_status(): void
    {
        $this->actingAs($this->user);

        // Create sales with unique sale numbers
        for ($i = 0; $i < 2; $i++) {
            Sale::create([
                'sale_number' => 'S-FILTER-COMP-' . now()->format('YmdHis') . '-' . $i,
                'item_id' => Item::factory()->create(['status' => 'available'])->id,
                'customer_id' => $this->customer->id,
                'branch_id' => $this->branch->id,
                'sale_price' => 1000.00,
                'discount' => 0.00,
                'final_price' => 1000.00,
                'sale_date' => now(),
                'payment_method' => 'cash',
                'status' => 'completed',
            ]);
            usleep(1000);
        }

        Sale::create([
            'sale_number' => 'S-FILTER-PEND-' . now()->format('YmdHis'),
            'item_id' => Item::factory()->create(['status' => 'available'])->id,
            'customer_id' => $this->customer->id,
            'branch_id' => $this->branch->id,
            'sale_price' => 1000.00,
            'discount' => 0.00,
            'final_price' => 1000.00,
            'sale_date' => now(),
            'payment_method' => 'cash',
            'status' => 'pending',
        ]);

        $completedSales = Sale::where('status', 'completed')->get();

        $this->assertCount(2, $completedSales);
    }
}
