<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class PaymentsExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $payments)
    {
    }

    public function collection(): Collection
    {
        return $this->payments->map(fn($payment) => [
            'Número' => $payment->payment_number,
            'Préstamo' => $payment->loan->loan_number ?? '',
            'Cliente' => $payment->loan->customer->full_name ?? '',
            'Monto' => $payment->amount,
            'Fecha' => $payment->payment_date->format('d/m/Y'),
            'Método' => $payment->payment_method,
        ]);
    }

    public function headings(): array
    {
        return ['Número', 'Préstamo', 'Cliente', 'Monto', 'Fecha', 'Método'];
    }
}
