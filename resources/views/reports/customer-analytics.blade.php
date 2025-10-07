<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Análisis de Clientes</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; color: #f59e0b; }
        .info { text-align: center; margin-bottom: 20px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 9px; }
        th { background-color: #fef3c7; font-weight: bold; color: #92400e; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; padding: 15px; background-color: #fef3c7; border-radius: 4px; }
        .summary-row { display: table; width: 100%; margin-bottom: 5px; }
        .summary-label { display: table-cell; font-weight: bold; width: 70%; }
        .summary-value { display: table-cell; text-align: right; width: 30%; }
        .note { margin-top: 20px; font-size: 9px; color: #666; font-style: italic; }
    </style>
</head>
<body>
    <h1>ANÁLISIS DE CLIENTES - TOP 50</h1>

    <div class="info">
        <p><strong>Período:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
        <p><em>Mostrando los 50 principales clientes ordenados por volumen de negocio</em></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Cliente</th>
                <th>DPI</th>
                <th>Teléfono</th>
                <th class="text-center">Total<br>Préstamos</th>
                <th class="text-center">Activos</th>
                <th class="text-center">Pagados</th>
                <th class="text-right">Total<br>Prestado</th>
                <th class="text-right">Total<br>Comprado</th>
                <th class="text-right">Volumen<br>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($customerData as $index => $data)
            <tr>
                <td class="text-center">{{ $index + 1 }}</td>
                <td><strong>{{ $data['customer']->full_name }}</strong></td>
                <td>{{ $data['customer']->dpi }}</td>
                <td>{{ $data['customer']->phone }}</td>
                <td class="text-center">{{ $data['total_loans'] }}</td>
                <td class="text-center">{{ $data['active_loans'] }}</td>
                <td class="text-center">{{ $data['paid_loans'] }}</td>
                <td class="text-right">${{ number_format($data['total_borrowed'], 2) }}</td>
                <td class="text-right">${{ number_format($data['total_purchased'], 2) }}</td>
                <td class="text-right"><strong>${{ number_format($data['total_business'], 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3 style="margin-top: 0; color: #92400e;">Resumen del Análisis</h3>

        <div class="summary-row">
            <span class="summary-label">Total de Clientes Analizados:</span>
            <span class="summary-value">{{ $totals['total_customers'] }} clientes</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total de Préstamos:</span>
            <span class="summary-value">{{ $totals['total_loans'] }} préstamos</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total Monto Prestado:</span>
            <span class="summary-value">${{ number_format($totals['total_borrowed'], 2) }}</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total Monto en Compras:</span>
            <span class="summary-value">${{ number_format($totals['total_purchased'], 2) }}</span>
        </div>

        <div class="summary-row" style="border-top: 2px solid #f59e0b; padding-top: 10px; margin-top: 10px; font-size: 12px; color: #92400e;">
            <span class="summary-label">VOLUMEN TOTAL DE NEGOCIO:</span>
            <span class="summary-value">${{ number_format($totals['total_business'], 2) }}</span>
        </div>
    </div>

    <div class="note">
        <p><strong>Nota:</strong> Este reporte muestra únicamente los 50 clientes con mayor volumen de negocio durante el período seleccionado.</p>
        <p>El volumen total incluye la suma de préstamos otorgados y compras realizadas.</p>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #999;">
        <p>Casa de Empeño - Sistema de Gestión de Clientes</p>
    </div>
</body>
</html>
