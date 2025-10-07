<?php

namespace App\Filament\Pages;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Collection;

class Reports extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-document-chart-bar';

    protected static string $view = 'filament.pages.reports';

    protected static ?string $navigationGroup = 'Reportes';

    protected static ?string $navigationLabel = 'Reportes';

    protected static ?string $title = 'Generador de Reportes';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'date_from' => now()->startOfMonth(),
            'date_to' => now()->endOfMonth(),
            'branch_id' => null, // All branches
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Filtros de Reporte')
                    ->description('Seleccione el rango de fechas y sucursal para los reportes')
                    ->schema([
                        DatePicker::make('date_from')
                            ->label('Desde')
                            ->default(now()->startOfMonth())
                            ->displayFormat('d/m/Y')
                            ->required(),
                        DatePicker::make('date_to')
                            ->label('Hasta')
                            ->default(now()->endOfMonth())
                            ->displayFormat('d/m/Y')
                            ->required(),
                        Select::make('branch_id')
                            ->label('Sucursal')
                            ->options(fn () => [null => 'Todas las Sucursales'] + Branch::pluck('name', 'id')->toArray())
                            ->default(null)
                            ->searchable(),
                    ])
                    ->columns(3),
            ])
            ->statePath('data');
    }

    public function generateActiveLoansReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $branchId = $formData['branch_id'];

        $query = Loan::where('status', 'active')
            ->with(['customer', 'item', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $loans = $query->orderBy('due_date', 'asc')->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.active-loans', compact('loans'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'prestamos-activos-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($loans) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $loans) {}

                    public function collection() {
                        return $this->loans->map(fn($loan) => [
                            'Número' => $loan->loan_number,
                            'Cliente' => $loan->customer->full_name ?? '',
                            'Artículo' => $loan->item->name ?? '',
                            'Monto' => $loan->loan_amount,
                            'Total' => $loan->total_amount,
                            'Saldo' => $loan->balance_remaining,
                            'Vencimiento' => $loan->due_date->format('d/m/Y'),
                        ]);
                    }

                    public function headings(): array {
                        return ['Número', 'Cliente', 'Artículo', 'Monto', 'Total', 'Saldo', 'Vencimiento'];
                    }
                },
                'prestamos-activos-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generateOverdueLoansReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $branchId = $formData['branch_id'];

        $query = Loan::whereIn('status', ['active', 'overdue'])
            ->where('due_date', '<', now())
            ->with(['customer', 'item', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $loans = $query->orderBy('due_date', 'asc')->get();

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.overdue-loans', compact('loans'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'prestamos-vencidos-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($loans) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $loans) {}

                    public function collection() {
                        return $this->loans->map(fn($loan) => [
                            'Número' => $loan->loan_number,
                            'Cliente' => $loan->customer->full_name ?? '',
                            'Artículo' => $loan->item->name ?? '',
                            'Monto' => $loan->loan_amount,
                            'Total' => $loan->total_amount,
                            'Saldo' => $loan->balance_remaining,
                            'Vencimiento' => $loan->due_date->format('d/m/Y'),
                            'Días Vencido' => now()->diffInDays($loan->due_date),
                        ]);
                    }

                    public function headings(): array {
                        return ['Número', 'Cliente', 'Artículo', 'Monto', 'Total', 'Saldo', 'Vencimiento', 'Días Vencido'];
                    }
                },
                'prestamos-vencidos-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generateSalesReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $dateFrom = $formData['date_from'];
        $dateTo = $formData['date_to'];
        $branchId = $formData['branch_id'];

        $query = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->with(['customer', 'item', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $sales = $query->orderBy('sale_date', 'desc')->get();

        $totalSales = $sales->sum('final_price');
        $totalDiscount = $sales->sum('discount');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.sales', compact('sales', 'totalSales', 'totalDiscount', 'dateFrom', 'dateTo'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'ventas-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($sales) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $sales) {}

                    public function collection() {
                        return $this->sales->map(fn($sale) => [
                            'Número' => $sale->sale_number,
                            'Cliente' => $sale->customer->full_name ?? 'Sin Cliente',
                            'Artículo' => $sale->item->name ?? '',
                            'Precio' => $sale->sale_price,
                            'Descuento' => $sale->discount,
                            'Total' => $sale->final_price,
                            'Fecha' => $sale->sale_date->format('d/m/Y'),
                            'Estado' => $sale->status,
                        ]);
                    }

                    public function headings(): array {
                        return ['Número', 'Cliente', 'Artículo', 'Precio', 'Descuento', 'Total', 'Fecha', 'Estado'];
                    }
                },
                'ventas-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generatePaymentsReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $dateFrom = $formData['date_from'];
        $dateTo = $formData['date_to'];
        $branchId = $formData['branch_id'];

        $query = Payment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->with(['loan.customer', 'branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $payments = $query->orderBy('payment_date', 'desc')->get();

        $totalPayments = $payments->sum('amount');

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.payments', compact('payments', 'totalPayments', 'dateFrom', 'dateTo'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'pagos-recibidos-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($payments) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $payments) {}

                    public function collection() {
                        return $this->payments->map(fn($payment) => [
                            'Número' => $payment->payment_number,
                            'Préstamo' => $payment->loan->loan_number ?? '',
                            'Cliente' => $payment->loan->customer->full_name ?? '',
                            'Monto' => $payment->amount,
                            'Fecha' => $payment->payment_date->format('d/m/Y'),
                            'Método' => $payment->payment_method,
                        ]);
                    }

                    public function headings(): array {
                        return ['Número', 'Préstamo', 'Cliente', 'Monto', 'Fecha', 'Método'];
                    }
                },
                'pagos-recibidos-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generateInventoryReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $branchId = $formData['branch_id'];

        $query = Item::whereIn('status', ['available', 'collateral', 'forfeited'])
            ->with(['branch']);

        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        $items = $query->orderBy('category')->orderBy('name')->get();

        $totalValue = $items->sum('appraised_value');
        $totalMarketValue = $items->sum('market_value');

        $byCategory = $items->groupBy('category')->map(function ($categoryItems) {
            return [
                'count' => $categoryItems->count(),
                'total_value' => $categoryItems->sum('appraised_value'),
            ];
        });

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.inventory', compact('items', 'totalValue', 'totalMarketValue', 'byCategory'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'inventario-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($items) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $items) {}

                    public function collection() {
                        return $this->items->map(fn($item) => [
                            'Nombre' => $item->name,
                            'Categoría' => $item->category,
                            'Marca' => $item->brand,
                            'Modelo' => $item->model,
                            'Valor Tasado' => $item->appraised_value,
                            'Valor Mercado' => $item->market_value,
                            'Estado' => $item->status,
                        ]);
                    }

                    public function headings(): array {
                        return ['Nombre', 'Categoría', 'Marca', 'Modelo', 'Valor Tasado', 'Valor Mercado', 'Estado'];
                    }
                },
                'inventario-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generateRevenueByBranchReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $dateFrom = $formData['date_from'];
        $dateTo = $formData['date_to'];

        $branches = Branch::with([
            'loans' => fn($q) => $q->whereBetween('start_date', [$dateFrom, $dateTo]),
            'payments' => fn($q) => $q->whereBetween('payment_date', [$dateFrom, $dateTo])->where('status', 'completed'),
            'sales' => fn($q) => $q->whereBetween('sale_date', [$dateFrom, $dateTo])->where('status', 'delivered'),
        ])->get();

        $revenueData = $branches->map(function ($branch) {
            $loansRevenue = $branch->loans->sum('interest_amount');
            $salesRevenue = $branch->sales->sum('final_price');
            $paymentsReceived = $branch->payments->sum('amount');

            return [
                'branch' => $branch,
                'loans_issued' => $branch->loans->count(),
                'loans_revenue' => $loansRevenue,
                'sales_count' => $branch->sales->count(),
                'sales_revenue' => $salesRevenue,
                'payments_received' => $paymentsReceived,
                'total_revenue' => $loansRevenue + $salesRevenue,
            ];
        });

        $totals = [
            'loans_issued' => $revenueData->sum('loans_issued'),
            'loans_revenue' => $revenueData->sum('loans_revenue'),
            'sales_count' => $revenueData->sum('sales_count'),
            'sales_revenue' => $revenueData->sum('sales_revenue'),
            'payments_received' => $revenueData->sum('payments_received'),
            'total_revenue' => $revenueData->sum('total_revenue'),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.revenue-by-branch', compact('revenueData', 'totals', 'dateFrom', 'dateTo'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'ingresos-por-sucursal-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($revenueData) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $data) {}

                    public function collection() {
                        return $this->data->map(fn($item) => [
                            'Sucursal' => $item['branch']->name,
                            'Préstamos Emitidos' => $item['loans_issued'],
                            'Ingresos por Intereses' => $item['loans_revenue'],
                            'Ventas Realizadas' => $item['sales_count'],
                            'Ingresos por Ventas' => $item['sales_revenue'],
                            'Pagos Recibidos' => $item['payments_received'],
                            'Ingresos Totales' => $item['total_revenue'],
                        ]);
                    }

                    public function headings(): array {
                        return ['Sucursal', 'Préstamos Emitidos', 'Ingresos por Intereses', 'Ventas Realizadas', 'Ingresos por Ventas', 'Pagos Recibidos', 'Ingresos Totales'];
                    }
                },
                'ingresos-por-sucursal-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }

    public function generateCustomerAnalyticsReport(string $format = 'pdf')
    {
        $formData = $this->form->getState();
        $dateFrom = $formData['date_from'];
        $dateTo = $formData['date_to'];
        $branchId = $formData['branch_id'];

        $query = Customer::with([
            'loans' => fn($q) => $q->whereBetween('start_date', [$dateFrom, $dateTo]),
            'sales' => fn($q) => $q->whereBetween('sale_date', [$dateFrom, $dateTo]),
        ]);

        if ($branchId) {
            $query->whereHas('loans', fn($q) => $q->where('branch_id', $branchId));
        }

        $customers = $query->get();

        $customerData = $customers->map(function ($customer) {
            $totalBorrowed = $customer->loans->sum('loan_amount');
            $totalPurchased = $customer->sales->sum('final_price');
            $activeLoans = $customer->loans->where('status', 'active')->count();
            $paidLoans = $customer->loans->where('status', 'paid')->count();

            return [
                'customer' => $customer,
                'total_loans' => $customer->loans->count(),
                'active_loans' => $activeLoans,
                'paid_loans' => $paidLoans,
                'total_borrowed' => $totalBorrowed,
                'total_purchased' => $totalPurchased,
                'total_business' => $totalBorrowed + $totalPurchased,
            ];
        })->sortByDesc('total_business')->take(50); // Top 50 customers

        $totals = [
            'total_customers' => $customerData->count(),
            'total_loans' => $customerData->sum('total_loans'),
            'total_borrowed' => $customerData->sum('total_borrowed'),
            'total_purchased' => $customerData->sum('total_purchased'),
            'total_business' => $customerData->sum('total_business'),
        ];

        if ($format === 'pdf') {
            $pdf = Pdf::loadView('reports.customer-analytics', compact('customerData', 'totals', 'dateFrom', 'dateTo'));
            return response()->streamDownload(
                fn () => print($pdf->output()),
                'analisis-clientes-' . now()->format('Y-m-d') . '.pdf'
            );
        } else {
            return Excel::download(
                new class($customerData) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
                    public function __construct(private Collection $data) {}

                    public function collection() {
                        return $this->data->map(fn($item) => [
                            'Cliente' => $item['customer']->full_name,
                            'DPI' => $item['customer']->identity_number,
                            'Teléfono' => $item['customer']->phone,
                            'Total Préstamos' => $item['total_loans'],
                            'Préstamos Activos' => $item['active_loans'],
                            'Préstamos Pagados' => $item['paid_loans'],
                            'Total Prestado' => $item['total_borrowed'],
                            'Total Comprado' => $item['total_purchased'],
                            'Volumen Total' => $item['total_business'],
                        ]);
                    }

                    public function headings(): array {
                        return ['Cliente', 'DPI', 'Teléfono', 'Total Préstamos', 'Préstamos Activos', 'Préstamos Pagados', 'Total Prestado', 'Total Comprado', 'Volumen Total'];
                    }
                },
                'analisis-clientes-' . now()->format('Y-m-d') . '.xlsx'
            );
        }
    }
}
