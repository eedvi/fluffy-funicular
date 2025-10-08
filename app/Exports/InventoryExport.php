<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class InventoryExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $items)
    {
    }

    public function collection(): Collection
    {
        return $this->items->map(fn($item) => [
            'Nombre' => $item->name,
            'Categoría' => $item->category,
            'Marca' => $item->brand,
            'Modelo' => $item->model,
            'Valor Tasado' => $item->appraised_value,
            'Valor Mercado' => $item->market_value,
            'Estado' => $item->status,
        ]);
    }

    public function headings(): array
    {
        return ['Nombre', 'Categoría', 'Marca', 'Modelo', 'Valor Tasado', 'Valor Mercado', 'Estado'];
    }
}
