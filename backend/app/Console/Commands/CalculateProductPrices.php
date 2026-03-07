<?php

namespace App\Console\Commands;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\ProductPrice;
use Illuminate\Console\Command;

class CalculateProductPrices extends Command
{
    protected $signature = 'calculate:prices {--product-id=}';
    protected $description = 'Calcular precios de venta para todos los productos según tipo de cliente';

    // Márgenes por tipo de cliente (%)
    private array $margins = [
        'CAMION VIP' => 15,
        'CAMION' => 20,
        'OFERTA' => 25,
        'VIP' => 30,
        'EMPRESAS' => 35,
        'EMPRESAS A' => 40,
    ];

    public function handle()
    {
        $productId = $this->option('product-id');
        
        if ($productId) {
            $products = Product::where('id', $productId)->get();
        } else {
            $products = Product::whereHas('costes')->get();
        }

        $this->info("Calculando precios para {$products->count()} productos...");
        
        $calculated = 0;
        $errors = 0;
        
        foreach ($products as $product) {
            try {
                $this->calculatePricesForProduct($product);
                $calculated++;
                $this->info("✓ Producto {$product->sku}: {$product->nombre}");
            } catch (\Exception $e) {
                $errors++;
                $this->error("✗ Error en producto {$product->sku}: " . $e->getMessage());
            }
        }
        
        $this->info("\n✅ Cálculo completado:");
        $this->info("  - Productos procesados: $calculated");
        $this->info("  - Errores: $errors");
        
        return 0;
    }

    private function calculatePricesForProduct(Product $product): void
    {
        // Obtener el coste más reciente y activo
        $coste = ProductCost::where('producto_id', $product->id)
            ->where('activo', true)
            ->orderBy('fecha_vigencia_desde', 'desc')
            ->first();

        if (!$coste) {
            throw new \Exception("No hay coste definido");
        }

        // Calcular precio base después de descuentos
        $precioBase = $coste->precio_compra;
        
        // Aplicar descuentos en cascada
        if ($coste->descuento_1 > 0) {
            $precioBase = $precioBase * (1 - $coste->descuento_1 / 100);
        }
        if ($coste->descuento_2 > 0) {
            $precioBase = $precioBase * (1 - $coste->descuento_2 / 100);
        }
        if ($coste->descuento_3 > 0) {
            $precioBase = $precioBase * (1 - $coste->descuento_3 / 100);
        }

        // Calcular precio para cada tipo de cliente
        $customerTypes = CustomerType::where('activo', true)->get();
        
        foreach ($customerTypes as $type) {
            $margen = $this->margins[$type->nombre] ?? 30;
            $precioVenta = $precioBase * (1 + $margen / 100);
            
            // Redondear a 2 decimales
            $precioVenta = round($precioVenta, 2);

            ProductPrice::updateOrCreate(
                [
                    'producto_id' => $product->id,
                    'tipo_cliente_id' => $type->id,
                ],
                [
                    'precio_base' => $precioVenta,
                    'margen_porcentaje' => $margen,
                    'margen_absoluto' => $precioVenta - $precioBase,
                    'fecha_vigencia_desde' => now(),
                    'activo' => true,
                ]
            );
        }
    }
}
