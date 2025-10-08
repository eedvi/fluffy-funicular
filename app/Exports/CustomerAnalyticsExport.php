<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class CustomerAnalyticsExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $customerData)
    {
    }

    public function collection(): Collection
    {
        return $this->customerData->map(fn($item) => [
            'Cliente' => $item['customer']->full_name,
            'DPI' => $item['customer']->identity_number,
            'Teléfono' => $item['customer']->phone,
            'Total Préstamos' => $item['total_loans'],
            'Préstamos Activos' => $item['active_loans'],
            'Préstamos Pagados' => $item['paid_loans'],
            'Total Prestado' => $item['total_borrowed'],
            'Total Comprado' => $item['total_purchased'],
            'Volumen Total' => $item['total_business'],
        ]);
    }

    public function headings(): array
    {
        return ['Cliente', 'DPI', 'Teléfono', 'Total Préstamos', 'Préstamos Activos', 'Préstamos Pagados', 'Total Prestado', 'Total Comprado', 'Volumen Total'];
    }
}
