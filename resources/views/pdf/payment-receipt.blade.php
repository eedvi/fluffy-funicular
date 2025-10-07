@extends('pdf.layout')

@section('title', 'Recibo de Pago - ' . $payment->payment_number)

@section('footer-text', 'Recibo de Pago')

@section('content')
    <div class="document-title">Recibo de Pago</div>
    <div class="document-number">{{ $payment->payment_number }}</div>

    <!-- Payment Info -->
    <div class="info-box">
        <div class="info-box-title">Información del Pago</div>
        <div class="info-row">
            <span class="info-label">Fecha de Pago:</span>
            <span class="info-value"><strong>{{ $payment->payment_date->format('d/m/Y H:i') }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Método de Pago:</span>
            <span class="info-value">{{ ucfirst($payment->payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                @if($payment->status === 'Completado')
                    <strong style="color: #10b981;">✓ {{ $payment->status }}</strong>
                @else
                    <strong style="color: #f59e0b;">⏳ {{ $payment->status }}</strong>
                @endif
            </span>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="info-box">
        <div class="info-box-title">Datos del Cliente</div>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $payment->loan->customer->first_name }} {{ $payment->loan->customer->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">DPI:</span>
            <span class="info-value">{{ $payment->loan->customer->identity_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Teléfono:</span>
            <span class="info-value">{{ $payment->loan->customer->phone }}</span>
        </div>
    </div>

    <!-- Loan Information -->
    <div class="info-box">
        <div class="info-box-title">Préstamo Relacionado</div>
        <div class="info-row">
            <span class="info-label">Nº de Préstamo:</span>
            <span class="info-value"><strong>{{ $payment->loan->loan_number }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Artículo:</span>
            <span class="info-value">{{ $payment->loan->item->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Fecha de Vencimiento:</span>
            <span class="info-value">{{ $payment->loan->due_date->format('d/m/Y') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total del Préstamo:</span>
            <span class="info-value">${{ number_format($payment->loan->total_amount, 2) }}</span>
        </div>
    </div>

    <!-- Payment Details -->
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pago realizado el {{ $payment->payment_date->format('d/m/Y') }}</td>
                <td class="text-right"><strong>${{ number_format($payment->amount, 2) }}</strong></td>
            </tr>
        </tbody>
    </table>

    <!-- Payment Summary -->
    <div class="totals-box">
        <div class="total-row">
            <span class="total-label">Total Pagado en esta Transacción:</span>
            <span class="total-value"><strong>${{ number_format($payment->amount, 2) }}</strong></span>
        </div>
    </div>

    <!-- Loan Balance -->
    <div class="info-box" style="margin-top: 20px;">
        <div class="info-box-title">Estado Actual del Préstamo</div>
        <table style="margin: 0;">
            <tr>
                <td style="width: 50%; border-bottom: none;"><strong>Total del Préstamo:</strong></td>
                <td style="width: 50%; border-bottom: none; text-align: right;">${{ number_format($payment->loan->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td style="border-bottom: none;"><strong>Total Pagado (incluyendo este pago):</strong></td>
                <td style="border-bottom: none; text-align: right; color: #10b981;">${{ number_format($payment->loan->amount_paid, 2) }}</td>
            </tr>
            <tr>
                <td style="border-bottom: none;"><strong>Saldo Pendiente:</strong></td>
                <td style="border-bottom: none; text-align: right; color: {{ $payment->loan->balance_remaining > 0 ? '#f59e0b' : '#10b981' }};">
                    <strong>${{ number_format($payment->loan->balance_remaining, 2) }}</strong>
                </td>
            </tr>
        </table>
    </div>

    @if($payment->loan->balance_remaining <= 0)
    <div class="notes-section" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">✅ PRÉSTAMO PAGADO COMPLETAMENTE</div>
        <div class="notes-content">
            <p>Este préstamo ha sido liquidado en su totalidad. El artículo en garantía puede ser retirado presentando este comprobante.</p>
        </div>
    </div>
    @else
    <div class="notes-section">
        <div class="notes-title">💡 INFORMACIÓN IMPORTANTE:</div>
        <div class="notes-content">
            <p style="margin-bottom: 8px;">
                <strong>Saldo Pendiente:</strong> Aún resta pagar <strong>${{ number_format($payment->loan->balance_remaining, 2) }}</strong>
                para liquidar completamente este préstamo.
            </p>
            <p style="margin-bottom: 0px;">
                <strong>Próximo Vencimiento:</strong> {{ $payment->loan->due_date->format('d/m/Y') }}.
                Recuerde que puede realizar pagos parciales en cualquier momento.
            </p>
        </div>
    </div>
    @endif

    @if($payment->notes)
    <div class="notes-section" style="background-color: #f3f4f6; border-color: #9ca3af; margin-top: 15px;">
        <div class="notes-title" style="color: #4b5563;">📝 NOTAS:</div>
        <div class="notes-content">{{ $payment->notes }}</div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Firma del Cliente</strong><br>
                {{ $payment->loan->customer->first_name }} {{ $payment->loan->customer->last_name }}<br>
                DPI: {{ $payment->loan->customer->identity_number }}
            </div>
        </div>
        <div class="signature-box">
            <div class="signature-line">
                <strong>Casa de Empeño</strong><br>
                Representante Autorizado<br>
                {{ $branch->name }}
            </div>
        </div>
    </div>

    <div style="margin-top: 20px; text-align: center; font-size: 9pt; color: #999;">
        Conserve este recibo como comprobante de pago
    </div>
@endsection
