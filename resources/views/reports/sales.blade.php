<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Ventas</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #e8f5e9; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; font-weight: bold; }
    </style>
</head>
<body>
    <h1>REPORTE DE VENTAS</h1>
    <p>Período: {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
    <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>No. Venta</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Artículo</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Descuento</th>
                <th class="text-right">Total</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->sale_number }}</td>
                <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
                <td>{{ $sale->customer ? $sale->customer->full_name : 'N/A' }}</td>
                <td>{{ $sale->item->name }}</td>
                <td class="text-right">${{ number_format($sale->sale_price, 2) }}</td>
                <td class="text-right">${{ number_format($sale->discount, 2) }}</td>
                <td class="text-right">${{ number_format($sale->final_price, 2) }}</td>
                <td>{{ $sale->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de ventas: {{ $sales->count() }}</p>
        <p>Total descuentos: ${{ number_format($totalDiscount, 2) }}</p>
        <p>Total ingresos: ${{ number_format($totalSales, 2) }}</p>
    </div>
</body>
</html>
