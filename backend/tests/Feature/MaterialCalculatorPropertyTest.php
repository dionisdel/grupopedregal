<?php

namespace Tests\Feature;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\ProductSpec;
use App\Services\MaterialCalculatorService;
use App\Services\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for material calculator.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class MaterialCalculatorPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    private MaterialCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MaterialCalculatorService(new PriceCalculationService());
    }

    /**
     * Helper: create a product with specs and a PVP price.
     */
    private function createProductWithSpecsAndPrice(float $m2PorUnidad, float $precioBase): Product
    {
        $product = Product::factory()->create();
        $type = CustomerType::factory()->create(['orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $type->id,
            'precio_base' => $precioBase,
            'activo' => true,
        ]);
        ProductSpec::factory()->create([
            'producto_id' => $product->id,
            'm2_por_unidad' => $m2PorUnidad,
        ]);

        return $product;
    }

    /**
     * Feature: web-portal-product-catalog, Property 8: Material quantity calculation
     *
     * For any product with m2_por_unidad > 0 and any positive m² value, the calculated
     * quantity should equal ceil(m2 / m2_por_unidad). When m² is zero or negative,
     * the calculated quantity should be zero.
     *
     * **Validates: Requirements 4.2, 4.5**
     */
    public function test_property8_material_quantity_calculation(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Generate random m2_por_unidad > 0 and a random precio
            $m2PorUnidad = fake()->randomFloat(4, 0.01, 10.0);
            $precioBase = fake()->randomFloat(4, 0.01, 500.0);
            $product = $this->createProductWithSpecsAndPrice($m2PorUnidad, $precioBase);

            // --- Positive m² case ---
            $m2 = fake()->randomFloat(2, 0.01, 1000.0);
            $result = $this->service->calculate($product, $m2);

            $expectedQuantity = (int) ceil($m2 / $m2PorUnidad);
            $actualQuantity = $result['materiales'][0]['cantidad_total'];

            $this->assertEquals(
                $expectedQuantity,
                $actualQuantity,
                "Iteration {$i} (positive m²): m2={$m2}, m2_por_unidad={$m2PorUnidad}. "
                . "Expected ceil({$m2}/{$m2PorUnidad})={$expectedQuantity}, got {$actualQuantity}"
            );

            // --- Zero m² case ---
            $resultZero = $this->service->calculate($product, 0.0);
            $this->assertEquals(
                0,
                $resultZero['materiales'][0]['cantidad_total'],
                "Iteration {$i} (zero m²): Expected quantity 0, got {$resultZero['materiales'][0]['cantidad_total']}"
            );

            // --- Negative m² case ---
            $negativeM2 = fake()->randomFloat(2, -1000.0, -0.01);
            $resultNeg = $this->service->calculate($product, $negativeM2);
            $this->assertEquals(
                0,
                $resultNeg['materiales'][0]['cantidad_total'],
                "Iteration {$i} (negative m²={$negativeM2}): Expected quantity 0, got {$resultNeg['materiales'][0]['cantidad_total']}"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 9: Breakdown row total consistency
     *
     * For any material breakdown row in the calculator result, the total field should
     * equal cantidad_total * precio_unitario, rounded to 2 decimal places.
     *
     * **Validates: Requirements 4.3**
     */
    public function test_property9_breakdown_row_total_consistency(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            $m2PorUnidad = fake()->randomFloat(4, 0.01, 10.0);
            $precioBase = fake()->randomFloat(4, 0.01, 500.0);
            $product = $this->createProductWithSpecsAndPrice($m2PorUnidad, $precioBase);

            $m2 = fake()->randomFloat(2, 0.01, 1000.0);
            $result = $this->service->calculate($product, $m2);

            foreach ($result['materiales'] as $index => $row) {
                $expectedTotal = round($row['cantidad_total'] * $row['precio_unitario'], 2);

                $this->assertEquals(
                    $expectedTotal,
                    $row['total'],
                    "Iteration {$i}, row {$index}: "
                    . "cantidad_total={$row['cantidad_total']} * precio_unitario={$row['precio_unitario']} "
                    . "= {$expectedTotal}, but got total={$row['total']}"
                );
            }
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 10: Merma calculation
     *
     * For any subtotal value and merma percentage (0-100), the total_con_merma should
     * equal subtotal * (1 + merma_porcentaje / 100), rounded to 2 decimal places.
     *
     * **Validates: Requirements 4.4**
     */
    public function test_property10_merma_calculation(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            $m2PorUnidad = fake()->randomFloat(4, 0.01, 10.0);
            $precioBase = fake()->randomFloat(4, 0.01, 500.0);
            $product = $this->createProductWithSpecsAndPrice($m2PorUnidad, $precioBase);

            $m2 = fake()->randomFloat(2, 0.01, 1000.0);
            $mermaPorcentaje = fake()->randomFloat(2, 0.0, 100.0);

            $result = $this->service->calculate($product, $m2, $mermaPorcentaje);

            $subtotal = $result['subtotal_sin_merma'];
            $expectedTotal = round($subtotal * (1 + $mermaPorcentaje / 100), 2);

            $this->assertEquals(
                $expectedTotal,
                $result['total_con_merma'],
                "Iteration {$i}: subtotal={$subtotal}, merma={$mermaPorcentaje}%. "
                . "Expected total_con_merma={$expectedTotal}, got {$result['total_con_merma']}"
            );

            // Also verify the merma_porcentaje is echoed back correctly
            $this->assertEquals(
                $mermaPorcentaje,
                $result['merma_porcentaje'],
                "Iteration {$i}: merma_porcentaje should be {$mermaPorcentaje}, got {$result['merma_porcentaje']}"
            );
        }
    }
}
