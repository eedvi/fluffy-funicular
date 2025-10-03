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
            font-size: 11pt;
            line-height: 1.4;
            color: #333;
        }

        .container {
            padding: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        /* Header */
        .header {
            border-bottom: 3px solid #f59e0b;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .company-name {
            font-size: 24pt;
            font-weight: bold;
            color: #f59e0b;
            margin-bottom: 5px;
        }

        .company-info {
            font-size: 9pt;
            color: #666;
        }

        .branch-info {
            text-align: right;
            font-size: 9pt;
        }

        /* Document Title */
        .document-title {
            text-align: center;
            font-size: 18pt;
            font-weight: bold;
            margin: 20px 0;
            text-transform: uppercase;
            color: #1f2937;
        }

        .document-number {
            text-align: center;
            font-size: 12pt;
            color: #666;
            margin-bottom: 20px;
        }

        /* Info boxes */
        .info-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 12px;
            margin: 15px 0;
        }

        .info-box-title {
            font-weight: bold;
            font-size: 10pt;
            color: #f59e0b;
            margin-bottom: 8px;
            text-transform: uppercase;
        }

        .info-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .info-label {
            display: table-cell;
            font-weight: bold;
            width: 35%;
            font-size: 10pt;
        }

        .info-value {
            display: table-cell;
            font-size: 10pt;
        }

        /* Tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }

        table th {
            background-color: #f59e0b;
            color: white;
            padding: 10px;
            text-align: left;
            font-size: 10pt;
            font-weight: bold;
        }

        table td {
            padding: 8px 10px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10pt;
        }

        table tr:last-child td {
            border-bottom: none;
        }

        table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /* Totals */
        .totals-box {
            margin-top: 20px;
            border-top: 2px solid #f59e0b;
            padding-top: 10px;
        }

        .total-row {
            display: table;
            width: 100%;
            margin-bottom: 5px;
        }

        .total-label {
            display: table-cell;
            width: 70%;
            text-align: right;
            font-weight: bold;
            padding-right: 15px;
            font-size: 11pt;
        }

        .total-value {
            display: table-cell;
            width: 30%;
            text-align: right;
            font-size: 11pt;
        }

        .grand-total {
            font-size: 14pt;
            font-weight: bold;
            color: #f59e0b;
            padding-top: 8px;
            border-top: 2px solid #f59e0b;
            margin-top: 8px;
        }

        /* Notes & Terms */
        .notes-section {
            margin-top: 25px;
            padding: 12px;
            background-color: #fffbeb;
            border: 1px solid #fbbf24;
            border-radius: 4px;
        }

        .notes-title {
            font-weight: bold;
            font-size: 10pt;
            margin-bottom: 5px;
            color: #f59e0b;
        }

        .notes-content {
            font-size: 9pt;
            line-height: 1.5;
            color: #666;
        }

        /* Signatures */
        .signatures {
            margin-top: 40px;
            display: table;
            width: 100%;
        }

        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 0 15px;
        }

        .signature-line {
            border-top: 1px solid #333;
            margin-top: 50px;
            padding-top: 5px;
            font-size: 9pt;
        }

        /* Footer */
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e5e7eb;
            text-align: center;
            font-size: 8pt;
            color: #999;
        }

        /* Utility classes */
        .bold {
            font-weight: bold;
        }

        .text-warning {
            color: #f59e0b;
        }

        .text-success {
            color: #10b981;
        }

        .text-danger {
            color: #ef4444;
        }

        .mb-10 {
            margin-bottom: 10px;
        }

        .mb-20 {
            margin-bottom: 20px;
        }

        .mt-10 {
            margin-top: 10px;
        }

        .mt-20 {
            margin-top: 20px;
        }
    </style>
    @yield('extra-styles')
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <table style="width: 100%; border: none;">
                <tr>
                    <td style="width: 60%; border: none; vertical-align: top;">
                        <div class="company-name">Casa de Empeño</div>
                        <div class="company-info">
                            Sistema de Gestión de Préstamos y Empeños
                        </div>
                    </td>
                    <td style="width: 40%; border: none; vertical-align: top; text-align: right;">
                        <div class="branch-info">
                            <strong>{{ $branch->name }}</strong><br>
                            @if($branch->address){{ $branch->address }}<br>@endif
                            @if($branch->city){{ $branch->city }}, {{ $branch->state }}<br>@endif
                            @if($branch->phone)Tel: {{ $branch->phone }}<br>@endif
                            @if($branch->email){{ $branch->email }}@endif
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Content -->
        @yield('content')

        <!-- Footer -->
        <div class="footer">
            <p>Este documento fue generado electrónicamente el {{ now()->format('d/m/Y H:i:s') }}</p>
            <p>Casa de Empeño - Sistema de Gestión | @yield('footer-text', 'Documento Oficial')</p>
        </div>
    </div>
</body>
</html>
