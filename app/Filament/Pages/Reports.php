<?php

namespace App\Filament\Pages;

use App\Models\Item;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\Sale;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
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
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Rango de Fechas')
                    ->description('Seleccione el rango de fechas para los reportes')
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
                    ])
                    ->columns(2),
            ])
            ->statePath('data');
    }

    public function generateActiveLoansReport(string $format = 'pdf')
    {
        $loans = Loan::where('status', 'active')
            ->with(['customer', 'item'])
            ->orderBy('due_date', 'asc')
            ->get();

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
        $loans = Loan::whereIn('status', ['active', 'overdue'])
            ->where('due_date', '<', now())
            ->with(['customer', 'item'])
            ->orderBy('due_date', 'asc')
            ->get();

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

        $sales = Sale::whereBetween('sale_date', [$dateFrom, $dateTo])
            ->with(['customer', 'item'])
            ->orderBy('sale_date', 'desc')
            ->get();

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

        $payments = Payment::whereBetween('payment_date', [$dateFrom, $dateTo])
            ->where('status', 'completed')
            ->with(['loan.customer'])
            ->orderBy('payment_date', 'desc')
            ->get();

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
        $items = Item::whereIn('status', ['available', 'collateral', 'forfeited'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

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
}
