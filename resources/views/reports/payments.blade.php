<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Pagos Recibidos</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e3f2fd; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>REPORTE DE PAGOS RECIBIDOS</h1>
    <p>Período: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
    <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>No. Pago</th>
                <th>Fecha</th>
                <th>No. Préstamo</th>
                <th>Cliente</th>
                <th class="text-right">Monto</th>
                <th>Método</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->payment_number }}</td>
                <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
                <td>{{ $payment->loan->loan_number }}</td>
                <td>{{ $payment->loan->customer->full_name }}</td>
                <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->payment_method }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de pagos: {{ $payments->count() }}</p>
        <p>Total recibido: ${{ number_format($totalPayments, 2) }}</p>
    </div>
</body>
</html>
