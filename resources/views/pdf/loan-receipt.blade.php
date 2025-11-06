@extends('pdf.layout')

@section('title', 'Comprobante de Préstamo - ' . $loan->loan_number)

@section('footer-text', 'Comprobante de Préstamo')

@section('content')
    <div class="document-title">Comprobante de Préstamo</div>
    <div class="document-number">{{ $loan->loan_number }}</div>

    <!-- Customer Information -->
    <div class="info-box">
        <div class="info-box-title">Datos del Cliente</div>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $loan->customer->first_name }} {{ $loan->customer->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">DPI:</span>
            <span class="info-value">{{ $loan->customer->identity_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Teléfono:</span>
            <span class="info-value">{{ $loan->customer->phone }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Email:</span>
            <span class="info-value">{{ $loan->customer->email ?? 'N/A' }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Dirección:</span>
            <span class="info-value">{{ $loan->customer->address }}</span>
        </div>
    </div>

    <!-- Item Information -->
    <div class="info-box">
        <div class="info-box-title">Artículo en Garantía</div>
        <div class="info-row">
            <span class="info-label">Descripción:</span>
            <span class="info-value"><strong>{{ $loan->item->name }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Categoría:</span>
            <span class="info-value">{{ $loan->item->category->name }}</span>
        </div>
        @if($loan->item->brand)
        <div class="info-row">
            <span class="info-label">Marca:</span>
            <span class="info-value">{{ $loan->item->brand }}</span>
        </div>
        @endif
        @if($loan->item->model)
        <div class="info-row">
            <span class="info-label">Modelo:</span>
            <span class="info-value">{{ $loan->item->model }}</span>
        </div>
        @endif
        @if($loan->item->serial_number)
        <div class="info-row">
            <span class="info-label">Nº de Serie:</span>
            <span class="info-value">{{ $loan->item->serial_number }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translateItemCondition($loan->item->condition) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Valor Tasado:</span>
            <span class="info-value"><strong>GTQ{{ number_format($loan->item->appraised_value, 2) }}</strong></span>
        </div>
    </div>

    <!-- Loan Details -->
    <div class="info-box">
        <div class="info-box-title">Detalles del Préstamo</div>
        <table style="margin: 0;">
            <tr>
                <td style="width: 50%; border-bottom: none;"><strong>Fecha de Préstamo:</strong></td>
                <td style="width: 50%; border-bottom: none; text-align: right;">{{ $loan->start_date->format('d/m/Y') }}</td>
            </tr>
            <tr>
                <td style="border-bottom: none;"><strong>Fecha de Vencimiento:</strong></td>
                <td style="border-bottom: none; text-align: right; color: #f59e0b;"><strong>{{ $loan->due_date->format('d/m/Y') }}</strong></td>
            </tr>
            <tr>
                <td style="border-bottom: none;"><strong>Plazo:</strong></td>
                <td style="border-bottom: none; text-align: right;">{{ $loan->loan_term_days }} días</td>
            </tr>
            <tr>
                <td style="border-bottom: none;"><strong>Tasa de Interés:</strong></td>
                <td style="border-bottom: none; text-align: right;">{{ number_format($loan->interest_rate, 2) }}% mensual</td>
            </tr>
        </table>
    </div>

    <!-- Financial Summary -->
    @php
        // Calculate initial amounts (original agreement values)
        $interesInicial = $loan->loan_amount * ($loan->interest_rate / 100);
        $totalInicial = $loan->loan_amount + $interesInicial;
    @endphp
    <div class="totals-box">
        <div class="total-row">
            <span class="total-label">Capital del Préstamo:</span>
            <span class="total-value">GTQ{{ number_format($loan->loan_amount, 2) }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Interés Inicial ({{ number_format($loan->interest_rate, 2) }}%):</span>
            <span class="total-value">GTQ{{ number_format($interesInicial, 2) }}</span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label">TOTAL INICIAL A PAGAR:</span>
            <span class="total-value">GTQ{{ number_format($totalInicial, 2) }}</span>
        </div>
    </div>

    <!-- Important Interest Note -->
    <div class="notes-section" style="background-color: #fef3c7; border-color: #f59e0b;">
        <div class="notes-title" style="color: #d97706;">⚠️ IMPORTANTE - CÁLCULO DE INTERESES:</div>
        <div class="notes-content">
            <p style="margin-bottom: 8px;">
                <strong>El interés se recalcula sobre el saldo restante después de cada pago.</strong>
            </p>
            <p style="margin-bottom: 8px;">
                • Los pagos se aplican <strong>primero a los intereses acumulados</strong>, luego al capital.<br>
                • El interés del {{ number_format($loan->interest_rate, 2) }}% se calcula sobre el <strong>capital restante</strong> después de cada abono.<br>
                • A medida que pague capital, el interés disminuirá proporcionalmente.
            </p>
            <p style="margin-bottom: 0px;">
                <strong>Ejemplo:</strong> Si paga GTQ{{ number_format($loan->loan_amount * 0.5, 2) }}, cubrirá los intereses y reducirá el capital.
                El nuevo interés se calculará solo sobre el capital restante.
            </p>
        </div>
    </div>

    <!-- Minimum Payment Requirements -->
    @if($loan->requires_minimum_payment && $loan->minimum_monthly_payment > 0)
    <div class="notes-section" style="background-color: #dbeafe; border-color: #3b82f6;">
        <div class="notes-title" style="color: #1e40af;">💰 REQUISITO DE PAGO MÍNIMO MENSUAL:</div>
        <div class="notes-content">
            <p style="margin-bottom: 8px;">
                <strong>Este préstamo requiere un pago mínimo mensual de GTQ{{ number_format($loan->minimum_monthly_payment, 2) }}</strong>
            </p>
            <p style="margin-bottom: 8px;">
                • El pago mínimo debe realizarse <strong>cada 30 días</strong> para mantener el préstamo activo.<br>
                • Próximo pago mínimo vence el: <strong>{{ $loan->next_minimum_payment_date ? $loan->next_minimum_payment_date->format('d/m/Y') : 'Por determinar' }}</strong><br>
                • Si no realiza el pago mínimo, tendrá un período de gracia de <strong>{{ $loan->grace_period_days }} días</strong>.<br>
                • Después del período de gracia, el préstamo será marcado como <strong>EN RIESGO</strong>.
            </p>
            <p style="margin-bottom: 0px;">
                <strong>IMPORTANTE:</strong> El pago mínimo mensual es obligatorio para evitar que el artículo
                sea confiscado. Puede pagar más del mínimo en cualquier momento para reducir su deuda más rápido.
            </p>
        </div>
    </div>
    @endif

    <!-- Important Notes -->
    <div class="notes-section">
        <div class="notes-title">TÉRMINOS Y CONDICIONES IMPORTANTES:</div>
        <div class="notes-content">
            <p style="margin-bottom: 8px;">
                <strong>1. PLAZO:</strong> Este préstamo vence el <strong>{{ $loan->due_date->format('d/m/Y') }}</strong>.
                Después de esta fecha, se aplicarán intereses moratorios adicionales.
            </p>
            <p style="margin-bottom: 8px;">
                <strong>2. RENOVACIÓN:</strong> El préstamo puede renovarse pagando los intereses acumulados
                antes de la fecha de vencimiento.
            </p>
            <p style="margin-bottom: 8px;">
                <strong>3. RECUPERACIÓN:</strong> Para recuperar el artículo en garantía, debe pagar el TOTAL
                indicado arriba (capital + intereses).
            </p>
            <p style="margin-bottom: 8px;">
                <strong>4. CONFISCACIÓN:</strong> Si el préstamo no es renovado o pagado dentro de los 90 días
                posteriores al vencimiento, el artículo pasará a ser propiedad de la Casa de Empeño.
            </p>
            <p style="margin-bottom: 0px;">
                <strong>5. RESPONSABILIDAD:</strong> La Casa de Empeño no se hace responsable por pérdida o daño
                del artículo debido a caso fortuito o fuerza mayor.
            </p>
        </div>
    </div>

    @if($loan->notes)
    <div class="notes-section" style="background-color: #f3f4f6; border-color: #9ca3af;">
        <div class="notes-title" style="color: #4b5563;">📝 NOTAS ADICIONALES:</div>
        <div class="notes-content">{{ $loan->notes }}</div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Firma del Cliente</strong><br>
                {{ $loan->customer->first_name }} {{ $loan->customer->last_name }}<br>
                DPI: {{ $loan->customer->identity_number }}
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
@endsection
