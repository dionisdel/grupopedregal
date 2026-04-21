<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 25px 35px 50px 35px;
        size: A4 portrait;
    }
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 10pt;
        color: #333333;
        margin: 0;
        padding: 0;
    }
    /* Header */
    .header {
        width: 100%;
        border-bottom: 3px solid #E8751A;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .header td {
        vertical-align: middle;
    }
    .header .logo-cell {
        font-size: 18pt;
        font-weight: bold;
        color: #333333;
    }
    .header .logo-cell span {
        color: #E8751A;
    }
    .header .contact-cell {
        text-align: right;
        font-size: 8pt;
        color: #666;
    }
    /* Title */
    .doc-title {
        font-size: 14pt;
        font-weight: bold;
        color: #E8751A;
        text-transform: uppercase;
        margin-bottom: 4px;
    }
    .doc-date {
        font-size: 9pt;
        color: #888;
        margin-bottom: 15px;
    }
    .section-title {
        font-size: 11pt;
        font-weight: bold;
        color: #E8751A;
        border-bottom: 1px solid #E8751A;
        padding-bottom: 4px;
        margin-top: 18px;
        margin-bottom: 8px;
    }
    /* Product info */
    .product-info {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .product-info td {
        padding: 4px 10px;
        font-size: 9.5pt;
    }
    .product-info .label {
        font-weight: bold;
        color: #333333;
        width: 30%;
        background-color: #F5F5F5;
    }
    .product-info .value {
        color: #555;
    }
    /* Materials table */
    .materials-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 15px;
    }
    .materials-table th {
        background-color: #333333;
        color: #FFFFFF;
        font-size: 8.5pt;
        padding: 6px 8px;
        text-align: left;
    }
    .materials-table th.num {
        text-align: right;
    }
    .materials-table td {
        padding: 5px 8px;
        font-size: 9pt;
        border-bottom: 1px solid #E0E0E0;
    }
    .materials-table td.num {
        text-align: right;
    }
    .materials-table tr:nth-child(even) td {
        background-color: #F5F5F5;
    }
    /* Totals */
    .totals-table {
        width: 50%;
        margin-left: auto;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .totals-table td {
        padding: 6px 12px;
        font-size: 10pt;
    }
    .totals-table .label {
        font-weight: bold;
        color: #333333;
    }
    .totals-table .value {
        text-align: right;
        font-weight: bold;
        color: #555;
    }
    .totals-table .merma-row td {
        color: #888;
        font-size: 9pt;
    }
    .totals-table .total-row td {
        border-top: 2px solid #E8751A;
        font-size: 13pt;
        color: #E8751A;
        padding-top: 8px;
    }
    /* Specs table */
    .specs-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .specs-table th {
        background-color: #333333;
        color: #FFFFFF;
        font-size: 9pt;
        padding: 6px 10px;
        text-align: left;
    }
    .specs-table td {
        padding: 5px 10px;
        font-size: 9.5pt;
        border-bottom: 1px solid #E0E0E0;
    }
    .specs-table tr:nth-child(even) td {
        background-color: #F5F5F5;
    }
    /* Footer */
    .footer {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 35px;
        text-align: center;
        font-size: 8pt;
        color: #999;
        border-top: 1px solid #E0E0E0;
        padding-top: 5px;
    }
</style>
</head>
<body>

{{-- Header --}}
<table class="header">
    <tr>
        <td class="logo-cell">
            GRUPO <span>PEDREGAL</span>
        </td>
        <td class="contact-cell">
            www.grupopedregal.es<br>
            info@grupopedregal.es<br>
            +34 610 92 95 92
        </td>
    </tr>
</table>

{{-- Document title --}}
<div class="doc-title">Presupuesto de Materiales</div>
<div class="doc-date">Fecha: {{ date('d/m/Y') }}</div>

{{-- Product info --}}
<div class="section-title">Producto</div>
<table class="product-info">
    <tr>
        <td class="label">Nombre</td>
        <td class="value">{{ $product->nombre }}</td>
    </tr>
    <tr>
        <td class="label">SKU</td>
        <td class="value">{{ $product->sku }}</td>
    </tr>
    <tr>
        <td class="label">Categoría</td>
        <td class="value">{{ $category }}</td>
    </tr>
    <tr>
        <td class="label">Marca</td>
        <td class="value">{{ $brand }}</td>
    </tr>
</table>

{{-- Technical specs --}}
@if($specs)
<div class="section-title">Especificaciones Técnicas</div>
<table class="specs-table">
    <thead>
        <tr>
            <th>Característica</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        @if($specs->peso_kg)
        <tr>
            <td>Peso</td>
            <td>{{ $specs->peso_kg }} kg</td>
        </tr>
        @endif
        @if($specs->largo_cm || $specs->ancho_cm || $specs->alto_cm)
        <tr>
            <td>Dimensiones (L × A × H)</td>
            <td>{{ $specs->largo_cm ?? '-' }} × {{ $specs->ancho_cm ?? '-' }} × {{ $specs->alto_cm ?? '-' }} cm</td>
        </tr>
        @endif
        @if($specs->m2_por_unidad)
        <tr>
            <td>m² por unidad</td>
            <td>{{ $specs->m2_por_unidad }}</td>
        </tr>
        @endif
        @if($specs->unidades_por_embalaje)
        <tr>
            <td>Unidades por embalaje</td>
            <td>{{ $specs->unidades_por_embalaje }}</td>
        </tr>
        @endif
        @if($specs->unidades_por_palet)
        <tr>
            <td>Unidades por palet</td>
            <td>{{ $specs->unidades_por_palet }}</td>
        </tr>
        @endif
    </tbody>
</table>
@endif

{{-- Materials breakdown --}}
<div class="section-title">Desglose de Materiales</div>
<table class="materials-table">
    <thead>
        <tr>
            <th>Descripción</th>
            <th class="num">Cant./m²</th>
            <th class="num">Cant. Total</th>
            <th>Unidad</th>
            <th class="num">Precio/ud</th>
            <th class="num">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($materiales as $material)
        <tr>
            <td>{{ $material['descripcion'] }}</td>
            <td class="num">{{ number_format($material['cantidad_por_m2'], 4, ',', '.') }}</td>
            <td class="num">{{ $material['cantidad_total'] }}</td>
            <td>{{ $material['unidad'] }}</td>
            <td class="num">{{ number_format($material['precio_unitario'], 2, ',', '.') }} €</td>
            <td class="num">{{ number_format($material['total'], 2, ',', '.') }} €</td>
        </tr>
        @empty
        <tr>
            <td colspan="6" style="text-align:center; color:#999;">Sin materiales calculados</td>
        </tr>
        @endforelse
    </tbody>
</table>

{{-- Totals --}}
<table class="totals-table">
    <tr>
        <td class="label">Subtotal (sin merma)</td>
        <td class="value">{{ number_format($subtotal, 2, ',', '.') }} €</td>
    </tr>
    <tr class="merma-row">
        <td class="label">Merma ({{ number_format($merma_porcentaje, 1, ',', '.') }}%)</td>
        <td class="value">{{ number_format($subtotal * $merma_porcentaje / 100, 2, ',', '.') }} €</td>
    </tr>
    <tr class="total-row">
        <td class="label">TOTAL CON MERMA</td>
        <td class="value">{{ number_format($total, 2, ',', '.') }} €</td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer">
    GRUPO PEDREGAL — C/ Fontaneros, 2-4, Arahal, Sevilla — www.grupopedregal.es — © {{ date('Y') }} Todos los derechos reservados.
</div>

</body>
</html>
