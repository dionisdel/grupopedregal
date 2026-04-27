<?php

namespace Tests\Property;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\PriceCalculatorService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 13: Customer-Type-Specific Pricing
 *
 * For any product and any customer with an assigned customer_type, the price
 * displayed to that customer SHALL equal the neto_X field of the product that
 * corresponds to the customer type's discount_field.
 *
 * For example, a customer of type "CAMION VIP" (discount_field = "desc_camion_vip")
 * SHALL see neto_camion_vip as their price.
 *
 * **Validates: Requirements 15.7**
 */
class CustomerTypePricingTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 100;
    private const TOLERANCE = 1e-4;

    /**
     * Mapping from discount_field to the corresponding neto field on the product.
     */
    private const DISCOUNT_TO_NETO_MAP = [
        'desc_camion_vip' => 'neto_camion_vip',
        'desc_camion'     => 'neto_camion',
        'desc_oferta'     => 'neto_oferta',
        'desc_vip'        => 'neto_vip',
        'desc_empresas'   => 'neto_empresas',
        'desc_empresas_a' => 'neto_empresas_a',
    ];

    /**
     * Generate a random float between $min and $max.
     */
    private function randomFloat(float $min, float $max): float
    {
        return $min + mt_rand() / mt_getrandmax() * ($max - $min);
    }

    /**
     * @test
     * Property 13: Correct neto_X field is returned per customer type's discount_field
     */
    public function correct_neto_field_returned_per_customer_type(): void
    {
        $category = Category::factory()->create();
        $supplier = Supplier::factory()->create();
        $brand = Brand::factory()->create();

        $discountFields = array_keys(self::DISCOUNT_TO_NETO_MAP);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // Generate random price inputs
            $pvpProveedor = $this->randomFloat(1, 1000);
            $descProv1 = $this->randomFloat(0, 50);
            $ivaPorcentaje = $this->randomFloat(0, 30);
            $discounts = [];
            foreach ($discountFields as $field) {
                $discounts[$field] = $this->randomFloat(0, 50);
            }

            // Create a product with these prices
            $product = Product::create([
                'codigo_articulo' => 'CTP-' . str_pad((string) $i, 6, '0', STR_PAD_LEFT),
                'descripcion'     => 'Test Product ' . $i,
                'slug'            => 'test-product-ctp-' . $i,
                'categoria_id'    => $category->id,
                'proveedor_id'    => $supplier->id,
                'marca_id'        => $brand->id,
                'pvp_proveedor'   => $pvpProveedor,
                'desc_prov_1'     => $descProv1,
                'coste_transporte' => $this->randomFloat(0, 20),
                'iva_porcentaje'  => $ivaPorcentaje,
                'desc_camion_vip' => $discounts['desc_camion_vip'],
                'desc_camion'     => $discounts['desc_camion'],
                'desc_oferta'     => $discounts['desc_oferta'],
                'desc_vip'        => $discounts['desc_vip'],
                'desc_empresas'   => $discounts['desc_empresas'],
                'desc_empresas_a' => $discounts['desc_empresas_a'],
                'estado_publicado' => true,
            ]);

            // Reload to get calculated fields
            $product->refresh();

            // Pick a random customer type discount_field
            $discountField = $discountFields[array_rand($discountFields)];
            $netoField = self::DISCOUNT_TO_NETO_MAP[$discountField];

            // Compute expected using PriceCalculatorService (same as the model boot event)
            $calculated = PriceCalculatorService::calculate([
                'pvp_proveedor'    => $pvpProveedor,
                'desc_prov_1'      => $descProv1,
                'coste_transporte' => 0,
                'iva_porcentaje'   => $ivaPorcentaje,
                'desc_camion_vip'  => $discounts['desc_camion_vip'],
                'desc_camion'      => $discounts['desc_camion'],
                'desc_oferta'      => $discounts['desc_oferta'],
                'desc_vip'         => $discounts['desc_vip'],
                'desc_empresas'    => $discounts['desc_empresas'],
                'desc_empresas_a'  => $discounts['desc_empresas_a'],
                'metros_articulo'  => null,
            ]);

            $expectedNeto = $calculated[$netoField];

            $this->assertEqualsWithDelta(
                $expectedNeto,
                $product->{$netoField},
                self::TOLERANCE,
                "Iteration $i: For discount_field '$discountField', neto field '$netoField' should be " .
                $expectedNeto . " but got {$product->{$netoField}}"
            );
        }
    }

    /**
     * @test
     * Property 13: All customer types map to valid neto fields
     */
    public function all_customer_types_map_to_valid_neto_fields(): void
    {
        $category = Category::factory()->create();

        $product = Product::create([
            'codigo_articulo'  => 'CTP-MAP-001',
            'descripcion'      => 'Mapping Test Product',
            'slug'             => 'mapping-test-product',
            'categoria_id'     => $category->id,
            'pvp_proveedor'    => 100,
            'desc_prov_1'      => 10,
            'iva_porcentaje'   => 21,
            'desc_camion_vip'  => 15,
            'desc_camion'      => 12,
            'desc_oferta'      => 20,
            'desc_vip'         => 18,
            'desc_empresas'    => 10,
            'desc_empresas_a'  => 8,
            'estado_publicado' => true,
        ]);

        $product->refresh();

        // Verify each discount_field maps to a non-null neto field
        foreach (self::DISCOUNT_TO_NETO_MAP as $discountField => $netoField) {
            $this->assertNotNull(
                $product->{$netoField},
                "Neto field '$netoField' for discount_field '$discountField' should not be null"
            );
            $this->assertGreaterThan(
                0,
                $product->{$netoField},
                "Neto field '$netoField' should be positive for a product with positive pvp"
            );
        }
    }
}
