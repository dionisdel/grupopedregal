<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
    @page {
        margin: 30px 40px 40px 40px;
        size: A4 portrait;
    }
    body {
        font-family: Helvetica, Arial, sans-serif;
        font-size: 10pt;
        color: #222;
        margin: 0;
        padding: 0;
    }
    /* ── Portada ─────────────────────────────── */
    .cover {
        page-break-after: always;
        text-align: center;
        padding-top: 200px;
    }
    .cover h1 {
        font-size: 48pt;
        font-weight: bold;
        color: #1a1a1a;
        margin: 0;
        letter-spacing: 4px;
    }
    .cover .phone {
        font-size: 16pt;
        color: #555;
        margin-top: 30px;
    }
    .cover .subtitle {
        font-size: 14pt;
        color: #c00;
        margin-top: 40px;
        font-weight: bold;
    }
    /* ── Páginas de contenido ────────────────── */
    .page-header {
        width: 100%;
        margin-bottom: 12px;
        border-bottom: 2px solid #333;
        padding-bottom: 4px;
    }
    .page-header td {
        font-size: 11pt;
        font-weight: bold;
        vertical-align: bottom;
    }
    .page-header .left {
        text-align: left;
    }
    .page-header .right {
        text-align: right;
    }
    /* ── Categoría ──────────────────────────── */
    .cat-header {
        width: 100%;
        margin-top: 14px;
        margin-bottom: 2px;
    }
    .cat-header td {
        background-color: #f2f2f2;
        font-weight: bold;
        font-size: 10pt;
        padding: 5px 8px;
    }
    .cat-header .cat-name {
        text-align: center;
    }
    .cat-header .price-label {
        text-align: right;
        white-space: nowrap;
    }
    /* ── Subcategoría ───────────────────────── */
    .subcat-row td {
        font-weight: bold;
        font-size: 9.5pt;
        padding: 6px 8px 2px 8px;
        color: #333;
    }
    /* ── Productos ──────────────────────────── */
    .products-table {
        width: 100%;
        border-collapse: collapse;
    }
    .products-table td {
        padding: 2px 8px;
        font-size: 9.5pt;
        vertical-align: top;
    }
    .products-table .prod-name {
        width: 65%;
    }
    .products-table .prod-price {
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
    }
    .products-table .prod-price2 {
        text-align: right;
        font-weight: bold;
        white-space: nowrap;
    }
    /* ── Footer ─────────────────────────────── */
    .page-footer {
        width: 100%;
        margin-top: 16px;
        border-top: 1px solid #ccc;
        padding-top: 4px;
    }
    .page-footer td {
        font-size: 9pt;
        font-weight: bold;
        color: #555;
    }
    .page-break {
        page-break-before: always;
    }
</style>
</head>
<body>

{{-- ══════════════════════════════════════════════
     PORTADA
     ══════════════════════════════════════════════ --}}
<div class="cover">
    <h1>TARIFA</h1>
    <div class="phone">610 92 95 92</div>
    <div class="subtitle">#PREFORMADOS PYL</div>
</div>

{{-- ══════════════════════════════════════════════
     PÁGINAS DE CONTENIDO
     ══════════════════════════════════════════════ --}}
@foreach($pages as $pageIdx => $page)
<div class="{{ $pageIdx > 0 ? 'page-break' : '' }}">
    {{-- Encabezado de página --}}
    <table class="page-header">
        <tr>
            <td class="left">TARIFA {{ $mesAnio }}</td>
            <td class="right">{{ $tipoClienteLabel }}</td>
        </tr>
    </table>

    @foreach($page['sections'] as $section)
        {{-- Título de categoría --}}
        <table class="cat-header">
            <tr>
                <td class="cat-name" style="width:65%">{{ $section['category'] }}</td>
                @if($multiPrice)
                    <td class="price-label" style="width:17.5%">{{ $priceLabels[0] ?? 'PRECIO NETO' }}</td>
                    <td class="price-label" style="width:17.5%">{{ $priceLabels[1] ?? '' }}</td>
                @else
                    <td class="price-label" style="width:35%">PRECIO NETO</td>
                @endif
            </tr>
        </table>

        @foreach($section['subcategories'] as $subcat)
            @if($subcat['name'])
            <table class="products-table">
                <tr class="subcat-row">
                    <td colspan="{{ $multiPrice ? 3 : 2 }}">{{ $subcat['name'] }}</td>
                </tr>
            </table>
            @endif

            <table class="products-table">
            @foreach($subcat['products'] as $prod)
                <tr>
                    <td class="prod-name">{{ $prod['nombre'] }}</td>
                    @if($multiPrice)
                        <td class="prod-price">{{ $prod['precio1'] }}</td>
                        <td class="prod-price2">{{ $prod['precio2'] }}</td>
                    @else
                        <td class="prod-price">{{ $prod['precio'] }}</td>
                    @endif
                </tr>
            @endforeach
            </table>
        @endforeach
    @endforeach

    {{-- Footer --}}
    <table class="page-footer">
        <tr>
            <td>DEPARTAMENTO COMERCIAL  624 27 96 14</td>
        </tr>
    </table>
</div>
@endforeach

</body>
</html>
