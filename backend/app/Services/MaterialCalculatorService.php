<?php

namespace App\Services;

use App\Models\Product;

class MaterialCalculatorService
{
    public function __construct(
        private PriceCalculationService $priceService
    ) {}

    /**
     * Calcula el desglose de materiales para una superficie dada.
     *
     * @param  Product  $product
     * @param  float    $m2               Superficie en m²
     * @param  float    $mermaPorcentaje  Porcentaje de merma (default 5%)
     * @return array{
     *   materiales: array<int, array{descripcion: string, cantidad_por_m2: float, cantidad_total: int, unidad: string, precio_unitario: float, total: float}>,
     *   subtotal_sin_merma: float,
     *   merma_porcentaje: float,
     *   total_con_merma: float
     * }
     */
    public function calculate(Product $product, float $m2, float $mermaPorcentaje = 5.0): array
    {
        $product->loadMissing(['especificaciones', 'unidadBase']);

        $spec = $product->especificaciones;
        $m2PorUnidad = $spec?->m2_por_unidad ? (float) $spec->m2_por_unidad : 0.0;

        $precioUnitario = $this->priceService->getPvpPrice($product);
        $unidad = $product->unidadBase?->abreviatura ?? 'ud';

        // Cantidad por m²: inversa de m2_por_unidad
        $cantidadPorM2 = $m2PorUnidad > 0 ? round(1 / $m2PorUnidad, 4) : 0.0;

        // Cantidad total: ceil(m2 / m2_por_unidad). Si m² <= 0, cantidad = 0
        $cantidadTotal = 0;
        if ($m2 > 0 && $m2PorUnidad > 0) {
            $cantidadTotal = (int) ceil($m2 / $m2PorUnidad);
        }

        $totalMaterial = round($cantidadTotal * $precioUnitario, 2);

        $materiales = [
            [
                'descripcion' => $product->nombre,
                'cantidad_por_m2' => $cantidadPorM2,
                'cantidad_total' => $cantidadTotal,
                'unidad' => $unidad,
                'precio_unitario' => $precioUnitario,
                'total' => $totalMaterial,
            ],
        ];

        $subtotalSinMerma = $totalMaterial;
        $totalConMerma = round($subtotalSinMerma * (1 + $mermaPorcentaje / 100), 2);

        return [
            'materiales' => $materiales,
            'subtotal_sin_merma' => $subtotalSinMerma,
            'merma_porcentaje' => $mermaPorcentaje,
            'total_con_merma' => $totalConMerma,
        ];
    }
}
