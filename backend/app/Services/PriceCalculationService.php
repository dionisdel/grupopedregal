<?php

namespace App\Services;

use App\Models\CustomerType;
use App\Models\Product;

class PriceCalculationService
{
    /**
     * Devuelve el precio PVP (precio_base de la tarifa general/primera activa).
     *
     * Se obtiene el primer ProductPrice activo del producto, ordenado por
     * tipo_cliente_id ASC. Si no existe ningún precio activo, devuelve 0.0.
     */
    public function getPvpPrice(Product $product): float
    {
        $price = $product->precios()
            ->where('activo', true)
            ->orderBy('tipo_cliente_id', 'asc')
            ->first();

        return $price ? (float) $price->precio_base : 0.0;
    }

    /**
     * Devuelve el precio según el tipo de cliente.
     *
     * Busca el ProductPrice activo donde tipo_cliente_id coincide con el
     * CustomerType dado. Si no existe, devuelve 0.0.
     */
    public function getClientPrice(Product $product, CustomerType $customerType): float
    {
        $price = $product->precios()
            ->where('tipo_cliente_id', $customerType->id)
            ->where('activo', true)
            ->first();

        return $price ? (float) $price->precio_base : 0.0;
    }

    /**
     * Calcula el desglose de IVA para un precio dado.
     *
     * @return array{base: float, iva: float, total: float}
     */
    public function calculateIva(float $precio, float $tipoIva = 21.0): array
    {
        $iva = round($precio * $tipoIva / 100, 2);

        return [
            'base' => round($precio, 2),
            'iva' => $iva,
            'total' => round($precio + $iva, 2),
        ];
    }
}
