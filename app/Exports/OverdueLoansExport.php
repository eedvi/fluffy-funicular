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
        return $this->loans->map(function($loan) {
            $planType = 'Tradicional';
            $overdueReason = 'N/A';
            $daysOverdue = 0;

            if ($loan->payment_plan_type === 'installments') {
                $planType = 'Cuotas';
                $overdueInstallments = $loan->installments->where('status', 'overdue');
                $firstOverdue = $overdueInstallments->first();
                if ($firstOverdue) {
                    $overdueReason = "{$overdueInstallments->count()} cuota(s) vencida(s) desde {$firstOverdue->due_date->format('d/m/Y')}";
                    $daysOverdue = $firstOverdue->days_overdue;
                }
            } elseif ($loan->requires_minimum_payment && $loan->is_at_risk) {
                $planType = 'Pago Mínimo';
                $overdueReason = 'Pago mínimo no realizado';
                if ($loan->next_minimum_payment_date && $loan->next_minimum_payment_date->isPast()) {
                    $daysOverdue = $loan->next_minimum_payment_date->diffInDays(now());
                }
            } else {
                $overdueReason = $loan->due_date ? "Vencido: {$loan->due_date->format('d/m/Y')}" : 'N/A';
                $daysOverdue = $loan->due_date ? $loan->due_date->diffInDays(now()) : 0;
            }

            return [
                'Número' => $loan->loan_number,
                'Cliente' => $loan->customer->full_name ?? '',
                'Teléfono' => $loan->customer->phone ?? '',
                'Artículo' => $loan->item->name ?? '',
                'Plan de Pago' => $planType,
                'Monto' => $loan->loan_amount,
                'Saldo' => $loan->balance_remaining,
                'Motivo Vencido' => $overdueReason,
                'Días Vencido' => $daysOverdue,
            ];
        });
    }

    public function headings(): array
    {
        return ['Número', 'Cliente', 'Teléfono', 'Artículo', 'Plan de Pago', 'Monto', 'Saldo', 'Motivo Vencido', 'Días Vencido'];
    }
}
