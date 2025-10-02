<?php

namespace App\Filament\Widgets;

use App\Models\Sale;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class RevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Ingresos por Mes';

    protected static ?int $sort = 3;

    protected function getData(): array
    {
        $salesData = Sale::selectRaw("strftime('%Y-%m', sale_date) as month, SUM(final_price) as total")
            ->where('sale_date', '>=', now()->subMonths(11))
            ->where('status', 'Completada')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        $paymentsData = Payment::selectRaw("strftime('%Y-%m', payment_date) as month, SUM(amount) as total")
            ->where('payment_date', '>=', now()->subMonths(11))
            ->where('status', 'Completado')
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->pluck('total', 'month');

        $months = collect();
        for ($i = 11; $i >= 0; $i--) {
            $months->push(now()->subMonths($i)->format('Y-m'));
        }

        $salesValues = $months->map(fn ($month) => $salesData->get($month, 0));
        $paymentsValues = $months->map(fn ($month) => $paymentsData->get($month, 0));

        return [
            'datasets' => [
                [
                    'label' => 'Ventas',
                    'data' => $salesValues,
                    'backgroundColor' => 'rgba(16, 185, 129, 0.5)',
                    'borderColor' => 'rgb(16, 185, 129)',
                ],
                [
                    'label' => 'Pagos',
                    'data' => $paymentsValues,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.5)',
                    'borderColor' => 'rgb(59, 130, 246)',
                ],
            ],
            'labels' => $months->map(fn ($month) => \Carbon\Carbon::parse($month)->format('M Y')),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
