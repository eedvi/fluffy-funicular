<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ingresos por Sucursal</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; color: #f59e0b; }
        .info { text-align: center; margin-bottom: 20px; color: #666; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #fef3c7; font-weight: bold; color: #92400e; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; padding: 15px; background-color: #fef3c7; border-radius: 4px; }
        .summary-row { display: table; width: 100%; margin-bottom: 5px; }
        .summary-label { display: table-cell; font-weight: bold; width: 70%; }
        .summary-value { display: table-cell; text-align: right; width: 30%; }
        .grand-total { font-size: 14px; color: #92400e; border-top: 2px solid #f59e0b; padding-top: 10px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>REPORTE DE INGRESOS POR SUCURSAL</h1>

    <div class="info">
        <p><strong>Período:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Sucursal</th>
                <th class="text-center">Préstamos<br>Emitidos</th>
                <th class="text-right">Ingresos por<br>Intereses</th>
                <th class="text-center">Ventas<br>Realizadas</th>
                <th class="text-right">Ingresos por<br>Ventas</th>
                <th class="text-right">Pagos<br>Recibidos</th>
                <th class="text-right">Total<br>Ingresos</th>
            </tr>
        </thead>
        <tbody>
            @foreach($revenueData as $data)
            <tr>
                <td><strong>{{ $data['branch']->name }}</strong></td>
                <td class="text-center">{{ $data['loans_issued'] }}</td>
                <td class="text-right">Q{{ number_format($data['loans_revenue'], 2) }}</td>
                <td class="text-center">{{ $data['sales_count'] }}</td>
                <td class="text-right">Q{{ number_format($data['sales_revenue'], 2) }}</td>
                <td class="text-right">Q{{ number_format($data['payments_received'], 2) }}</td>
                <td class="text-right"><strong>Q{{ number_format($data['total_revenue'], 2) }}</strong></td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <h3 style="margin-top: 0; color: #92400e;">Resumen General</h3>

        <div class="summary-row">
            <span class="summary-label">Total de Préstamos Emitidos:</span>
            <span class="summary-value">{{ $totals['loans_issued'] }} préstamos</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total Ingresos por Intereses:</span>
            <span class="summary-value">Q{{ number_format($totals['loans_revenue'], 2) }}</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total de Ventas Realizadas:</span>
            <span class="summary-value">{{ $totals['sales_count'] }} ventas</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total Ingresos por Ventas:</span>
            <span class="summary-value">Q{{ number_format($totals['sales_revenue'], 2) }}</span>
        </div>

        <div class="summary-row">
            <span class="summary-label">Total Pagos Recibidos:</span>
            <span class="summary-value">Q{{ number_format($totals['payments_received'], 2) }}</span>
        </div>

        <div class="summary-row grand-total">
            <span class="summary-label">TOTAL GENERAL DE INGRESOS:</span>
            <span class="summary-value">Q{{ number_format($totals['total_revenue'], 2) }}</span>
        </div>
    </div>

    <div style="margin-top: 30px; text-align: center; font-size: 9px; color: #999;">
        <p>Este reporte muestra los ingresos generados por cada sucursal durante el período especificado.</p>
        <p>Casa de Empeño - Sistema de Gestión</p>
    </div>
</body>
</html>
