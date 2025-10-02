<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\Loan;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class LoanStatsWidget extends BaseWidget
{
    public ?int $branchFilter = null;

    protected function getFilters(): ?array
    {
        $branches = Branch::pluck('name', 'id')->toArray();
        return [null => 'Todas las sucursales'] + $branches;
    }

    protected function getStats(): array
    {
        // Calculate stats
        $activeLoans = Loan::where('status', 'active')
            ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
            ->count();

        $overdueLoans = Loan::where('status', 'overdue')
            ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
            ->count();

        $totalActiveBalance = Loan::whereIn('status', ['active', 'overdue'])
            ->when($this->branchFilter, fn($query) => $query->where('branch_id', $this->branchFilter))
            ->sum('balance_remaining');

        // Revenue this month
        $revenueThisMonth = Payment::where('status', 'Completado')
            ->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->when($this->branchFilter, fn($query) => $query->whereHas('loan', fn($q) => $q->where('branch_id', $this->branchFilter)))
            ->sum('amount');

        $revenueLastMonth = Payment::where('status', 'Completado')
            ->whereMonth('payment_date', now()->subMonth()->month)
            ->whereYear('payment_date', now()->subMonth()->year)
            ->when($this->branchFilter, fn($query) => $query->whereHas('loan', fn($q) => $q->where('branch_id', $this->branchFilter)))
            ->sum('amount');

        $revenueChange = $revenueLastMonth > 0
            ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
            : 0;

        return [
            Stat::make('Préstamos Activos', $activeLoans)
                ->description($overdueLoans . ' préstamos vencidos')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color($overdueLoans > 0 ? 'warning' : 'success')
                ->chart([7, 5, 10, 8, 12, 9, $activeLoans]),

            Stat::make('Saldo Total Pendiente', '$' . number_format($totalActiveBalance, 2))
                ->description('Préstamos activos y vencidos')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('info'),

            Stat::make('Ingresos del Mes', '$' . number_format($revenueThisMonth, 2))
                ->description(($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '% vs mes pasado')
                ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                ->color($revenueChange >= 0 ? 'success' : 'danger')
                ->chart([
                    $revenueLastMonth * 0.8,
                    $revenueLastMonth * 0.9,
                    $revenueLastMonth,
                    $revenueThisMonth * 0.7,
                    $revenueThisMonth * 0.85,
                    $revenueThisMonth
                ]),
        ];
    }
}
