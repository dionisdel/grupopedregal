<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for product detail endpoint.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class ProductDetailPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Create a visible product with an active PVP price.
     */
    private function createVisibleProductWithPrice(array $overrides = [], float $price = 10.0): Product
    {
        $product = Product::factory()->create(array_merge([
            'visible_web' => true,
        ], $overrides));

        $customerType = CustomerType::factory()->create(['codigo' => 'PVP-' . uniqid(), 'orden' => 1]);
        ProductPrice::factory()->create([
            'producto_id' => $product->id,
            'tipo_cliente_id' => $customerType->id,
            'precio_base' => $price,
            'activo' => true,
        ]);

        return $product;
    }

    /**
     * Feature: web-portal-product-catalog, Property 6: Description fallback logic
     *
     * For any product, if `descripcion_larga_web` is non-null and non-empty,
     * the detail endpoint should return `descripcion_larga_web` as the `descripcion` field;
     * otherwise it should return the `descripcion` field from the product.
     *
     * **Validates: Requirements 3.2, 3.3**
     */
    public function test_property6_description_fallback_logic(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Generate random description values
            $descripcionBase = fake()->sentence();
            $scenario = fake()->randomElement(['has_long_web', 'null_long_web', 'empty_long_web']);

            $productOverrides = [
                'descripcion' => $descripcionBase,
            ];

            $expectedDescripcion = null;

            switch ($scenario) {
                case 'has_long_web':
                    $descripcionLargaWeb = fake()->paragraph();
                    $productOverrides['descripcion_larga_web'] = $descripcionLargaWeb;
                    $expectedDescripcion = $descripcionLargaWeb;
                    break;

                case 'null_long_web':
                    $productOverrides['descripcion_larga_web'] = null;
                    $expectedDescripcion = $descripcionBase;
                    break;

                case 'empty_long_web':
                    $productOverrides['descripcion_larga_web'] = '';
                    $expectedDescripcion = $descripcionBase;
                    break;
            }

            $product = $this->createVisibleProductWithPrice(
                $productOverrides,
                fake()->randomFloat(2, 1, 500)
            );

            $response = $this->getJson("/api/products/{$product->id}/detail");
            $response->assertStatus(200);

            $data = $response->json();

            $this->assertArrayHasKey(
                'descripcion',
                $data,
                "Iteration {$i} ({$scenario}): Response must contain 'descripcion' field"
            );

            $this->assertEquals(
                $expectedDescripcion,
                $data['descripcion'],
                "Iteration {$i} ({$scenario}): Description fallback mismatch. " .
                "descripcion_larga_web=" . json_encode($product->descripcion_larga_web) .
                ", descripcion=" . json_encode($product->descripcion) .
                ", expected=" . json_encode($expectedDescripcion) .
                ", got=" . json_encode($data['descripcion'])
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 7: IVA calculation correctness
     *
     * For any product with a PVP price and a valid IVA rate (21%), the displayed
     * IVA amount should equal `precio_base * (21 / 100)` and the total should equal
     * `precio_base + iva_amount`, both rounded to 2 decimal places.
     *
     * **Validates: Requirements 3.4**
     */
    public function test_property7_iva_calculation_correctness(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean data between iterations
            Product::query()->forceDelete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();
            CustomerType::query()->delete();
            ProductPrice::query()->delete();

            // Generate a random price base
            $precioBase = fake()->randomFloat(2, 0.01, 9999.99);

            $product = $this->createVisibleProductWithPrice([], $precioBase);

            $response = $this->getJson("/api/products/{$product->id}/detail");
            $response->assertStatus(200);

            $data = $response->json();

            $this->assertArrayHasKey(
                'precio',
                $data,
                "Iteration {$i}: Response must contain 'precio' field"
            );

            $precio = $data['precio'];

            $this->assertArrayHasKey('base', $precio, "Iteration {$i}: precio must contain 'base'");
            $this->assertArrayHasKey('iva', $precio, "Iteration {$i}: precio must contain 'iva'");
            $this->assertArrayHasKey('total', $precio, "Iteration {$i}: precio must contain 'total'");

            // Expected calculations with 21% IVA
            $expectedBase = round($precioBase, 2);
            $expectedIva = round($precioBase * 21 / 100, 2);
            $expectedTotal = round($precioBase + $expectedIva, 2);

            $this->assertEquals(
                $expectedBase,
                $precio['base'],
                "Iteration {$i}: Base price mismatch. Input={$precioBase}, expected={$expectedBase}, got={$precio['base']}"
            );

            $this->assertEquals(
                $expectedIva,
                $precio['iva'],
                "Iteration {$i}: IVA mismatch. Base={$precioBase}, expected IVA={$expectedIva}, got={$precio['iva']}"
            );

            $this->assertEquals(
                $expectedTotal,
                $precio['total'],
                "Iteration {$i}: Total mismatch. Base={$precioBase}, IVA={$expectedIva}, expected total={$expectedTotal}, got={$precio['total']}"
            );
        }
    }
}
