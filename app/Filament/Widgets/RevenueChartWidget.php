<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ingresos Mensuales';

    protected static ?int $sort = 2;

    protected function getData(): array
    {
        $data = $this->getRevenuePerMonth();

        return [
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $data['revenue'],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    private function getRevenuePerMonth(): array
    {
        $months = collect(range(0, 11))->map(function ($monthsAgo) {
            return now()->subMonths(11 - $monthsAgo);
        });

        $revenue = $months->map(function ($month) {
            return Payment::where('status', 'Completado')
                ->whereYear('payment_date', $month->year)
                ->whereMonth('payment_date', $month->month)
                ->sum('amount');
        })->toArray();

        $labels = $months->map(function ($month) {
            return $month->format('M Y');
        })->toArray();

        return [
            'revenue' => $revenue,
            'labels' => $labels,
        ];
    }
}
