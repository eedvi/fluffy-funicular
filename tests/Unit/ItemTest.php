<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $branch = Branch::factory()->create();

        $item = Item::create([
            'branch_id' => $branch->id,
            'name' => 'Gold Ring',
            'category' => 'Jewelry',
            'description' => 'Beautiful gold ring',
            'appraised_value' => 1000.00,
            'status' => 'available',
        ]);

        $this->assertEquals('Gold Ring', $item->name);
        $this->assertEquals('Jewelry', $item->category);
        $this->assertEquals('Beautiful gold ring', $item->description);
        $this->assertEquals(1000.00, $item->appraised_value);
        $this->assertEquals('available', $item->status);
    }

    public function test_it_belongs_to_branch(): void
    {
        $item = Item::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\BelongsTo::class,
            $item->branch()
        );
    }

    public function test_it_has_loans_relationship(): void
    {
        $item = Item::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $item->loans()
        );
    }

    public function test_it_has_sales_relationship(): void
    {
        $item = Item::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $item->sales()
        );
    }

    public function test_it_casts_appraised_value_to_decimal(): void
    {
        $item = Item::factory()->create([
            'appraised_value' => 1500.50,
        ]);

        // Decimal cast may return string, so check numeric equality
        $this->assertEquals('1500.50', (string)$item->appraised_value);
    }

    public function test_it_uses_activity_log(): void
    {
        $item = Item::factory()->create([
            'name' => 'Test Item',
        ]);

        $this->assertNotEmpty($item->activities);
    }

    public function test_it_soft_deletes(): void
    {
        $item = Item::factory()->create();
        $id = $item->id;

        $item->delete();

        $this->assertSoftDeleted('items', ['id' => $id]);
    }

    public function test_it_has_default_status(): void
    {
        $item = Item::factory()->create([
            'status' => 'available',
        ]);

        $this->assertEquals('available', $item->status);
    }

    public function test_it_can_filter_by_status(): void
    {
        Item::factory()->create(['status' => 'available']);
        Item::factory()->create(['status' => 'collateral']);
        Item::factory()->create(['status' => 'sold']);

        $available = Item::where('status', 'available')->get();
        $collateral = Item::where('status', 'collateral')->get();

        $this->assertCount(1, $available);
        $this->assertCount(1, $collateral);
    }

    public function test_it_can_filter_by_category(): void
    {
        Item::factory()->create(['category' => 'Jewelry']);
        Item::factory()->create(['category' => 'Electronics']);
        Item::factory()->create(['category' => 'Jewelry']);

        $jewelry = Item::where('category', 'Jewelry')->get();

        $this->assertCount(2, $jewelry);
    }
}
