@extends('pdf.layout')

@section('title', 'Comprobante de Venta - ' . $sale->sale_number)

@section('footer-text', 'Comprobante de Venta')

@section('content')
    <div class="document-title">Comprobante de Venta</div>
    <div class="document-number">{{ $sale->sale_number }}</div>

    <!-- Sale Info -->
    <div class="info-box">
        <div class="info-box-title">Información de la Venta</div>
        <div class="info-row">
            <span class="info-label">Fecha de Venta:</span>
            <span class="info-value"><strong>{{ $sale->sale_date->format('d/m/Y H:i') }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Método de Pago:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translatePaymentMethod($sale->payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                @if($sale->status === 'delivered')
                    <strong style="color: #10b981;">✓ {{ \App\Helpers\TranslationHelper::translateSaleStatus($sale->status) }}</strong>
                @elseif($sale->status === 'pending')
                    <strong style="color: #f59e0b;">⏳ {{ \App\Helpers\TranslationHelper::translateSaleStatus($sale->status) }}</strong>
                @else
                    <strong style="color: #ef4444;">✗ {{ \App\Helpers\TranslationHelper::translateSaleStatus($sale->status) }}</strong>
                @endif
            </span>
        </div>
    </div>

    @if($sale->customer)
    <!-- Customer Information -->
    <div class="info-box">
        <div class="info-box-title">Datos del Cliente</div>
        <div class="info-row">
            <span class="info-label">Nombre:</span>
            <span class="info-value">{{ $sale->customer->first_name }} {{ $sale->customer->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">DPI:</span>
            <span class="info-value">{{ $sale->customer->identity_number }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Teléfono:</span>
            <span class="info-value">{{ $sale->customer->phone }}</span>
        </div>
    </div>
    @endif

    <!-- Item Information -->
    <div class="info-box">
        <div class="info-box-title">Artículo Vendido</div>
        <div class="info-row">
            <span class="info-label">Descripción:</span>
            <span class="info-value"><strong>{{ $sale->item->name }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Categoría:</span>
            <span class="info-value">{{ $sale->item->category }}</span>
        </div>
        @if($sale->item->brand)
        <div class="info-row">
            <span class="info-label">Marca:</span>
            <span class="info-value">{{ $sale->item->brand }}</span>
        </div>
        @endif
        @if($sale->item->model)
        <div class="info-row">
            <span class="info-label">Modelo:</span>
            <span class="info-value">{{ $sale->item->model }}</span>
        </div>
        @endif
        @if($sale->item->serial_number)
        <div class="info-row">
            <span class="info-label">Nº de Serie:</span>
            <span class="info-value">{{ $sale->item->serial_number }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translateItemCondition($sale->item->condition) }}</span>
        </div>
    </div>

    <!-- Sale Details -->
    <table>
        <thead>
            <tr>
                <th>Concepto</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Precio de Venta</td>
                <td class="text-right">${{ number_format($sale->sale_price, 2) }}</td>
            </tr>
            @if($sale->discount > 0)
            <tr>
                <td>Descuento Aplicado</td>
                <td class="text-right" style="color: #10b981;">-${{ number_format($sale->discount, 2) }}</td>
            </tr>
            @endif
        </tbody>
    </table>

    <!-- Sale Summary -->
    <div class="totals-box">
        <div class="total-row grand-total">
            <span class="total-label">TOTAL PAGADO:</span>
            <span class="total-value"><strong>${{ number_format($sale->final_price, 2) }}</strong></span>
        </div>
    </div>

    @if($sale->discount > 0)
    <div class="notes-section" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">💰 DESCUENTO APLICADO</div>
        <div class="notes-content">
            <p>Se aplicó un descuento de <strong>${{ number_format($sale->discount, 2) }}</strong> sobre el precio original de <strong>${{ number_format($sale->sale_price, 2) }}</strong>.</p>
        </div>
    </div>
    @endif

    @if($sale->status === 'delivered')
    <div class="notes-section" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">✅ VENTA COMPLETADA</div>
        <div class="notes-content">
            <p>El artículo ha sido entregado al cliente. Conserve este comprobante como garantía de compra.</p>
        </div>
    </div>
    @elseif($sale->status === 'pending')
    <div class="notes-section">
        <div class="notes-title">⏳ VENTA PENDIENTE</div>
        <div class="notes-content">
            <p>Esta venta está pendiente de completarse. El artículo será entregado una vez confirmado el pago.</p>
        </div>
    </div>
    @endif

    <!-- Warranty and Return Policy -->
    <div class="notes-section">
        <div class="notes-title">📋 POLÍTICA DE GARANTÍA Y DEVOLUCIONES:</div>
        <div class="notes-content">
            <p style="margin-bottom: 8px;">
                <strong>1. GARANTÍA:</strong> Todos los artículos vendidos cuentan con una garantía de 7 días
                contra defectos de funcionamiento no evidentes al momento de la compra.
            </p>
            <p style="margin-bottom: 8px;">
                <strong>2. DEVOLUCIONES:</strong> Las devoluciones se aceptan dentro de las primeras 48 horas
                presentando este comprobante y el artículo en perfecto estado.
            </p>
            <p style="margin-bottom: 0px;">
                <strong>3. INSPECCIÓN:</strong> El cliente acepta haber inspeccionado el artículo y está
                conforme con su estado y funcionamiento al momento de la compra.
            </p>
        </div>
    </div>

    @if($sale->notes)
    <div class="notes-section" style="background-color: #f3f4f6; border-color: #9ca3af; margin-top: 15px;">
        <div class="notes-title" style="color: #4b5563;">📝 NOTAS:</div>
        <div class="notes-content">{{ $sale->notes }}</div>
    </div>
    @endif

    <!-- Signatures -->
    <div class="signatures">
        <div class="signature-box">
            <div class="signature-line">
                <strong>Firma del Cliente</strong><br>
                @if($sale->customer)
                {{ $sale->customer->first_name }} {{ $sale->customer->last_name }}<br>
                DPI: {{ $sale->customer->identity_number }}
                @endif
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
        Conserve este comprobante como garantía de compra
    </div>
@endsection
