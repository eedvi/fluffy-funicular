<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Préstamos Activos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f0f0f0; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>REPORTE DE PRÉSTAMOS ACTIVOS</h1>
    <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>No. Préstamo</th>
                <th>Cliente</th>
                <th>Artículo</th>
                <th class="text-right">Monto</th>
                <th class="text-right">Saldo</th>
                <th>Vencimiento</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($loans as $loan)
            <tr>
                <td>{{ $loan->loan_number }}</td>
                <td>{{ $loan->customer->full_name }}</td>
                <td>{{ $loan->item->name }}</td>
                <td class="text-right">${{ number_format($loan->total_amount, 2) }}</td>
                <td class="text-right">${{ number_format($loan->balance_remaining, 2) }}</td>
                <td>{{ $loan->due_date->format('d/m/Y') }}</td>
                <td>{{ $loan->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de préstamos activos: {{ $loans->count() }}</p>
        <p>Monto total prestado: ${{ number_format($loans->sum('loan_amount'), 2) }}</p>
        <p>Saldo total pendiente: ${{ number_format($loans->sum('balance_remaining'), 2) }}</p>
    </div>
</body>
</html>
