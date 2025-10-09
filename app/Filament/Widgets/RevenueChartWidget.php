<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class RevenueChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Ingresos Mensuales';

    protected static ?int $sort = 2;

    public ?int $branchFilter = null;

    protected function getFilters(): ?array
    {
        $branches = Branch::pluck('name', 'id')->toArray();
        return [null => 'Todas las sucursales'] + $branches;
    }

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
        $cacheKey = 'revenue_chart_' . ($this->branchFilter ?? 'all');

        return Cache::remember($cacheKey, 300, function () {
            $months = collect(range(0, 11))->map(function ($monthsAgo) {
                return now()->subMonths(11 - $monthsAgo);
            });

            $revenue = $months->map(function ($month) {
                return Payment::where('status', 'completed')
                    ->whereYear('payment_date', $month->year)
                    ->whereMonth('payment_date', $month->month)
                    ->when($this->branchFilter, fn($query) => $query->whereHas('loan', fn($q) => $q->where('branch_id', $this->branchFilter)))
                    ->sum('amount');
            })->toArray();

            $labels = $months->map(function ($month) {
                return $month->format('M Y');
            })->toArray();

            return [
                'revenue' => $revenue,
                'labels' => $labels,
            ];
        });
    }
}
