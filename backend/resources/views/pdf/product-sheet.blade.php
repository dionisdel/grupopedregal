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
    .product-title {
        font-size: 16pt;
        font-weight: bold;
        color: #333333;
        margin-bottom: 4px;
    }
    .product-sku {
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
    /* Info table */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .info-table td {
        padding: 5px 10px;
        font-size: 9.5pt;
        vertical-align: top;
    }
    .info-table .label {
        font-weight: bold;
        color: #333333;
        width: 40%;
        background-color: #F5F5F5;
    }
    .info-table .value {
        color: #555;
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
    /* Price box */
    .price-box {
        width: 100%;
        border: 2px solid #E8751A;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .price-box .price-header {
        background-color: #E8751A;
        color: #FFFFFF;
        font-weight: bold;
        font-size: 11pt;
        padding: 8px 12px;
    }
    .price-box td {
        padding: 6px 12px;
        font-size: 10pt;
    }
    .price-box .price-label {
        font-weight: bold;
        color: #333333;
        width: 60%;
    }
    .price-box .price-value {
        text-align: right;
        font-weight: bold;
    }
    .price-box .total-row td {
        border-top: 2px solid #E8751A;
        font-size: 12pt;
        color: #E8751A;
    }
    /* Description */
    .description {
        font-size: 9.5pt;
        line-height: 1.5;
        color: #555;
        margin-bottom: 10px;
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

{{-- Product title --}}
<div class="product-title">{{ $product->nombre }}</div>
<div class="product-sku">SKU: {{ $product->sku }} | Categoría: {{ $category }} | Marca: {{ $brand }}</div>

{{-- Description --}}
@if($description)
<div class="section-title">Descripción</div>
<div class="description">{{ $description }}</div>
@endif

{{-- Technical specs --}}
<div class="section-title">Especificaciones Técnicas</div>
<table class="specs-table">
    <thead>
        <tr>
            <th>Característica</th>
            <th>Valor</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Unidad</td>
            <td>{{ $unit }}</td>
        </tr>
        @if($specs)
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
        @else
            <tr>
                <td colspan="2" style="text-align:center; color:#999;">Sin especificaciones disponibles</td>
            </tr>
        @endif
    </tbody>
</table>

{{-- Price --}}
<table class="price-box">
    <tr>
        <td colspan="2" class="price-header">Precio PVP</td>
    </tr>
    <tr>
        <td class="price-label">Base imponible</td>
        <td class="price-value">{{ number_format($price['base'], 2, ',', '.') }} €</td>
    </tr>
    <tr>
        <td class="price-label">IVA (21%)</td>
        <td class="price-value">{{ number_format($price['iva'], 2, ',', '.') }} €</td>
    </tr>
    <tr class="total-row">
        <td class="price-label">Total con IVA</td>
        <td class="price-value">{{ number_format($price['total'], 2, ',', '.') }} €</td>
    </tr>
</table>

{{-- Footer --}}
<div class="footer">
    GRUPO PEDREGAL — C/ Fontaneros, 2-4, Arahal, Sevilla — www.grupopedregal.es — © {{ date('Y') }} Todos los derechos reservados.
</div>

</body>
</html>
