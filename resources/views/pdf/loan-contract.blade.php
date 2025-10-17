<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contrato de Préstamo - {{ $loan->loan_number }}</title>
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
        .contract-info {
            margin-bottom: 30px;
        }
        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .info-table td {
            padding: 8px;
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
            margin-top: 30px;
            margin-bottom: 15px;
            color: #000;
            border-bottom: 1px solid #333;
            padding-bottom: 5px;
        }
        .terms {
            text-align: justify;
            margin-bottom: 20px;
        }
        .terms p {
            margin-bottom: 10px;
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
        .footer {
            text-align: center;
            margin-top: 40px;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CASA DE EMPEÑO</h1>
        <p>Contrato de Préstamo Prendario</p>
        <p>Número: {{ $loan->loan_number }}</p>
    </div>

    <div class="contract-info">
        <div class="section-title">INFORMACIÓN DEL CLIENTE</div>
        <table class="info-table">
            <tr>
                <td>Nombre Completo:</td>
                <td>{{ $loan->customer->full_name }}</td>
            </tr>
            <tr>
                <td>Documento de Identidad:</td>
                <td>{{ $loan->customer->identity_type }}: {{ $loan->customer->identity_number }}</td>
            </tr>
            <tr>
                <td>Teléfono:</td>
                <td>{{ $loan->customer->phone }}</td>
            </tr>
            <tr>
                <td>Dirección:</td>
                <td>{{ $loan->customer->address ?? 'N/A' }}</td>
            </tr>
        </table>

        <div class="section-title">INFORMACIÓN DEL ARTÍCULO EMPEÑADO</div>
        <table class="info-table">
            <tr>
                <td>Artículo:</td>
                <td>{{ $loan->item->name }}</td>
            </tr>
            <tr>
                <td>Categoría:</td>
                <td>{{ $loan->item->category->name }}</td>
            </tr>
            @if($loan->item->brand)
            <tr>
                <td>Marca:</td>
                <td>{{ $loan->item->brand }}</td>
            </tr>
            @endif
            @if($loan->item->model)
            <tr>
                <td>Modelo:</td>
                <td>{{ $loan->item->model }}</td>
            </tr>
            @endif
            @if($loan->item->serial_number)
            <tr>
                <td>Número de Serie:</td>
                <td>{{ $loan->item->serial_number }}</td>
            </tr>
            @endif
            <tr>
                <td>Condición:</td>
                <td>{{ \App\Helpers\TranslationHelper::translateItemCondition($loan->item->condition) }}</td>
            </tr>
            <tr>
                <td>Valor Tasado:</td>
                <td>${{ number_format($loan->item->appraised_value, 2) }}</td>
            </tr>
        </table>

        <div class="section-title">DETALLES DEL PRÉSTAMO</div>
        <table class="info-table">
            <tr>
                <td>Monto del Préstamo:</td>
                <td>GTQ{{ number_format($loan->loan_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Tasa de Interés:</td>
                <td>{{ $loan->interest_rate }}%</td>
            </tr>
            <tr>
                <td>Monto de Interés:</td>
                <td>GTQ{{ number_format($loan->interest_amount, 2) }}</td>
            </tr>
            <tr>
                <td>Monto Total a Pagar:</td>
                <td><strong>GTQ{{ number_format($loan->total_amount, 2) }}</strong></td>
            </tr>
            <tr>
                <td>Plazo:</td>
                <td>{{ $loan->loan_term_days }} días</td>
            </tr>
            <tr>
                <td>Fecha de Inicio:</td>
                <td>{{ $loan->start_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td>Fecha de Vencimiento:</td>
                <td>{{ $loan->due_date->format('d/m/Y') }}</td>
            </tr>
        </table>
    </div>

    <div class="section-title">TÉRMINOS Y CONDICIONES</div>
    <div class="terms">
        <p><strong>PRIMERA:</strong> El cliente entrega en prenda el artículo descrito anteriormente a la Casa de Empeño como garantía del préstamo otorgado.</p>

        <p><strong>SEGUNDA:</strong> El cliente se compromete a pagar el monto total de ${{ number_format($loan->total_amount, 2) }} en o antes de la fecha de vencimiento {{ $loan->due_date->format('d/m/Y') }}.</p>

        <p><strong>TERCERA:</strong> En caso de no realizar el pago dentro del plazo establecido, el cliente acepta que la Casa de Empeño puede retener el artículo empeñado y proceder a su venta para recuperar el monto adeudado.</p>

        <p><strong>CUARTA:</strong> El cliente declara que el artículo empeñado es de su legítima propiedad y está libre de gravámenes, embargos o cualquier otra limitación de dominio.</p>

        <p><strong>QUINTA:</strong> La Casa de Empeño se compromete a mantener el artículo en custodia bajo condiciones adecuadas de seguridad.</p>

        <p><strong>SEXTA:</strong> El cliente puede renovar el préstamo antes de la fecha de vencimiento, sujeto a los términos y condiciones vigentes en ese momento.</p>

        <p><strong>SÉPTIMA:</strong> Ambas partes aceptan los términos establecidos en este contrato y se comprometen a cumplirlos fielmente.</p>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <p>_______________________________</p>
            <p><strong>EL CLIENTE</strong></p>
            <p>{{ $loan->customer->full_name }}</p>
            <p>{{ $loan->customer->identity_type }}: {{ $loan->customer->identity_number }}</p>
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
    </div>
</body>
</html>
