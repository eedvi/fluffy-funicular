<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ActiveLoansExport implements FromCollection, WithHeadings
{
    public function __construct(private Collection $loans)
    {
    }

    public function collection(): Collection
    {
        return $this->loans->map(function($loan) {
            $planType = 'Tradicional';
            $nextPayment = 'N/A';

            if ($loan->payment_plan_type === 'installments') {
                $planType = "Cuotas ({$loan->number_of_installments})";
                $nextInstallment = $loan->installments->where('status', '!=', 'paid')->first();
                $nextPayment = $nextInstallment ? $nextInstallment->due_date->format('d/m/Y') : 'N/A';
            } elseif ($loan->requires_minimum_payment) {
                $planType = 'Pago Mínimo';
                $nextPayment = $loan->next_minimum_payment_date ? $loan->next_minimum_payment_date->format('d/m/Y') : 'N/A';
            } else {
                $nextPayment = $loan->due_date ? $loan->due_date->format('d/m/Y') : 'N/A';
            }

            return [
                'Número' => $loan->loan_number,
                'Cliente' => $loan->customer->full_name ?? '',
                'Artículo' => $loan->item->name ?? '',
                'Plan de Pago' => $planType,
                'Monto' => $loan->loan_amount,
                'Total' => $loan->total_amount,
                'Saldo' => $loan->balance_remaining,
                'Próximo Pago' => $nextPayment,
            ];
        });
    }

    public function headings(): array
    {
        return ['Número', 'Cliente', 'Artículo', 'Plan de Pago', 'Monto', 'Total', 'Saldo', 'Próximo Pago'];
    }
}
