@extends('pdf.ticket-layout')

@section('title', 'Recibo de Pago - ' . $payment->payment_number)

@section('footer-text', 'Recibo de Pago')

@section('content')
    <div class="document-title">RECIBO DE PAGO</div>
    <div class="document-number">{{ $payment->payment_number }}</div>

    <!-- Payment Info -->
    <div class="info-section">
        <div class="info-title">Pago</div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $payment->payment_date->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Método:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translatePaymentMethod($payment->payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                @if($payment->status === 'completed')
                    <strong style="color: #10b981;">✓ Completado</strong>
                @else
                    <strong>{{ \App\Helpers\TranslationHelper::translatePaymentStatus($payment->status) }}</strong>
                @endif
            </span>
        </div>
    </div>

    <!-- Customer Info -->
    <div class="info-section">
        <div class="info-title">Cliente</div>
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

    <!-- Loan Info -->
    <div class="info-section">
        <div class="info-title">Préstamo</div>
        <div class="info-row">
            <span class="info-label">Nº Préstamo:</span>
            <span class="info-value"><strong>{{ $payment->loan->loan_number }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Artículo:</span>
            <span class="info-value">{{ $payment->loan->item->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Vencimiento:</span>
            <span class="info-value">{{ $payment->loan->due_date->format('d/m/Y') }}</span>
        </div>
    </div>

    <!-- Payment Amount -->
    <div class="total-box">
        <div class="total-row">
            <span class="total-label">MONTO PAGADO:</span>
            <span class="total-value">Q{{ number_format($payment->amount, 2) }}</span>
        </div>
    </div>

    <!-- Loan Balance -->
    <div class="balance-box">
        <div style="font-weight: bold; margin-bottom: 2mm; font-size: 8pt;">Estado Actual del Préstamo</div>

        <div class="balance-row" style="background-color: #f3f4f6; padding: 1mm; margin-bottom: 1mm;">
            <span class="balance-label" style="font-weight: bold;">Capital Original:</span>
            <span class="balance-value">Q{{ number_format($payment->loan->loan_amount, 2) }}</span>
        </div>

        <div class="balance-row">
            <span class="balance-label">Capital Restante:</span>
            <span class="balance-value" style="color: {{ $payment->loan->principal_remaining > 0 ? '#f59e0b' : '#10b981' }};">
                Q{{ number_format($payment->loan->principal_remaining, 2) }}
            </span>
        </div>

        <div class="balance-row">
            <span class="balance-label">Interés Acumulado:</span>
            <span class="balance-value" style="color: #3b82f6;">Q{{ number_format($payment->loan->interest_amount, 2) }}</span>
        </div>

        <div class="balance-row" style="border-top: 1px solid #999; padding-top: 1mm; margin-top: 1mm;">
            <span class="balance-label" style="font-weight: bold;">TOTAL A PAGAR:</span>
            <span class="balance-value" style="color: {{ $payment->loan->total_amount > 0 ? '#f59e0b' : '#10b981' }}; font-weight: bold;">
                Q{{ number_format($payment->loan->total_amount, 2) }}
            </span>
        </div>

        <div class="balance-row" style="background-color: #e0f2fe; padding: 1mm; margin-top: 1mm; font-size: 7pt;">
            <span class="balance-label">Total Pagado Acumulado:</span>
            <span class="balance-value" style="color: #10b981;">Q{{ number_format($payment->loan->amount_paid, 2) }}</span>
        </div>
    </div>

    @if($payment->loan->principal_remaining <= 0)
    <div class="notes-box" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">✅ PRÉSTAMO PAGADO</div>
        <p>Este préstamo ha sido liquidado. Puede retirar su artículo presentando este comprobante.</p>
    </div>
    @else
    <div class="notes-box" style="background-color: #fef3c7; border-color: #f59e0b;">
        <div class="notes-title" style="color: #d97706;">ℹ️ INFORMACIÓN IMPORTANTE:</div>
        <p style="margin-bottom: 2mm;"><strong>Capital restante:</strong> Q{{ number_format($payment->loan->principal_remaining, 2) }}</p>
        <p style="margin-bottom: 2mm;"><strong>Interés actual:</strong> Q{{ number_format($payment->loan->interest_amount, 2) }} ({{ number_format($payment->loan->interest_rate, 2) }}%)</p>
        <p style="margin-bottom: 2mm;"><strong>Total a pagar:</strong> Q{{ number_format($payment->loan->total_amount, 2) }}</p>
        <p style="margin-bottom: 0mm;"><strong>Vencimiento:</strong> {{ $payment->loan->due_date->format('d/m/Y') }}</p>
    </div>

    <div class="notes-box" style="font-size: 6pt; background-color: #f3f4f6; border-color: #9ca3af;">
        <div class="notes-title" style="font-size: 6.5pt;">💡 CÓMO FUNCIONA EL INTERÉS:</div>
        <p style="margin: 0;">Los pagos se aplican <strong>primero a intereses</strong>, luego a capital. El interés se recalcula sobre el capital restante después de cada pago.</p>
    </div>
    @endif

    @if($payment->notes)
    <div class="notes-box">
        <div class="notes-title">NOTAS:</div>
        <p>{{ $payment->notes }}</p>
    </div>
    @endif

    <!-- Signature -->
    <div class="signature-line">
        <strong>Firma del Cliente</strong><br>
        {{ $payment->loan->customer->first_name }} {{ $payment->loan->customer->last_name }}
    </div>
@endsection
