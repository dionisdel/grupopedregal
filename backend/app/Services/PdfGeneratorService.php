<?php

namespace App\Services;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfGeneratorService
{
    public function __construct(
        private PriceCalculationService $priceService
    ) {}

    /**
     * Genera PDF de ficha técnica del producto.
     *
     * @param  Product  $product
     * @return string  Contenido binario del PDF
     */
    public function generateProductSheet(Product $product): string
    {
        $product->loadMissing(['especificaciones', 'unidadBase', 'categoria', 'marca']);

        $pvpPrice = $this->priceService->getPvpPrice($product);
        $ivaBreakdown = $this->priceService->calculateIva($pvpPrice);

        $description = $product->descripcion_larga_web ?: $product->descripcion;

        $pdf = Pdf::loadView('pdf.product-sheet', [
            'product' => $product,
            'specs' => $product->especificaciones,
            'description' => $description,
            'price' => $ivaBreakdown,
            'unit' => $product->unidadBase?->abreviatura ?? 'ud',
            'category' => $product->categoria?->nombre ?? '',
            'brand' => $product->marca?->nombre ?? '',
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Genera PDF de presupuesto con desglose de materiales.
     *
     * @param  Product  $product
     * @param  array    $calculatorResult  Resultado de MaterialCalculatorService::calculate()
     * @return string  Contenido binario del PDF
     */
    public function generateQuote(Product $product, array $calculatorResult): string
    {
        $product->loadMissing(['especificaciones', 'unidadBase', 'categoria', 'marca']);

        $description = $product->descripcion_larga_web ?: $product->descripcion;

        $pdf = Pdf::loadView('pdf.quote', [
            'product' => $product,
            'specs' => $product->especificaciones,
            'description' => $description,
            'materiales' => $calculatorResult['materiales'] ?? [],
            'subtotal' => $calculatorResult['subtotal_sin_merma'] ?? 0,
            'merma_porcentaje' => $calculatorResult['merma_porcentaje'] ?? 5.0,
            'total' => $calculatorResult['total_con_merma'] ?? 0,
            'category' => $product->categoria?->nombre ?? '',
            'brand' => $product->marca?->nombre ?? '',
            'unit' => $product->unidadBase?->abreviatura ?? 'ud',
        ]);

        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }
}
