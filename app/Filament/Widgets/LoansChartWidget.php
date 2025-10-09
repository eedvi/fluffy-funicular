<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Loan;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class LoansChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Préstamos';

    protected static ?int $sort = 3;

    public ?int $branchFilter = null;

    protected function getFilters(): ?array
    {
        $branches = Branch::pluck('name', 'id')->toArray();
        return [null => 'Todas las sucursales'] + $branches;
    }

    protected function getData(): array
    {
        $cacheKey = 'loans_chart_' . ($this->branchFilter ?? 'all');

        return Cache::remember($cacheKey, 300, function () {
            $active = Loan::where('status', 'active')
                ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
                ->count();

            $overdue = Loan::where('status', 'overdue')
                ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
                ->count();

            $paid = Loan::where('status', 'paid')
                ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
                ->count();

            $pending = Loan::where('status', 'pending')
                ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
                ->count();

            $defaulted = Loan::where('status', 'defaulted')
                ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
                ->count();

            return [
                'datasets' => [
                    [
                        'label' => 'Préstamos',
                        'data' => [$active, $overdue, $paid, $pending, $defaulted],
                        'backgroundColor' => [
                            'rgb(34, 197, 94)',   // green - active
                            'rgb(249, 115, 22)',  // orange - overdue
                            'rgb(59, 130, 246)',  // blue - paid
                            'rgb(156, 163, 175)', // gray - pending
                            'rgb(239, 68, 68)',   // red - defaulted
                        ],
                    ],
                ],
                'labels' => ['Activo', 'Vencido', 'Pagado', 'Pendiente', 'Confiscado'],
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
