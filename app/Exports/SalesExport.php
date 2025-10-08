<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SalesExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $sales)
    {
    }

    public function collection(): Collection
    {
        return $this->sales->map(fn($sale) => [
            'Número' => $sale->sale_number,
            'Cliente' => $sale->customer->full_name ?? 'Sin Cliente',
            'Artículo' => $sale->item->name ?? '',
            'Precio' => $sale->sale_price,
            'Descuento' => $sale->discount,
            'Total' => $sale->final_price,
            'Fecha' => $sale->sale_date->format('d/m/Y'),
            'Estado' => $sale->status,
        ]);
    }

    public function headings(): array
    {
        return ['Número', 'Cliente', 'Artículo', 'Precio', 'Descuento', 'Total', 'Fecha', 'Estado'];
    }
}
