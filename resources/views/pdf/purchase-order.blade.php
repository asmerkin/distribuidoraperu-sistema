<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 30px 40px;
        }
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #222;
            line-height: 1.3;
        }
        h1, p, div, table, td, th {
            margin: 0;
            padding: 0;
        }

        /* ── Header ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .header-table td {
            vertical-align: top;
            padding: 0;
        }
        .logo {
            margin-bottom: 6px;
        }
        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #dc2626;
        }
        .company-info {
            font-size: 9px;
            color: #555;
            line-height: 1.5;
            margin-top: 4px;
        }
        .doc-title {
            font-size: 22px;
            font-weight: bold;
            text-align: right;
            color: #111;
            letter-spacing: 0.5px;
        }
        .doc-meta {
            margin-top: 8px;
        }
        .doc-meta-table {
            border-collapse: collapse;
            float: right;
        }
        .doc-meta-table td {
            padding: 4px 10px;
            font-size: 9.5px;
        }
        .doc-meta-table .label-cell {
            background-color: #dc2626;
            color: #fff;
            font-weight: bold;
            text-align: right;
        }
        .doc-meta-table .value-cell {
            border: 1px solid #d1d5db;
            text-align: center;
            font-weight: bold;
            min-width: 90px;
        }

        /* ── Secciones Proveedor / Enviar A ── */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .parties-table > tr > td,
        .parties-table > tbody > tr > td {
            vertical-align: top;
        }
        .party-header {
            background-color: #dc2626;
            color: #fff;
            font-weight: bold;
            font-size: 9.5px;
            padding: 5px 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .party-body {
            padding: 10px;
            border: 1px solid #d1d5db;
            border-top: none;
            font-size: 9.5px;
            line-height: 1.6;
            vertical-align: top;
        }
        .party-name {
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 3px;
        }

        /* ── Fila de condiciones ── */
        .conditions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .conditions-table .cond-header {
            background-color: #dc2626;
            color: #fff;
            font-weight: bold;
            font-size: 8.5px;
            padding: 5px 6px;
            text-transform: uppercase;
            text-align: center;
            letter-spacing: 0.3px;
        }
        .conditions-table .cond-value {
            border: 1px solid #d1d5db;
            padding: 6px;
            text-align: center;
            font-size: 9.5px;
        }

        /* ── Tabla de items ── */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }
        .items-table thead th {
            background-color: #dc2626;
            color: #fff;
            font-weight: bold;
            font-size: 8.5px;
            padding: 5px 6px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border: 1px solid #dc2626;
        }
        .items-table thead th.right { text-align: right; }
        .items-table thead th.center { text-align: center; }
        .items-table tbody td {
            padding: 5px 6px;
            border: 1px solid #d1d5db;
            font-size: 9.5px;
        }
        .items-table tbody td.right { text-align: right; }
        .items-table tbody td.center { text-align: center; }
        .items-table tbody tr.empty-row td {
            height: 18px;
        }

        /* ── Totales ── */
        .totals-table {
            border-collapse: collapse;
            float: right;
            width: 240px;
        }
        .totals-table td {
            padding: 5px 10px;
            font-size: 9.5px;
        }
        .totals-table .total-label {
            text-align: right;
            font-weight: bold;
            border: 1px solid #d1d5db;
        }
        .totals-table .total-value {
            text-align: right;
            border: 1px solid #d1d5db;
            width: 90px;
        }
        .totals-table .grand-total .total-label {
            background-color: #dc2626;
            color: #fff;
        }
        .totals-table .grand-total .total-value {
            font-weight: bold;
            font-size: 11px;
            background-color: #fef2f2;
        }

        /* ── Notas ── */
        .notes-wrapper {
            overflow: hidden;
        }
        .notes-section {
            float: left;
            width: 55%;
        }
        .notes-header {
            background-color: #dc2626;
            color: #fff;
            font-weight: bold;
            font-size: 8.5px;
            padding: 5px 10px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }
        .notes-body {
            border: 1px solid #d1d5db;
            border-top: none;
            padding: 10px;
            font-size: 9.5px;
            min-height: 50px;
            white-space: pre-line;
        }

        /* ── Footer ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #aaa;
        }
    </style>
</head>
<body>

    {{-- ══ HEADER ══ --}}
    <table class="header-table">
        <tr>
            <td style="width: 50%;">
                <div class="logo">
                    <img src="{{ public_path('images/logo.png') }}" alt="Logo" style="height: 40px;">
                </div>
                <div class="company-name">{{ $company['name'] ?: 'Distribuidora Perú' }}</div>
                <div class="company-info">
                    @if(!empty($company['address'])){{ $company['address'] }}<br>@endif
                    @if(!empty($company['phone']))Tel: {{ $company['phone'] }}<br>@endif
                    @if(!empty($company['tax_id']))CUIT: {{ $company['tax_id'] }}<br>@endif
                    @if(!empty($company['email'])){{ $company['email'] }}@endif
                </div>
            </td>
            <td style="width: 50%;">
                <div class="doc-title">ORDEN DE COMPRA</div>
                <div class="doc-meta">
                    <table class="doc-meta-table">
                        <tr>
                            <td class="label-cell">FECHA</td>
                            <td class="value-cell">{{ $purchaseOrder->order_date->format('d/m/Y') }}</td>
                        </tr>
                        <tr>
                            <td class="label-cell">OC #</td>
                            <td class="value-cell">{{ $purchaseOrder->po_number }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ══ PROVEEDOR / ENVIAR A ══ --}}
    <table class="parties-table">
        <tr>
            {{-- Proveedor --}}
            <td style="width: 49%; padding-right: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td class="party-header">Proveedor</td></tr>
                    <tr>
                        <td class="party-body" style="height: 95px;">
                            <div class="party-name">{{ $purchaseOrder->supplier->name }}</div>
                            @if($purchaseOrder->supplier->contact_name)Atención: {{ $purchaseOrder->supplier->contact_name }}<br>@endif
                            @if($purchaseOrder->supplier->address){{ $purchaseOrder->supplier->address }}<br>@endif
                            @if($purchaseOrder->supplier->phone)Tel: {{ $purchaseOrder->supplier->phone }}<br>@endif
                            @if($purchaseOrder->supplier->email){{ $purchaseOrder->supplier->email }}<br>@endif
                            @if($purchaseOrder->supplier->tax_id)CUIT: {{ $purchaseOrder->supplier->tax_id }}@endif
                        </td>
                    </tr>
                </table>
            </td>
            {{-- Enviar a --}}
            <td style="width: 49%; padding-left: 8px;">
                <table style="width: 100%; border-collapse: collapse;">
                    <tr><td class="party-header">Enviar a</td></tr>
                    <tr>
                        <td class="party-body" style="height: 95px;">
                            <div class="party-name">{{ $purchaseOrder->location->name }}</div>
                            {{ $company['name'] ?: 'Distribuidora Perú' }}<br>
                            @if(!empty($company['address'])){{ $company['address'] }}<br>@endif
                            @if(!empty($company['phone']))Tel: {{ $company['phone'] }}@endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- ══ CONDICIONES ══ --}}
    <table class="conditions-table">
        <tr>
            <td class="cond-header" style="width: 33%;">Fecha de entrega</td>
            <td class="cond-header" style="width: 34%;">Condición de pago</td>
            <td class="cond-header" style="width: 33%;">Creado por</td>
        </tr>
        <tr>
            <td class="cond-value">{{ $purchaseOrder->expected_date ? $purchaseOrder->expected_date->format('d/m/Y') : '—' }}</td>
            <td class="cond-value">{{ $purchaseOrder->supplier->payment_terms ?: '—' }}</td>
            <td class="cond-value">{{ $purchaseOrder->user?->name ?: '—' }}</td>
        </tr>
    </table>

    {{-- ══ DETALLE DE PRODUCTOS ══ --}}
    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 13%;">SKU</th>
                <th style="width: 42%;">Descripción</th>
                <th class="center" style="width: 10%;">Cant.</th>
                <th class="right" style="width: 15%;">P/U</th>
                <th class="right" style="width: 15%;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($purchaseOrder->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->variant->sku }}</td>
                    <td>
                        {{ $item->variant->product->name }}
                        @if($item->variant->name !== 'Default')
                            — {{ $item->variant->name }}
                        @endif
                    </td>
                    <td class="center">{{ $item->quantity_ordered }}</td>
                    <td class="right">{{ number_format($item->unit_cost, 2, ',', '.') }}</td>
                    <td class="right">{{ number_format($item->subtotal, 2, ',', '.') }}</td>
                </tr>
            @endforeach
            @for($i = count($purchaseOrder->items); $i < 12; $i++)
                <tr class="empty-row">
                    <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- ══ NOTAS + TOTALES ══ --}}
    <div class="notes-wrapper">
        @if($purchaseOrder->notes_for_supplier)
            <div class="notes-section">
                <div class="notes-header">Comentarios o instrucciones especiales</div>
                <div class="notes-body">{{ $purchaseOrder->notes_for_supplier }}</div>
            </div>
        @endif

        <table class="totals-table">
            <tr>
                <td class="total-label">SUBTOTAL</td>
                <td class="total-value">{{ number_format($purchaseOrder->total, 2, ',', '.') }}</td>
            </tr>
            <tr>
                <td class="total-label">IMPUESTO</td>
                <td class="total-value">—</td>
            </tr>
            <tr>
                <td class="total-label">ENVÍO</td>
                <td class="total-value">—</td>
            </tr>
            <tr>
                <td class="total-label">OTRO</td>
                <td class="total-value">—</td>
            </tr>
            <tr class="grand-total">
                <td class="total-label">TOTAL</td>
                <td class="total-value">$ {{ number_format($purchaseOrder->total, 2, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i') }} — {{ $company['name'] ?: 'Distribuidora Perú' }}
    </div>
</body>
</html>
