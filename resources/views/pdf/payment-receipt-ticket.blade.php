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
        <div style="font-weight: bold; margin-bottom: 2mm; font-size: 8pt;">Estado del Préstamo</div>
        <div class="balance-row">
            <span class="balance-label">Total Préstamo:</span>
            <span class="balance-value">Q{{ number_format($payment->loan->total_amount, 2) }}</span>
        </div>
        <div class="balance-row">
            <span class="balance-label">Total Pagado:</span>
            <span class="balance-value" style="color: #10b981;">Q{{ number_format($payment->loan->amount_paid, 2) }}</span>
        </div>
        <div class="balance-row" style="border-top: 1px solid #999; padding-top: 1mm;">
            <span class="balance-label">SALDO PENDIENTE:</span>
            <span class="balance-value" style="color: {{ $payment->loan->balance_remaining > 0 ? '#f59e0b' : '#10b981' }}; font-weight: bold;">
                Q{{ number_format($payment->loan->balance_remaining, 2) }}
            </span>
        </div>
    </div>

    @if($payment->loan->balance_remaining <= 0)
    <div class="notes-box" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">✅ PRÉSTAMO PAGADO</div>
        <p>Este préstamo ha sido liquidado. Puede retirar su artículo presentando este comprobante.</p>
    </div>
    @else
    <div class="notes-box">
        <div class="notes-title">INFORMACIÓN:</div>
        <p>Saldo pendiente: <strong>Q{{ number_format($payment->loan->balance_remaining, 2) }}</strong></p>
        <p>Vencimiento: {{ $payment->loan->due_date->format('d/m/Y') }}</p>
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
