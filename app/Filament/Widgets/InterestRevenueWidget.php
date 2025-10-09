<?php

namespace App\Filament\Widgets;

use App\Models\Branch;
use App\Models\InterestCharge;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Cache;

class InterestRevenueWidget extends BaseWidget
{
    public ?int $branchFilter = null;

    protected static ?int $sort = 2;

    protected function getFilters(): ?array
    {
        $branches = Branch::pluck('name', 'id')->toArray();
        return [null => 'Todas las sucursales'] + $branches;
    }

    protected function getStats(): array
    {
        $cacheKey = 'interest_revenue_' . ($this->branchFilter ?? 'all');

        return Cache::remember($cacheKey, 300, function () {
            // Interest revenue this month
            $revenueThisMonth = InterestCharge::where('is_applied', true)
                ->whereMonth('charge_date', now()->month)
                ->whereYear('charge_date', now()->year)
                ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                ->sum('interest_amount');

            // Interest revenue last month
            $revenueLastMonth = InterestCharge::where('is_applied', true)
                ->whereMonth('charge_date', now()->subMonth()->month)
                ->whereYear('charge_date', now()->subMonth()->year)
                ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                ->sum('interest_amount');

            // Total interest revenue (all time)
            $totalRevenue = InterestCharge::where('is_applied', true)
                ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                ->sum('interest_amount');

            // Count of interest charges this month
            $chargesThisMonth = InterestCharge::where('is_applied', true)
                ->whereMonth('charge_date', now()->month)
                ->whereYear('charge_date', now()->year)
                ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                ->count();

            // Waived interest charges (not applied)
            $waivedAmount = InterestCharge::where('is_applied', false)
                ->whereMonth('charge_date', now()->month)
                ->whereYear('charge_date', now()->year)
                ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                ->sum('interest_amount');

            // Calculate percentage change
            $revenueChange = $revenueLastMonth > 0
                ? (($revenueThisMonth - $revenueLastMonth) / $revenueLastMonth) * 100
                : 0;

            // Get last 6 months for chart
            $chartData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = now()->subMonths($i);
                $chartData[] = InterestCharge::where('is_applied', true)
                    ->whereMonth('charge_date', $month->month)
                    ->whereYear('charge_date', $month->year)
                    ->when($this->branchFilter, fn ($query) => $query->whereHas('loan', fn ($q) => $q->where('branch_id', $this->branchFilter)))
                    ->sum('interest_amount');
            }

            return [
                Stat::make('Intereses del Mes', 'Q' . number_format($revenueThisMonth, 2))
                    ->description(($revenueChange >= 0 ? '+' : '') . number_format($revenueChange, 1) . '% vs mes pasado')
                    ->descriptionIcon($revenueChange >= 0 ? 'heroicon-m-arrow-trending-up' : 'heroicon-m-arrow-trending-down')
                    ->color($revenueChange >= 0 ? 'success' : 'danger')
                    ->chart($chartData),

                Stat::make('Total Intereses (Todo el tiempo)', 'Q' . number_format($totalRevenue, 2))
                    ->description($chargesThisMonth . ' cargos este mes')
                    ->descriptionIcon('heroicon-m-calculator')
                    ->color('info'),

                Stat::make('Intereses Condonados', 'Q' . number_format($waivedAmount, 2))
                    ->description('Cargos no aplicados este mes')
                    ->descriptionIcon('heroicon-m-x-circle')
                    ->color('warning'),
            ];
        });
    }
}
