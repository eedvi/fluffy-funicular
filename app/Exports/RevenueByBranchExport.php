<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class RevenueByBranchExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $revenueData)
    {
    }

    public function collection(): Collection
    {
        return $this->revenueData->map(fn($item) => [
            'Sucursal' => $item['branch']->name,
            'Préstamos Emitidos' => $item['loans_issued'],
            'Ingresos por Intereses' => $item['loans_revenue'],
            'Ventas Realizadas' => $item['sales_count'],
            'Ingresos por Ventas' => $item['sales_revenue'],
            'Pagos Recibidos' => $item['payments_received'],
            'Ingresos Totales' => $item['total_revenue'],
        ]);
    }

    public function headings(): array
    {
        return ['Sucursal', 'Préstamos Emitidos', 'Ingresos por Intereses', 'Ventas Realizadas', 'Ingresos por Ventas', 'Pagos Recibidos', 'Ingresos Totales'];
    }
}
