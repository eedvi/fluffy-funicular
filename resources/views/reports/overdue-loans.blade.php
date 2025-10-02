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
                <th class="text-right">Saldo</th>
                <th>Vencimiento</th>
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
                <td class="text-right">${{ number_format($loan->balance_remaining, 2) }}</td>
                <td class="overdue">{{ $loan->due_date->format('d/m/Y') }}</td>
                <td class="overdue">{{ $loan->due_date->diffInDays(now()) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de préstamos vencidos: {{ $loans->count() }}</p>
        <p>Saldo total vencido: ${{ number_format($loans->sum('balance_remaining'), 2) }}</p>
    </div>
</body>
</html>
