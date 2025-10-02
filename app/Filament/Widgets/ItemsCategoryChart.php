<?php

namespace App\Filament\Widgets;

use App\Models\Item;
use Filament\Widgets\ChartWidget;

class ItemsCategoryChart extends ChartWidget
{
    protected static ?string $heading = 'Artículos por Categoría';

    protected static ?int $sort = 4;

    protected function getData(): array
    {
        $data = Item::selectRaw('category, COUNT(*) as count')
            ->whereIn('status', ['Disponible', 'En Préstamo', 'Confiscado'])
            ->groupBy('category')
            ->orderBy('count', 'desc')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Artículos',
                    'data' => $data->pluck('count'),
                    'backgroundColor' => [
                        'rgba(251, 191, 36, 0.8)',  // Joyería - yellow
                        'rgba(59, 130, 246, 0.8)',  // Electrónica - blue
                        'rgba(16, 185, 129, 0.8)',  // Herramientas - green
                        'rgba(156, 163, 175, 0.8)', // Otros - gray
                    ],
                ],
            ],
            'labels' => $data->pluck('category'),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
