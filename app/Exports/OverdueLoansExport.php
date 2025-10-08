<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class OverdueLoansExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $loans)
    {
    }

    public function collection(): Collection
    {
        return $this->loans->map(fn($loan) => [
            'Número' => $loan->loan_number,
            'Cliente' => $loan->customer->full_name ?? '',
            'Artículo' => $loan->item->name ?? '',
            'Monto' => $loan->loan_amount,
            'Total' => $loan->total_amount,
            'Saldo' => $loan->balance_remaining,
            'Vencimiento' => $loan->due_date->format('d/m/Y'),
            'Días Vencido' => now()->diffInDays($loan->due_date),
        ]);
    }

    public function headings(): array
    {
        return ['Número', 'Cliente', 'Artículo', 'Monto', 'Total', 'Saldo', 'Vencimiento', 'Días Vencido'];
    }
}
