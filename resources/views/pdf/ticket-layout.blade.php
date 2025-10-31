<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>@yield('title', 'Documento')</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            line-height: 1.3;
            color: #000;
            padding: 5mm;
        }

        /* Header */
        .header {
            text-align: center;
            border-bottom: 2px solid #000;
            padding-bottom: 3mm;
            margin-bottom: 3mm;
        }

        .company-name {
            font-size: 14pt;
            font-weight: bold;
            margin-bottom: 1mm;
        }

        .branch-info {
            font-size: 7pt;
            line-height: 1.2;
        }

        /* Document Title */
        .document-title {
            text-align: center;
            font-size: 12pt;
            font-weight: bold;
            margin: 3mm 0;
            text-transform: uppercase;
        }

        .document-number {
            text-align: center;
            font-size: 9pt;
            margin-bottom: 3mm;
        }

        /* Info sections */
        .info-section {
            margin: 3mm 0;
            border-top: 1px dashed #999;
            padding-top: 2mm;
        }

        .info-title {
            font-weight: bold;
            font-size: 8pt;
            margin-bottom: 1mm;
            text-transform: uppercase;
        }

        .info-row {
            margin-bottom: 1mm;
            font-size: 7pt;
        }

        .info-label {
            font-weight: bold;
            display: inline-block;
            width: 40%;
        }

        .info-value {
            display: inline-block;
            width: 58%;
        }

        /* Table */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 3mm 0;
            font-size: 7pt;
        }

        table th {
            background-color: #000;
            color: white;
            padding: 2mm;
            text-align: left;
            font-size: 8pt;
            font-weight: bold;
        }

        table td {
            padding: 2mm;
            border-bottom: 1px solid #ddd;
        }

        .text-right {
            text-align: right;
        }

        /* Totals */
        .total-box {
            margin: 3mm 0;
            border-top: 2px solid #000;
            border-bottom: 2px solid #000;
            padding: 2mm 0;
        }

        .total-row {
            font-size: 9pt;
            padding: 1mm 0;
        }

        .total-label {
            font-weight: bold;
        }

        .total-value {
            float: right;
            font-weight: bold;
        }

        /* Balance box */
        .balance-box {
            background-color: #f5f5f5;
            border: 1px solid #999;
            padding: 2mm;
            margin: 3mm 0;
            font-size: 7pt;
        }

        .balance-row {
            display: table;
            width: 100%;
            margin-bottom: 1mm;
        }

        .balance-label {
            display: table-cell;
            width: 60%;
            font-weight: bold;
        }

        .balance-value {
            display: table-cell;
            width: 40%;
            text-align: right;
        }

        /* Notes */
        .notes-box {
            margin: 3mm 0;
            padding: 2mm;
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            font-size: 7pt;
        }

        .notes-title {
            font-weight: bold;
            margin-bottom: 1mm;
        }

        /* Footer */
        .footer {
            margin-top: 5mm;
            padding-top: 2mm;
            border-top: 1px dashed #999;
            text-align: center;
            font-size: 6pt;
            color: #666;
        }

        .signature-line {
            margin-top: 8mm;
            border-top: 1px solid #000;
            padding-top: 1mm;
            text-align: center;
            font-size: 7pt;
        }

        /* Utility */
        .text-center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .text-success {
            color: #10b981;
        }

        .text-warning {
            color: #f59e0b;
        }
    </style>
    @yield('extra-styles')
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="company-name">Casa de Empeño</div>
        <div class="branch-info">
            <strong>{{ $branch->name }}</strong><br>
            @if($branch->address){{ $branch->address }}<br>@endif
            @if($branch->phone)Tel: {{ $branch->phone }}<br>@endif
        </div>
    </div>

    <!-- Content -->
    @yield('content')

    <!-- Footer -->
    <div class="footer">
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
        <p>@yield('footer-text', 'Documento Oficial')</p>
        <p style="margin-top: 2mm;">Conserve este comprobante</p>
    </div>
</body>
</html>
