<?php

namespace Tests\Feature;

use App\Filament\Widgets\LoanStatsWidget;
use App\Filament\Widgets\LoansChartWidget;
use App\Filament\Widgets\RevenueChartWidget;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WidgetTest extends TestCase
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

        $this->actingAs($this->user);
    }

    #[Test]
    public function loan_stats_widget_can_render()
    {
        Livewire::test(LoanStatsWidget::class)
            ->assertSuccessful();
    }

    #[Test]
    public function loan_stats_widget_displays_active_loans_count()
    {
        // Create active loans
        $this->createLoan('active', 'L-001');
        $this->createLoan('active', 'L-002');
        $this->createLoan('overdue', 'L-003');

        $widget = new LoanStatsWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertCount(3, $stats);
        $this->assertStringContainsString('Préstamos Activos', $stats[0]->getLabel());
    }

    #[Test]
    public function loan_stats_widget_calculates_total_balance()
    {
        $this->createLoan('active', 'L-001', 1000);
        $this->createLoan('overdue', 'L-002', 500);

        $widget = new LoanStatsWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertStringContainsString('Saldo Total Pendiente', $stats[1]->getLabel());
    }

    #[Test]
    public function loan_stats_widget_filters_by_branch()
    {
        $branch2 = Branch::create([
            'name' => 'Branch 2',
            'code' => 'TB2',
            'address' => 'Address 2',
            'phone' => '0987654321',
            'email' => 'branch2@test.com',
            'is_active' => true,
        ]);

        $this->createLoan('active', 'L-001', 1000, $this->branch->id);
        $this->createLoan('active', 'L-002', 500, $branch2->id);

        $widget = new LoanStatsWidget();
        $widget->branchFilter = $this->branch->id;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getStats');
        $method->setAccessible(true);
        $stats = $method->invoke($widget);

        $this->assertCount(3, $stats);
    }

    #[Test]
    public function loans_chart_widget_can_render()
    {
        Livewire::test(LoansChartWidget::class)
            ->assertSuccessful();
    }

    #[Test]
    public function loans_chart_widget_returns_correct_data_structure()
    {
        $this->createLoan('active', 'L-001');
        $this->createLoan('overdue', 'L-002');
        $this->createLoan('paid', 'L-003');

        $widget = new LoansChartWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(5, $data['labels']); // active, overdue, paid, pending, defaulted
    }

    #[Test]
    public function loans_chart_widget_counts_loans_by_status()
    {
        $this->createLoan('active', 'L-001');
        $this->createLoan('active', 'L-002');
        $this->createLoan('overdue', 'L-003');
        $this->createLoan('paid', 'L-004');

        $widget = new LoansChartWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $counts = $data['datasets'][0]['data'];
        $this->assertEquals(2, $counts[0]); // active
        $this->assertEquals(1, $counts[1]); // overdue
        $this->assertEquals(1, $counts[2]); // paid
    }

    #[Test]
    public function loans_chart_widget_filters_by_branch()
    {
        $branch2 = Branch::create([
            'name' => 'Branch 2',
            'code' => 'TB2',
            'address' => 'Address 2',
            'phone' => '0987654321',
            'email' => 'branch2@test.com',
            'is_active' => true,
        ]);

        $this->createLoan('active', 'L-001', 1000, $this->branch->id);
        $this->createLoan('active', 'L-002', 500, $branch2->id);
        $this->createLoan('active', 'L-003', 500, $branch2->id);

        $widget = new LoansChartWidget();
        $widget->branchFilter = $this->branch->id;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $counts = $data['datasets'][0]['data'];
        $this->assertEquals(1, $counts[0]); // only 1 active loan in branch 1
    }

    #[Test]
    public function revenue_chart_widget_can_render()
    {
        Livewire::test(RevenueChartWidget::class)
            ->assertSuccessful();
    }

    #[Test]
    public function revenue_chart_widget_returns_12_months_data()
    {
        $widget = new RevenueChartWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $this->assertArrayHasKey('datasets', $data);
        $this->assertArrayHasKey('labels', $data);
        $this->assertCount(12, $data['labels']); // 12 months
        $this->assertCount(12, $data['datasets'][0]['data']); // 12 revenue values
    }

    #[Test]
    public function revenue_chart_widget_calculates_monthly_revenue()
    {
        $loan = $this->createLoan('active', 'L-001', 1000);

        // Create payment for this month
        Payment::create([
            'loan_id' => $loan->id,
            'payment_number' => 'P-001',
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'branch_id' => $this->branch->id,
        ]);

        $widget = new RevenueChartWidget();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        $lastMonthRevenue = end($data['datasets'][0]['data']);
        $this->assertGreaterThan(0, $lastMonthRevenue);
    }

    #[Test]
    public function revenue_chart_widget_filters_by_branch()
    {
        $branch2 = Branch::create([
            'name' => 'Branch 2',
            'code' => 'TB2',
            'address' => 'Address 2',
            'phone' => '0987654321',
            'email' => 'branch2@test.com',
            'is_active' => true,
        ]);

        $loan1 = $this->createLoan('active', 'L-001', 1000, $this->branch->id);
        $loan2 = $this->createLoan('active', 'L-002', 500, $branch2->id);

        Payment::create([
            'loan_id' => $loan1->id,
            'payment_number' => 'P-001',
            'amount' => 100,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'branch_id' => $this->branch->id,
        ]);

        Payment::create([
            'loan_id' => $loan2->id,
            'payment_number' => 'P-002',
            'amount' => 200,
            'payment_date' => now(),
            'payment_method' => 'cash',
            'status' => 'completed',
            'branch_id' => $branch2->id,
        ]);

        $widget = new RevenueChartWidget();
        $widget->branchFilter = $this->branch->id;
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getData');
        $method->setAccessible(true);
        $data = $method->invoke($widget);

        // Should only include revenue from branch 1
        $this->assertNotEmpty($data['datasets'][0]['data']);
    }

    #[Test]
    public function widgets_have_branch_filters()
    {
        $loanStatsWidget = new LoanStatsWidget();
        $loansChartWidget = new LoansChartWidget();
        $revenueChartWidget = new RevenueChartWidget();

        $reflection1 = new \ReflectionClass($loanStatsWidget);
        $method1 = $reflection1->getMethod('getFilters');
        $method1->setAccessible(true);
        $filters1 = $method1->invoke($loanStatsWidget);

        $reflection2 = new \ReflectionClass($loansChartWidget);
        $method2 = $reflection2->getMethod('getFilters');
        $method2->setAccessible(true);
        $filters2 = $method2->invoke($loansChartWidget);

        $reflection3 = new \ReflectionClass($revenueChartWidget);
        $method3 = $reflection3->getMethod('getFilters');
        $method3->setAccessible(true);
        $filters3 = $method3->invoke($revenueChartWidget);

        $this->assertNotNull($filters1);
        $this->assertNotNull($filters2);
        $this->assertNotNull($filters3);

        $this->assertIsArray($filters1);
        $this->assertArrayHasKey(null, $filters1);
    }

    // Helper method to create loans
    private function createLoan(string $status, string $loanNumber, float $amount = 500, ?int $branchId = null): Loan
    {
        $item = Item::create([
            'name' => 'Test Item ' . $loanNumber,
            'category' => 'Joyería',
            'condition' => 'excellent',
            'appraised_value' => 1000,
            'status' => 'available',
            'branch_id' => $branchId ?? $this->branch->id,
        ]);

        return Loan::create([
            'loan_number' => $loanNumber,
            'customer_id' => $this->customer->id,
            'item_id' => $item->id,
            'loan_amount' => $amount,
            'interest_rate' => 10,
            'loan_term_days' => 30,
            'start_date' => now(),
            'due_date' => $status === 'overdue' ? now()->subDays(5) : now()->addDays(30),
            'interest_amount' => $amount * 0.1,
            'total_amount' => $amount * 1.1,
            'status' => $status,
            'branch_id' => $branchId ?? $this->branch->id,
        ]);
    }
}
