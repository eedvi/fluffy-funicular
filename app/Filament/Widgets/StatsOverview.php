<?php

namespace App\Filament\Widgets;

use App\Models\Loan;
use App\Models\Sale;
use App\Models\Item;
use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $activeLoans = Loan::where('status', 'Activo')->count();
        $overdueLoans = Loan::where('status', 'Vencido')->count();
        $monthlyRevenue = Sale::whereMonth('sale_date', now()->month)
            ->whereYear('sale_date', now()->year)
            ->sum('final_price');
        $monthlyPayments = Payment::whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year)
            ->where('status', 'Completado')
            ->sum('amount');
        $availableItems = Item::where('status', 'Disponible')->count();
        $totalInventoryValue = Item::whereIn('status', ['Disponible', 'Confiscado'])
            ->sum('appraised_value');

        return [
            Stat::make('Préstamos Activos', $activeLoans)
                ->description('Préstamos en curso')
                ->descriptionIcon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Préstamos Vencidos', $overdueLoans)
                ->description('Requieren atención')
                ->descriptionIcon('heroicon-o-exclamation-triangle')
                ->color('danger'),

            Stat::make('Ingresos del Mes', '$' . number_format($monthlyRevenue + $monthlyPayments, 2))
                ->description('Ventas + Pagos')
                ->descriptionIcon('heroicon-o-currency-dollar')
                ->color('success'),

            Stat::make('Artículos Disponibles', $availableItems)
                ->description('En inventario')
                ->descriptionIcon('heroicon-o-cube')
                ->color('info'),

            Stat::make('Valor Inventario', '$' . number_format($totalInventoryValue, 2))
                ->description('Valor total tasado')
                ->descriptionIcon('heroicon-o-chart-bar')
                ->color('warning'),
        ];
    }
}
