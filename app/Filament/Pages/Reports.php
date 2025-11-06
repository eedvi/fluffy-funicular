<?php

namespace App\Filament\Pages;

use App\Exports\ActiveLoansExport;
use App\Exports\CustomerAnalyticsExport;
use App\Exports\InventoryExport;
use App\Exports\OverdueLoansExport;
use App\Exports\PaymentsExport;
use App\Exports\RevenueByBranchExport;
use App\Exports\SalesExport;
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

    /**
     * Log report export activity
     */
    protected function logReportExport(string $reportType, string $format, ?int $branchId, array $additionalData = []): void
    {
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'report_type' => $reportType,
                'format' => $format,
                'branch_id' => $branchId,
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
                ...$additionalData,
            ])
            ->log("Reporte exportado: {$reportType} ({$format})");
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
        try {
            $formData = $this->form->getState();
            $branchId = $formData['branch_id'];

            $query = Loan::where('status', 'active')
                ->with(['customer', 'item', 'branch', 'installments']);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $loans = $query->orderByRaw('COALESCE(due_date, start_date) ASC')->get();

            if ($loans->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay préstamos activos para generar el reporte.')
                    ->send();

                return null;
            }

            // Log the export
            $this->logReportExport('Préstamos Activos', $format, $branchId, [
                'total_loans' => $loans->count(),
                'total_amount' => $loans->sum('loan_amount'),
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.active-loans', compact('loans'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'prestamos-activos-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new ActiveLoansExport($loans),
                    'prestamos-activos-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating active loans report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateOverdueLoansReport(string $format = 'pdf')
    {
        try {
            $formData = $this->form->getState();
            $branchId = $formData['branch_id'];

            $query = Loan::where(function($q) {
                // Traditional loans with due_date
                $q->where(function($subQ) {
                    $subQ->whereIn('status', ['active', 'overdue'])
                         ->whereNotNull('due_date')
                         ->where('due_date', '<', now());
                })
                // Minimum payment loans that are at risk
                ->orWhere(function($subQ) {
                    $subQ->where('status', 'active')
                         ->where('requires_minimum_payment', true)
                         ->where('is_at_risk', true);
                })
                // Installment loans with overdue installments
                ->orWhere(function($subQ) {
                    $subQ->where('status', 'overdue')
                         ->where('payment_plan_type', 'installments');
                });
            })
            ->with(['customer', 'item', 'branch', 'installments' => function($q) {
                $q->where('status', 'overdue')->orderBy('installment_number');
            }]);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $loans = $query->orderByRaw('
                CASE
                    WHEN payment_plan_type = "installments" THEN 1
                    WHEN requires_minimum_payment = 1 THEN 2
                    ELSE 3
                END,
                COALESCE(due_date, start_date) ASC
            ')->get();

            if ($loans->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->success()
                    ->title('Sin datos')
                    ->body('No hay préstamos vencidos. ¡Excelente trabajo!')
                    ->send();

                return null;
            }

            // Log the export
            $this->logReportExport('Préstamos Vencidos', $format, $branchId, [
                'total_loans' => $loans->count(),
                'total_overdue_amount' => $loans->sum('balance_remaining'),
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.overdue-loans', compact('loans'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'prestamos-vencidos-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new OverdueLoansExport($loans),
                    'prestamos-vencidos-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating overdue loans report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateSalesReport(string $format = 'pdf')
    {
        try {
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

            if ($sales->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay ventas en el período seleccionado.')
                    ->send();

                return null;
            }

            $totalSales = $sales->sum('final_price');
            $totalDiscount = $sales->sum('discount');

            // Log the export
            $this->logReportExport('Ventas por Período', $format, $branchId, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_sales' => $sales->count(),
                'total_revenue' => $totalSales,
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.sales', compact('sales', 'totalSales', 'totalDiscount', 'dateFrom', 'dateTo'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'ventas-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new SalesExport($sales),
                    'ventas-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating sales report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generatePaymentsReport(string $format = 'pdf')
    {
        try {
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

            if ($payments->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay pagos en el período seleccionado.')
                    ->send();

                return null;
            }

            $totalPayments = $payments->sum('amount');

            // Log the export
            $this->logReportExport('Pagos Recibidos', $format, $branchId, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_payments' => $payments->count(),
                'total_amount' => $totalPayments,
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.payments', compact('payments', 'totalPayments', 'dateFrom', 'dateTo'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'pagos-recibidos-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new PaymentsExport($payments),
                    'pagos-recibidos-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating payments report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateInventoryReport(string $format = 'pdf')
    {
        try {
            $formData = $this->form->getState();
            $branchId = $formData['branch_id'];

            $query = Item::whereIn('status', ['available', 'collateral', 'forfeited'])
                ->with(['branch', 'category']);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            $items = $query->orderBy('category_id')->orderBy('name')->get();

            if ($items->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay artículos en inventario para generar el reporte.')
                    ->send();

                return null;
            }

            $totalValue = $items->sum('appraised_value');
            $totalMarketValue = $items->sum('market_value');

            $byCategory = $items->groupBy(fn($item) => $item->category?->name ?? 'Sin Categoría')->map(function ($categoryItems) {
                return [
                    'count' => $categoryItems->count(),
                    'total_value' => $categoryItems->sum('appraised_value'),
                ];
            });

            // Log the export
            $this->logReportExport('Inventario', $format, $branchId, [
                'total_items' => $items->count(),
                'total_value' => $totalValue,
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.inventory', compact('items', 'totalValue', 'totalMarketValue', 'byCategory'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'inventario-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new InventoryExport($items),
                    'inventario-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating inventory report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateRevenueByBranchReport(string $format = 'pdf')
    {
        try {
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

            if ($revenueData->sum('total_revenue') == 0) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay ingresos registrados en el período seleccionado.')
                    ->send();

                return null;
            }

            $totals = [
                'loans_issued' => $revenueData->sum('loans_issued'),
                'loans_revenue' => $revenueData->sum('loans_revenue'),
                'sales_count' => $revenueData->sum('sales_count'),
                'sales_revenue' => $revenueData->sum('sales_revenue'),
                'payments_received' => $revenueData->sum('payments_received'),
                'total_revenue' => $revenueData->sum('total_revenue'),
            ];

            // Log the export
            $this->logReportExport('Ingresos por Sucursal', $format, null, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_revenue' => $totals['total_revenue'],
                'branches_count' => $revenueData->count(),
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.revenue-by-branch', compact('revenueData', 'totals', 'dateFrom', 'dateTo'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'ingresos-por-sucursal-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new RevenueByBranchExport($revenueData),
                    'ingresos-por-sucursal-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating revenue by branch report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateCustomerAnalyticsReport(string $format = 'pdf')
    {
        try {
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

            if ($customerData->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay clientes con actividad en el período seleccionado.')
                    ->send();

                return null;
            }

            $totals = [
                'total_customers' => $customerData->count(),
                'total_loans' => $customerData->sum('total_loans'),
                'total_borrowed' => $customerData->sum('total_borrowed'),
                'total_purchased' => $customerData->sum('total_purchased'),
                'total_business' => $customerData->sum('total_business'),
            ];

            // Log the export
            $this->logReportExport('Análisis de Clientes', $format, $branchId, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_customers' => $totals['total_customers'],
                'total_business' => $totals['total_business'],
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.customer-analytics', compact('customerData', 'totals', 'dateFrom', 'dateTo'));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'analisis-clientes-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new CustomerAnalyticsExport($customerData),
                    'analisis-clientes-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating customer analytics report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }

    public function generateConfiscatedItemsReport(string $format = 'pdf')
    {
        try {
            $formData = $this->form->getState();
            $dateFrom = $formData['date_from'];
            $dateTo = $formData['date_to'];
            $branchId = $formData['branch_id'];

            $query = Item::where('status', 'forfeited')
                ->with(['branch', 'category', 'customer', 'loans' => function($q) {
                    $q->where('status', 'forfeited')->latest();
                }]);

            if ($branchId) {
                $query->where('branch_id', $branchId);
            }

            // Filter by confiscation date if available, otherwise use loan forfeited_date
            $query->where(function($q) use ($dateFrom, $dateTo) {
                $q->whereBetween('confiscated_date', [$dateFrom, $dateTo])
                  ->orWhereHas('loans', function($lq) use ($dateFrom, $dateTo) {
                      $lq->where('status', 'forfeited')
                         ->whereBetween('forfeited_date', [$dateFrom, $dateTo]);
                  });
            });

            $items = $query->orderBy('confiscated_date', 'desc')->get();

            // Enrich items with loan information
            $items->each(function($item) {
                $forfeitedLoan = $item->loans->where('status', 'forfeited')->first();
                $item->forfeited_loan = $forfeitedLoan;
                $item->effective_confiscation_date = $item->confiscated_date ?? $forfeitedLoan?->forfeited_date;
            });

            if ($items->isEmpty()) {
                \Filament\Notifications\Notification::make()
                    ->warning()
                    ->title('Sin datos')
                    ->body('No hay artículos confiscados en el período seleccionado.')
                    ->send();

                return null;
            }

            $totalValue = $items->sum('appraised_value');
            $totalAuctionPrice = $items->sum('auction_price');
            $itemsWithAuction = $items->whereNotNull('auction_date')->count();
            $itemsWithoutAuction = $items->whereNull('auction_date')->count();

            // Group by category
            $byCategory = $items->groupBy(fn($item) => $item->category?->name ?? 'Sin Categoría')->map(function ($categoryItems) {
                return [
                    'count' => $categoryItems->count(),
                    'total_value' => $categoryItems->sum('appraised_value'),
                ];
            });

            // Log the export
            $this->logReportExport('Artículos Confiscados', $format, $branchId, [
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'total_items' => $items->count(),
                'total_value' => $totalValue,
            ]);

            if ($format === 'pdf') {
                $pdf = Pdf::loadView('reports.confiscated-items', compact(
                    'items',
                    'totalValue',
                    'totalAuctionPrice',
                    'itemsWithAuction',
                    'itemsWithoutAuction',
                    'byCategory',
                    'dateFrom',
                    'dateTo'
                ));
                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    'articulos-confiscados-' . now()->format('Y-m-d') . '.pdf'
                );
            } else {
                return Excel::download(
                    new \App\Exports\ConfiscatedItemsExport($items),
                    'articulos-confiscados-' . now()->format('Y-m-d') . '.xlsx'
                );
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error generating confiscated items report', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            \Filament\Notifications\Notification::make()
                ->danger()
                ->title('Error al generar reporte')
                ->body('Ocurrió un error al generar el reporte. Por favor intente nuevamente.')
                ->send();

            return null;
        }
    }
}
