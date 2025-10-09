<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Loan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class OverdueLoansAnalyticsWidget extends ChartWidget
{
    protected static ?string $heading = 'Análisis de Préstamos Vencidos';

    protected static ?int $sort = 5;

    protected static ?string $maxHeight = '300px';

    public ?string $filter = 'all';

    protected function getFilters(): ?array
    {
        $branches = Branch::pluck('name', 'id')->toArray();
        return ['all' => 'Todas las sucursales'] + $branches;
    }

    protected function getData(): array
    {
        $branchId = $this->filter === 'all' ? null : $this->filter;
        $cacheKey = 'overdue_analytics_' . ($branchId ?? 'all');

        return Cache::remember($cacheKey, 300, function () use ($branchId) {
            // Count overdue loans by days overdue ranges
            $ranges = [
                '1-7 días' => Loan::where('status', 'overdue')
                    ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 1 AND 7')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->count(),

                '8-15 días' => Loan::where('status', 'overdue')
                    ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 8 AND 15')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->count(),

                '16-30 días' => Loan::where('status', 'overdue')
                    ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 16 AND 30')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->count(),

                '31-60 días' => Loan::where('status', 'overdue')
                    ->whereRaw('DATEDIFF(NOW(), due_date) BETWEEN 31 AND 60')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->count(),

                '60+ días' => Loan::where('status', 'overdue')
                    ->whereRaw('DATEDIFF(NOW(), due_date) > 60')
                    ->when($branchId, fn ($query) => $query->where('branch_id', $branchId))
                    ->count(),
            ];

            return [
                'datasets' => [
                    [
                        'label' => 'Préstamos Vencidos',
                        'data' => array_values($ranges),
                        'backgroundColor' => [
                            'rgb(234, 179, 8)',   // yellow - 1-7 days
                            'rgb(251, 146, 60)',  // orange - 8-15 days
                            'rgb(239, 68, 68)',   // red - 16-30 days
                            'rgb(220, 38, 38)',   // dark red - 31-60 days
                            'rgb(153, 27, 27)',   // very dark red - 60+ days
                        ],
                    ],
                ],
                'labels' => array_keys($ranges),
            ];
        });
    }

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,
                    ],
                ],
            ],
        ];
    }
}
