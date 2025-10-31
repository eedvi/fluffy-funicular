<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Inventario</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #fff3e0; font-weight: bold; }
        .text-right { text-align: right; }
        .summary { margin-top: 20px; font-weight: bold; }
        .category-summary { margin-top: 30px; }
        .category-summary table { width: 60%; }
    </style>
</head>
<body>
    <h1>REPORTE DE VALUACIÓN DE INVENTARIO</h1>
    <p>Generado: {{ now()->format('d/m/Y H:i:s') }}</p>

    <table>
        <thead>
            <tr>
                <th>Artículo</th>
                <th>Categoría</th>
                <th>Estado</th>
                <th>Condición</th>
                <th class="text-right">Valor Tasado</th>
                <th class="text-right">Valor Mercado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->name }}</td>
                <td>{{ $item->category->name }}</td>
                <td>{{ \App\Helpers\TranslationHelper::translateItemStatus($item->status) }}</td>
                <td>{{ \App\Helpers\TranslationHelper::translateItemCondition($item->condition) }}</td>
                <td class="text-right">${{ number_format($item->appraised_value, 2) }}</td>
                <td class="text-right">${{ number_format($item->market_value ?? 0, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary">
        <p>Total de artículos: {{ $items->count() }}</p>
        <p>Valor total tasado: ${{ number_format($totalValue, 2) }}</p>
        <p>Valor total de mercado: ${{ number_format($totalMarketValue, 2) }}</p>
    </div>

    <div class="category-summary">
        <h2>Resumen por Categoría</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Valor Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $category => $data)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">${{ number_format($data['total_value'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>
