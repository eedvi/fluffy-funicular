<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ConfiscatedItemsExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private Collection $items)
    {
    }

    public function collection(): Collection
    {
        return $this->items;
    }

    public function map($item): array
    {
        return [
            'Nombre' => $item->name,
            'Categoría' => $item->category?->name ?? 'Sin Categoría',
            'Marca' => $item->brand,
            'Modelo' => $item->model,
            'Serie' => $item->serial_number,
            'Cliente Original' => $item->customer?->full_name ?? 'N/A',
            'Sucursal' => $item->branch?->name ?? 'N/A',
            'Fecha Confiscación' => $item->effective_confiscation_date?->format('d/m/Y') ?? 'N/A',
            'Valor Tasado' => number_format($item->appraised_value, 2),
            'Precio Subasta' => $item->auction_price ? number_format($item->auction_price, 2) : 'No establecido',
            'Fecha Subasta' => $item->auction_date?->format('d/m/Y') ?? 'No programada',
            'Notas' => $item->confiscation_notes ?? '',
            'Monto Préstamo' => $item->forfeited_loan ? number_format($item->forfeited_loan->loan_amount, 2) : 'N/A',
            'Saldo Pendiente' => $item->forfeited_loan ? number_format($item->forfeited_loan->balance_remaining, 2) : 'N/A',
        ];
    }

    public function headings(): array
    {
        return [
            'Nombre',
            'Categoría',
            'Marca',
            'Modelo',
            'Serie',
            'Cliente Original',
            'Sucursal',
            'Fecha Confiscación',
            'Valor Tasado',
            'Precio Subasta',
            'Fecha Subasta',
            'Notas',
            'Monto Préstamo',
            'Saldo Pendiente',
        ];
    }
}
