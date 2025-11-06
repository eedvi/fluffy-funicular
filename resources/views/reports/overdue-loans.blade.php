<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos Vencidos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; color: #d32f2f; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #ffebee; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; font-weight: bold; }
        .overdue { color: #d32f2f; }
    </style>
</head>
<body>
    <h1>REPORTE DE PRÉSTAMOS VENCIDOS</h1>
    <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>No. Préstamo</th>
                <th>Cliente</th>
                <th>Teléfono</th>
                <th>Artículo</th>
                <th>Plan de Pago</th>
                <th class="text-right">Saldo</th>
                <th>Motivo Vencido</th>
                <th>Días Vencido</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $loan)
            <tr>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->customer->full_name }}</td>
                <td>{{ $loan->customer->phone }}</td>
                <td>{{ $loan->item->name }}</td>
                <td>
                    @if($loan->payment_plan_type === 'installments')
                        Cuotas
                    @elseif($loan->requires_minimum_payment)
                        Pago Mínimo
                    @else
                        Tradicional
                    @endif
                </td>
                <td class="text-right">Q{{ number_format($loan->balance_remaining, 2) }}</td>
                <td class="overdue">
                    @if($loan->payment_plan_type === 'installments')
                        @php
                            $overdueInstallments = $loan->installments->where('status', 'overdue');
                            $firstOverdue = $overdueInstallments->first();
                        @endphp
                        {{ $overdueInstallments->count() }} cuota(s) vencida(s)
                        @if($firstOverdue)
                            <br><small>Desde: {{ $firstOverdue->due_date->format('d/m/Y') }}</small>
                        @endif
                    @elseif($loan->requires_minimum_payment && $loan->is_at_risk)
                        Pago mínimo no realizado
                        @if($loan->grace_period_end_date)
                            <br><small>Gracia hasta: {{ $loan->grace_period_end_date->format('d/m/Y') }}</small>
                        @endif
                    @else
                        Vencido: {{ $loan->due_date ? $loan->due_date->format('d/m/Y') : 'N/A' }}
                    @endif
                </td>
                <td class="overdue">
                    @if($loan->payment_plan_type === 'installments')
                        @php
                            $firstOverdue = $loan->installments->where('status', 'overdue')->first();
                        @endphp
                        {{ $firstOverdue ? $firstOverdue->days_overdue : 0 }}
                    @elseif($loan->requires_minimum_payment && $loan->next_minimum_payment_date)
                        {{ $loan->next_minimum_payment_date->isPast() ? $loan->next_minimum_payment_date->diffInDays(now()) : 0 }}
                    @else
                        {{ $loan->due_date ? $loan->due_date->diffInDays(now()) : 0 }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de préstamos vencidos: {{ $loans->count() }}</p>
        <p>Saldo total vencido: Q{{ number_format($loans->sum('balance_remaining'), 2) }}</p>
    </div>
</body>
</html>
