<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura de Venta - {{ $sale->sale_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.6;
            color: #333;
            margin: 40px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #000;
        }
        .header p {
            margin: 5px 0;
            color: #666;
        }
        .invoice-number {
            font-size: 18px;
            font-weight: bold;
            color: #000;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 10px;
            border: 1px solid #ddd;
        }
        .info-table td:first-child {
            font-weight: bold;
            width: 40%;
            background-color: #f5f5f5;
        }
        .section-title {
            font-size: 16px;
            font-weight: bold;
            margin-top: 20px;
            margin-bottom: 15px;
            color: #000;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .item-details {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin: 20px 0;
        }
        .item-details h3 {
            margin-top: 0;
            color: #000;
        }
        .pricing-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .pricing-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .pricing-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .pricing-table tr:last-child {
            border-top: 2px solid #333;
            font-size: 18px;
            background-color: #f0f0f0;
        }
        .total-box {
            background-color: #f0f0f0;
            border: 2px solid #333;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .total-box .label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .total-box .amount {
            font-size: 32px;
            font-weight: bold;
            color: #000;
        }
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .signature-section {
            margin-top: 60px;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 50px;
        }
        .signature-box.right {
            float: right;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CASA DE EMPEÑO</h1>
        <p>Factura de Venta</p>
        <p class="invoice-number">{{ $sale->sale_number }}</p>
        @if($sale->invoice_number)
        <p>Número de Factura: {{ $sale->invoice_number }}</p>
        @endif
    </div>

    <div class="section-title">INFORMACIÓN DE LA VENTA</div>
    <table class="info-table">
        <tr>
            <td>Fecha de Venta:</td>
            <td>{{ $sale->sale_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Método de Pago:</td>
            <td>{{ $sale->payment_method }}</td>
        </tr>
        <tr>
            <td>Estado:</td>
            <td>{{ $sale->status }}</td>
        </tr>
        @if($sale->delivery_date)
        <tr>
            <td>Fecha de Entrega:</td>
            <td>{{ $sale->delivery_date->format('d/m/Y') }}</td>
        </tr>
        @endif
    </table>

    @if($sale->customer)
    <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
    <table class="info-table">
        <tr>
            <td>Nombre Completo:</td>
            <td>{{ $sale->customer->full_name }}</td>
        </tr>
        <tr>
            <td>Documento de Identidad:</td>
            <td>{{ $sale->customer->identity_type }}: {{ $sale->customer->identity_number }}</td>
        </tr>
        <tr>
            <td>Teléfono:</td>
            <td>{{ $sale->customer->phone }}</td>
        </tr>
        @if($sale->customer->address)
        <tr>
            <td>Dirección:</td>
            <td>{{ $sale->customer->address }}</td>
        </tr>
        @endif
    </table>
    @endif

    <div class="section-title">DETALLE DEL ARTÍCULO</div>
    <div class="item-details">
        <h3>{{ $sale->item->name }}</h3>
        <table style="width: 100%; margin-top: 10px;">
            <tr>
                <td style="width: 30%;"><strong>Categoría:</strong></td>
                <td>{{ $sale->item->category }}</td>
            </tr>
            @if($sale->item->brand)
            <tr>
                <td><strong>Marca:</strong></td>
                <td>{{ $sale->item->brand }}</td>
            </tr>
            @endif
            @if($sale->item->model)
            <tr>
                <td><strong>Modelo:</strong></td>
                <td>{{ $sale->item->model }}</td>
            </tr>
            @endif
            @if($sale->item->serial_number)
            <tr>
                <td><strong>Número de Serie:</strong></td>
                <td>{{ $sale->item->serial_number }}</td>
            </tr>
            @endif
            <tr>
                <td><strong>Condición:</strong></td>
                <td>{{ $sale->item->condition }}</td>
            </tr>
            @if($sale->item->description)
            <tr>
                <td><strong>Descripción:</strong></td>
                <td>{{ $sale->item->description }}</td>
            </tr>
            @endif
        </table>
    </div>

    <div class="section-title">DETALLE DE PRECIOS</div>
    <table class="pricing-table">
        <tr>
            <td>Precio de Venta:</td>
            <td>${{ number_format($sale->sale_price, 2) }}</td>
        </tr>
        @if($sale->discount > 0)
        <tr>
            <td>Descuento:</td>
            <td>-${{ number_format($sale->discount, 2) }}</td>
        </tr>
        @endif
        <tr>
            <td><strong>PRECIO FINAL:</strong></td>
            <td><strong>${{ number_format($sale->final_price, 2) }}</strong></td>
        </tr>
    </table>

    <div class="total-box">
        <div class="label">TOTAL A PAGAR</div>
        <div class="amount">${{ number_format($sale->final_price, 2) }}</div>
    </div>

    @if($sale->notes)
    <div class="section-title">NOTAS</div>
    <p>{{ $sale->notes }}</p>
    @endif

    <div class="section-title">TÉRMINOS Y CONDICIONES</div>
    <p style="text-align: justify; font-size: 11px;">
        El artículo se vende en el estado en que se encuentra. El comprador acepta que ha inspeccionado el artículo
        y está satisfecho con su condición. No se aceptan devoluciones después de completada la venta.
        La Casa de Empeño no se hace responsable por defectos ocultos o fallas posteriores a la venta.
    </p>

    <div class="signature-section">
        <div class="signature-box">
            <p>_______________________________</p>
            <p><strong>EL COMPRADOR</strong></p>
            @if($sale->customer)
            <p>{{ $sale->customer->full_name }}</p>
            <p>{{ $sale->customer->identity_type }}: {{ $sale->customer->identity_number }}</p>
            @endif
        </div>

        <div class="signature-box right">
            <p>_______________________________</p>
            <p><strong>LA CASA DE EMPEÑO</strong></p>
            <p>Representante Legal</p>
        </div>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Este documento fue generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Casa de Empeño - Todos los derechos reservados</p>
        <p>Gracias por su compra</p>
    </div>
</body>
</html>
