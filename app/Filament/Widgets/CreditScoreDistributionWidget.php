<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Cache;

class CreditScoreDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Distribución de Puntaje de Crédito';

    protected static ?int $sort = 3;

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        return Cache::remember('credit_score_distribution', 600, function () {
            // Count customers in each credit score range
            $excellent = Customer::where('credit_score', '>=', 750)->count();
            $good = Customer::whereBetween('credit_score', [650, 749])->count();
            $fair = Customer::whereBetween('credit_score', [550, 649])->count();
            $poor = Customer::where('credit_score', '<', 550)->where('credit_score', '>', 0)->count();
            $noScore = Customer::whereNull('credit_score')->orWhere('credit_score', 0)->count();

            return [
                'datasets' => [
                    [
                        'label' => 'Clientes',
                        'data' => [$excellent, $good, $fair, $poor, $noScore],
                        'backgroundColor' => [
                            'rgb(34, 197, 94)',   // green - excellent
                            'rgb(59, 130, 246)',  // blue - good
                            'rgb(234, 179, 8)',   // yellow - fair
                            'rgb(239, 68, 68)',   // red - poor
                            'rgb(156, 163, 175)', // gray - no score
                        ],
                    ],
                ],
                'labels' => [
                    'Excelente (750+)',
                    'Bueno (650-749)',
                    'Regular (550-649)',
                    'Bajo (<550)',
                    'Sin Calcular',
                ],
            ];
        });
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
        ];
    }
}
