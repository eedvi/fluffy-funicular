@extends('pdf.ticket-layout')

@section('title', 'Comprobante de Venta - ' . $sale->sale_number)

@section('footer-text', 'Comprobante de Venta')

@section('content')
    <div class="document-title">COMPROBANTE DE VENTA</div>
    <div class="document-number">{{ $sale->sale_number }}</div>

    <!-- Sale Info -->
    <div class="info-section">
        <div class="info-title">Venta</div>
        <div class="info-row">
            <span class="info-label">Fecha:</span>
            <span class="info-value">{{ $sale->sale_date->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Método:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translatePaymentMethod($sale->payment_method) }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">
                @if($sale->status === 'delivered')
                    <strong style="color: #10b981;">✓ Entregado</strong>
                @elseif($sale->status === 'pending')
                    <strong> Pendiente</strong>
                @else
                    <strong>{{ \App\Helpers\TranslationHelper::translateSaleStatus($sale->status) }}</strong>
                @endif
            </span>
        </div>
    </div>

    @if($sale->customer)
    <!-- Customer Info -->
    <div class="info-section">
        <div class="info-title">Cliente</div>
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

    <!-- Item Info -->
    <div class="info-section">
        <div class="info-title">Artículo</div>
        <div class="info-row">
            <span class="info-label">Descripción:</span>
            <span class="info-value"><strong>{{ $sale->item->name }}</strong></span>
        </div>
        <div class="info-row">
            <span class="info-label">Categoría:</span>
            <span class="info-value">{{ $sale->item->category->name }}</span>
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
            <span class="info-label">Serie:</span>
            <span class="info-value">{{ $sale->item->serial_number }}</span>
        </div>
        @endif
        <div class="info-row">
            <span class="info-label">Estado:</span>
            <span class="info-value">{{ \App\Helpers\TranslationHelper::translateItemCondition($sale->item->condition) }}</span>
        </div>
    </div>

    <!-- Price Details -->
    <div class="info-section">
        <div class="info-title">Desglose</div>
        <div class="balance-row">
            <span class="balance-label">Precio de Venta:</span>
            <span class="balance-value">Q{{ number_format($sale->sale_price, 2) }}</span>
        </div>
        @if($sale->discount > 0)
        <div class="balance-row">
            <span class="balance-label">Descuento:</span>
            <span class="balance-value" style="color: #10b981;">-Q{{ number_format($sale->discount, 2) }}</span>
        </div>
        @endif
    </div>

    <!-- Total -->
    <div class="total-box">
        <div class="total-row" style="font-size: 10pt;">
            <span class="total-label">TOTAL PAGADO:</span>
            <span class="total-value">Q{{ number_format($sale->final_price, 2) }}</span>
        </div>
    </div>

    @if($sale->status === 'delivered')
    <div class="notes-box" style="background-color: #d1fae5; border-color: #10b981;">
        <div class="notes-title" style="color: #10b981;">✅ VENTA COMPLETADA</div>
        <p>Artículo entregado. Conserve este comprobante.</p>
    </div>
    @elseif($sale->status === 'pending')
    <div class="notes-box">
        <div class="notes-title">⏳ VENTA PENDIENTE</div>
        <p>Pendiente de completar.</p>
    </div>
    @endif

    <!-- Warranty -->
    <div class="notes-box" style="margin-top: 3mm;">
        <div class="notes-title">GARANTÍA:</div>
        <p style="margin-bottom: 1mm;"><strong>7 días</strong> contra defectos no evidentes.</p>
        <p style="margin-bottom: 1mm;"><strong>Devolución:</strong> 48 hrs con este comprobante.</p>
        <p>Cliente inspecciona y acepta el artículo.</p>
    </div>

    @if($sale->notes)
    <div class="notes-box">
        <div class="notes-title">NOTAS:</div>
        <p>{{ $sale->notes }}</p>
    </div>
    @endif

    <!-- Signature -->
    <div class="signature-line">
        <strong>Firma del Cliente</strong>
        @if($sale->customer)
        <br>{{ $sale->customer->first_name }} {{ $sale->customer->last_name }}
        @endif
    </div>
@endsection
