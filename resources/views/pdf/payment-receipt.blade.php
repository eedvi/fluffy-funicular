<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo de Pago - {{ $payment->payment_number }}</title>
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
        .receipt-number {
            font-size: 18px;
            font-weight: bold;
            color: #000;
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
        .amount-box {
            background-color: #f0f0f0;
            border: 2px solid #333;
            padding: 20px;
            text-align: center;
            margin: 30px 0;
        }
        .amount-box .label {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .amount-box .amount {
            font-size: 32px;
            font-weight: bold;
            color: #000;
        }
        .balance-table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        .balance-table td {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .balance-table td:last-child {
            text-align: right;
            font-weight: bold;
        }
        .balance-table tr:last-child {
            border-top: 2px solid #333;
            font-size: 16px;
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
            text-align: center;
        }
        .signature-box {
            display: inline-block;
            width: 45%;
            text-align: center;
            border-top: 1px solid #333;
            padding-top: 10px;
            margin-top: 50px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CASA DE EMPEÑO</h1>
        <p>Recibo de Pago</p>
        <p class="receipt-number">{{ $payment->payment_number }}</p>
    </div>

    <div class="section-title">INFORMACIÓN DEL PAGO</div>
    <table class="info-table">
        <tr>
            <td>Fecha de Pago:</td>
            <td>{{ $payment->payment_date->format('d/m/Y') }}</td>
        </tr>
        <tr>
            <td>Método de Pago:</td>
            <td>{{ $payment->payment_method }}</td>
        </tr>
        @if($payment->reference_number)
        <tr>
            <td>Número de Referencia:</td>
            <td>{{ $payment->reference_number }}</td>
        </tr>
        @endif
        <tr>
            <td>Estado:</td>
            <td>{{ $payment->status }}</td>
        </tr>
    </table>

    <div class="amount-box">
        <div class="label">MONTO PAGADO</div>
        <div class="amount">${{ number_format($payment->amount, 2) }}</div>
    </div>

    <div class="section-title">INFORMACIÓN DEL PRÉSTAMO</div>
    <table class="info-table">
        <tr>
            <td>Número de Préstamo:</td>
            <td>{{ $payment->loan->loan_number }}</td>
        </tr>
        <tr>
            <td>Cliente:</td>
            <td>{{ $payment->loan->customer->full_name }}</td>
        </tr>
        <tr>
            <td>Documento:</td>
            <td>{{ $payment->loan->customer->identity_type }}: {{ $payment->loan->customer->identity_number }}</td>
        </tr>
        <tr>
            <td>Artículo Empeñado:</td>
            <td>{{ $payment->loan->item->name }}</td>
        </tr>
    </table>

    <div class="section-title">BALANCE DEL PRÉSTAMO</div>
    <table class="balance-table">
        <tr>
            <td>Monto Original del Préstamo:</td>
            <td>${{ number_format($payment->loan->loan_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Interés:</td>
            <td>${{ number_format($payment->loan->interest_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Monto Total:</td>
            <td>${{ number_format($payment->loan->total_amount, 2) }}</td>
        </tr>
        <tr>
            <td>Monto Pagado Previamente:</td>
            <td>${{ number_format($payment->loan->amount_paid - $payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td>Pago Actual:</td>
            <td>${{ number_format($payment->amount, 2) }}</td>
        </tr>
        <tr>
            <td><strong>SALDO PENDIENTE:</strong></td>
            <td><strong>${{ number_format($payment->loan->balance_remaining, 2) }}</strong></td>
        </tr>
    </table>

    @if($payment->notes)
    <div class="section-title">NOTAS</div>
    <p>{{ $payment->notes }}</p>
    @endif

    <div class="signature-section">
        <div class="signature-box">
            <p>_______________________________</p>
            <p><strong>Recibido por</strong></p>
            <p>Casa de Empeño</p>
        </div>
    </div>

    <div class="footer">
        <p>Este documento fue generado el {{ now()->format('d/m/Y H:i:s') }}</p>
        <p>Casa de Empeño - Todos los derechos reservados</p>
        <p>Gracias por su pago</p>
    </div>
</body>
</html>
