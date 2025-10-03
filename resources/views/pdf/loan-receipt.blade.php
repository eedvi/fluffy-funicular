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
            <span class="info-label">DNI:</span>
            <span class="info-value">{{ $loan->customer->dni }}</span>
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
            <span class="info-value">{{ $loan->item->category }}</span>
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
            <span class="info-value">{{ ucfirst($loan->item->condition) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Valor Tasado:</span>
            <span class="info-value"><strong>${{ number_format($loan->item->appraised_value, 2) }}</strong></span>
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
    <div class="totals-box">
        <div class="total-row">
            <span class="total-label">Monto del Préstamo:</span>
            <span class="total-value">${{ number_format($loan->loan_amount, 2) }}</span>
        </div>
        <div class="total-row">
            <span class="total-label">Interés ({{ number_format($loan->interest_rate, 2) }}%):</span>
            <span class="total-value">${{ number_format($loan->interest_amount, 2) }}</span>
        </div>
        <div class="total-row grand-total">
            <span class="total-label">TOTAL A PAGAR:</span>
            <span class="total-value">${{ number_format($loan->total_amount, 2) }}</span>
        </div>
    </div>

    <!-- Important Notes -->
    <div class="notes-section">
        <div class="notes-title">⚠️ TÉRMINOS Y CONDICIONES IMPORTANTES:</div>
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
                DNI: {{ $loan->customer->dni }}
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
