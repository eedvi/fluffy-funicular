<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Artículos Confiscados</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 20px; }
        h1 { text-align: center; font-size: 18px; margin-bottom: 10px; }
        .header-info { text-align: center; margin-bottom: 20px; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        th, td { border: 1px solid #ddd; padding: 6px; text-align: left; }
        th { background-color: #fff3e0; font-weight: bold; font-size: 10px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; background-color: #f9f9f9; padding: 15px; border: 1px solid #ddd; }
        .summary h2 { font-size: 14px; margin-bottom: 10px; }
        .summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
        .summary-item { margin-bottom: 8px; }
        .summary-label { font-weight: bold; }
        .category-summary { margin-top: 20px; }
        .category-summary h2 { font-size: 14px; margin-bottom: 10px; }
        .category-summary table { width: 50%; }
        .auction-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .auction-scheduled { background-color: #4caf50; color: white; }
        .auction-pending { background-color: #ff9800; color: white; }
        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    <h1>REPORTE DE ARTÍCULOS CONFISCADOS</h1>
    <div class="header-info">
        <p><strong>Período:</strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</p>
        <p><strong>Generado:</strong> {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="summary">
        <h2>Resumen General</h2>
        <div class="summary-grid">
            <div>
                <div class="summary-item">
                    <span class="summary-label">Total de Artículos Confiscados:</span> {{ $items->count() }}
                </div>
                <div class="summary-item">
                    <span class="summary-label">Valor Total Tasado:</span> Q{{ number_format($totalValue, 2) }}
                </div>
                <div class="summary-item">
                    <span class="summary-label">Valor Total en Subasta:</span> Q{{ number_format($totalAuctionPrice, 2) }}
                </div>
            </div>
            <div>
                <div class="summary-item">
                    <span class="summary-label">Artículos con Subasta Programada:</span> {{ $itemsWithAuction }}
                </div>
                <div class="summary-item">
                    <span class="summary-label">Artículos sin Subasta:</span> {{ $itemsWithoutAuction }}
                </div>
                <div class="summary-item">
                    <span class="summary-label">Valor Promedio:</span> Q{{ number_format($items->count() > 0 ? $totalValue / $items->count() : 0, 2) }}
                </div>
            </div>
        </div>
    </div>

    <h2 style="margin-top: 25px; font-size: 14px;">Detalle de Artículos Confiscados</h2>

    <table>
        <thead>
            <tr>
                <th>Artículo</th>
                <th>Cliente</th>
                <th>Sucursal</th>
                <th>Fecha Conf.</th>
                <th class="text-right">Valor Tasado</th>
                <th class="text-right">Monto Préstamo</th>
                <th class="text-right">Saldo Pend.</th>
                <th>Estado Subasta</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>
                    <strong>{{ $item->name }}</strong><br>
                    <small>{{ $item->category?->name ?? 'Sin Categoría' }}</small>
                    @if($item->brand || $item->model)
                        <br><small>{{ $item->brand }} {{ $item->model }}</small>
                    @endif
                </td>
                <td>{{ $item->customer?->full_name ?? 'N/A' }}</td>
                <td>{{ $item->branch?->name ?? 'N/A' }}</td>
                <td class="text-center">{{ $item->effective_confiscation_date?->format('d/m/Y') ?? 'N/A' }}</td>
                <td class="text-right">Q{{ number_format($item->appraised_value, 2) }}</td>
                <td class="text-right">
                    @if($item->forfeited_loan)
                        Q{{ number_format($item->forfeited_loan->loan_amount, 2) }}
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-right">
                    @if($item->forfeited_loan)
                        Q{{ number_format($item->forfeited_loan->balance_remaining, 2) }}
                    @else
                        N/A
                    @endif
                </td>
                <td class="text-center">
                    @if($item->auction_date)
                        <span class="auction-badge auction-scheduled">
                            {{ $item->auction_date->format('d/m/Y') }}<br>
                            Q{{ number_format($item->auction_price ?? 0, 2) }}
                        </span>
                    @else
                        <span class="auction-badge auction-pending">Pendiente</span>
                    @endif
                </td>
            </tr>
            @if($item->confiscation_notes)
            <tr>
                <td colspan="8" style="background-color: #f9f9f9; font-size: 9px;">
                    <strong>Notas:</strong> {{ $item->confiscation_notes }}
                </td>
            </tr>
            @endif
            @endforeach
        </tbody>
    </table>

    @if($byCategory->count() > 0)
    <div class="category-summary">
        <h2>Resumen por Categoría</h2>
        <table>
            <thead>
                <tr>
                    <th>Categoría</th>
                    <th class="text-right">Cantidad</th>
                    <th class="text-right">Valor Total</th>
                    <th class="text-right">% del Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($byCategory as $category => $data)
                <tr>
                    <td>{{ $category }}</td>
                    <td class="text-right">{{ $data['count'] }}</td>
                    <td class="text-right">Q{{ number_format($data['total_value'], 2) }}</td>
                    <td class="text-right">{{ number_format(($data['total_value'] / $totalValue) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div style="margin-top: 30px; font-size: 9px; color: #666; text-align: center;">
        <p>Este reporte incluye todos los artículos confiscados en el período seleccionado.</p>
        <p>Los artículos confiscados están disponibles para venta o subasta según la política de la empresa.</p>
    </div>
</body>
</html>
