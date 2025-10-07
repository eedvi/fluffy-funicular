<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Loan;
use App\Models\Sale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_has_fillable_attributes(): void
    {
        $branch = Branch::factory()->create();

        $customer = Customer::create([
            'first_name' => 'John',
            'last_name' => 'Doe',
            'phone' => '1234567890',
            'email' => 'john@example.com',
            'address' => '123 Main St',
            'identity_number' => 'ID123456',
            'registration_date' => now(),
            'is_active' => true,
            'branch_id' => $branch->id,
        ]);

        $this->assertEquals('John', $customer->first_name);
        $this->assertEquals('Doe', $customer->last_name);
        $this->assertEquals('1234567890', $customer->phone);
        $this->assertEquals('john@example.com', $customer->email);
        $this->assertEquals('123 Main St', $customer->address);
        $this->assertEquals('ID123456', $customer->identity_number);
    }

    public function test_it_has_loans_relationship(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $customer->loans()
        );
    }

    public function test_it_has_sales_relationship(): void
    {
        $customer = Customer::factory()->create();

        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $customer->sales()
        );
    }

    public function test_it_uses_activity_log(): void
    {
        $customer = Customer::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Customer',
        ]);

        $this->assertNotEmpty($customer->activities);
    }

    public function test_it_soft_deletes(): void
    {
        $customer = Customer::factory()->create();
        $id = $customer->id;

        $customer->delete();

        $this->assertSoftDeleted('customers', ['id' => $id]);
    }

    public function test_it_validates_required_fields(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);

        Customer::create([
            // Missing required first_name and last_name
            'phone' => '1234567890',
        ]);
    }
}
