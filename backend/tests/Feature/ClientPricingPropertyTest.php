<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Quote;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for client pricing and quote persistence.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class ClientPricingPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Feature: web-portal-product-catalog, Property 15: Client prices match their assigned tarifa
     *
     * For any authenticated user linked to a Customer with a specific tipo_cliente_id,
     * all product prices returned by the client products endpoint should correspond to
     * the ProductPrice record for that tipo_cliente_id, not the PVP price.
     *
     * **Validates: Requirements 7.1, 7.3**
     */
    public function test_property15_client_prices_match_their_assigned_tarifa(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean relevant tables between iterations
            Quote::query()->delete();
            ProductPrice::query()->delete();
            Product::query()->forceDelete();
            User::query()->delete();
            Customer::query()->delete();
            CustomerType::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();

            // Create shared dependencies
            $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();
            $unit = Unit::factory()->create();

            // Create a PVP customer type and a client-specific customer type
            $pvpType = CustomerType::factory()->create(['nombre' => 'PVP', 'orden' => 1]);
            $clientType = CustomerType::factory()->create([
                'nombre' => fake()->randomElement(['CAMION VIP', 'CAMION', 'OFERTA', 'VIP', 'EMPRESAS', 'EMPRESAS A']),
                'orden' => fake()->numberBetween(2, 10),
            ]);

            // Create 1-5 products with both PVP and client prices
            $productCount = fake()->numberBetween(1, 5);
            $expectedPrices = [];

            for ($j = 0; $j < $productCount; $j++) {
                $product = Product::factory()->create([
                    'categoria_id' => $category->id,
                    'marca_id' => $brand->id,
                    'proveedor_principal_id' => $supplier->id,
                    'unidad_base_id' => $unit->id,
                    'visible_web' => true,
                ]);

                $pvpPrice = fake()->randomFloat(4, 10, 500);
                $clientPrice = fake()->randomFloat(4, 5, 400);

                // PVP price (lower tipo_cliente_id order)
                ProductPrice::factory()->create([
                    'producto_id' => $product->id,
                    'tipo_cliente_id' => $pvpType->id,
                    'precio_base' => $pvpPrice,
                    'activo' => true,
                ]);

                // Client-specific price
                ProductPrice::factory()->create([
                    'producto_id' => $product->id,
                    'tipo_cliente_id' => $clientType->id,
                    'precio_base' => $clientPrice,
                    'activo' => true,
                ]);

                $expectedPrices[$product->id] = (float) $clientPrice;
            }

            // Create customer linked to the client type
            $customer = Customer::create([
                'codigo' => fake()->unique()->bothify('CLI-####'),
                'nombre_comercial' => fake()->company(),
                'razon_social' => fake()->company(),
                'nif_cif' => fake()->unique()->bothify('B########'),
                'tipo_cliente_id' => $clientType->id,
                'activo' => true,
            ]);

            // Create user linked to the customer
            $user = User::factory()->create([
                'customer_id' => $customer->id,
                'estado' => 'activo',
            ]);

            // Call the client products endpoint
            $response = $this->actingAs($user, 'sanctum')
                ->getJson('/api/client/products');

            $response->assertStatus(200,
                "Iteration {$i}: Client products endpoint should return 200"
            );

            $data = $response->json('data');

            $this->assertCount($productCount, $data,
                "Iteration {$i}: Expected {$productCount} products, got " . count($data)
            );

            // Verify each product's price matches the client type price, not PVP
            foreach ($data as $productData) {
                $productId = $productData['id'];
                $returnedPrice = (float) $productData['precio'];
                $expectedPrice = $expectedPrices[$productId];

                $this->assertEqualsWithDelta(
                    $expectedPrice,
                    $returnedPrice,
                    0.01,
                    "Iteration {$i}: Product {$productId} price should be client price {$expectedPrice}, got {$returnedPrice}"
                );
            }
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 15: Client detail prices match tarifa
     *
     * For any authenticated user linked to a Customer, the product detail endpoint
     * should return the client-specific price, not the PVP price.
     *
     * **Validates: Requirements 7.1, 7.3**
     */
    public function test_property15_client_detail_prices_match_tarifa(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean relevant tables
            ProductPrice::query()->delete();
            Product::query()->forceDelete();
            User::query()->delete();
            Customer::query()->delete();
            CustomerType::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();

            $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();
            $unit = Unit::factory()->create();

            $pvpType = CustomerType::factory()->create(['nombre' => 'PVP', 'orden' => 1]);
            $clientType = CustomerType::factory()->create(['orden' => fake()->numberBetween(2, 10)]);

            $product = Product::factory()->create([
                'categoria_id' => $category->id,
                'marca_id' => $brand->id,
                'proveedor_principal_id' => $supplier->id,
                'unidad_base_id' => $unit->id,
                'visible_web' => true,
            ]);

            $pvpPrice = fake()->randomFloat(4, 10, 500);
            $clientPrice = fake()->randomFloat(4, 5, 400);

            ProductPrice::factory()->create([
                'producto_id' => $product->id,
                'tipo_cliente_id' => $pvpType->id,
                'precio_base' => $pvpPrice,
                'activo' => true,
            ]);

            ProductPrice::factory()->create([
                'producto_id' => $product->id,
                'tipo_cliente_id' => $clientType->id,
                'precio_base' => $clientPrice,
                'activo' => true,
            ]);

            $customer = Customer::create([
                'codigo' => fake()->unique()->bothify('CLI-####'),
                'nombre_comercial' => fake()->company(),
                'razon_social' => fake()->company(),
                'nif_cif' => fake()->unique()->bothify('B########'),
                'tipo_cliente_id' => $clientType->id,
                'activo' => true,
            ]);

            $user = User::factory()->create([
                'customer_id' => $customer->id,
                'estado' => 'activo',
            ]);

            $response = $this->actingAs($user, 'sanctum')
                ->getJson("/api/client/products/{$product->id}/detail");

            $response->assertStatus(200,
                "Iteration {$i}: Client product detail should return 200"
            );

            $responseData = $response->json();
            $returnedBasePrice = (float) $responseData['precio']['base'];

            $this->assertEqualsWithDelta(
                (float) $clientPrice,
                $returnedBasePrice,
                0.01,
                "Iteration {$i}: Detail base price should be client price {$clientPrice}, got {$returnedBasePrice}"
            );
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 16: Quote persistence round-trip
     *
     * For any authenticated user who generates a quote (product + m² + merma),
     * the quote should be persisted and subsequently appear in the user's quote history
     * with matching product_id, m², merma, and total values.
     *
     * **Validates: Requirements 7.5**
     */
    public function test_property16_quote_persistence_round_trip(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean relevant tables
            Quote::query()->delete();
            ProductPrice::query()->delete();
            Product::query()->forceDelete();
            User::query()->delete();
            Customer::query()->delete();
            CustomerType::query()->delete();
            Category::query()->delete();
            Brand::query()->delete();
            Supplier::query()->delete();
            Unit::query()->delete();

            $category = Category::factory()->create(['nivel' => 1, 'activo' => true]);
            $brand = Brand::factory()->create();
            $supplier = Supplier::factory()->create();
            $unit = Unit::factory()->create();

            $product = Product::factory()->create([
                'categoria_id' => $category->id,
                'marca_id' => $brand->id,
                'proveedor_principal_id' => $supplier->id,
                'unidad_base_id' => $unit->id,
                'visible_web' => true,
            ]);

            $customerType = CustomerType::factory()->create();
            $customer = Customer::create([
                'codigo' => fake()->unique()->bothify('CLI-####'),
                'nombre_comercial' => fake()->company(),
                'razon_social' => fake()->company(),
                'nif_cif' => fake()->unique()->bothify('B########'),
                'tipo_cliente_id' => $customerType->id,
                'activo' => true,
            ]);

            $user = User::factory()->create([
                'customer_id' => $customer->id,
                'estado' => 'activo',
            ]);

            // Generate random quote data
            $m2 = fake()->randomFloat(2, 0.5, 500);
            $mermaPorcentaje = fake()->randomFloat(2, 0, 100);
            $subtotal = fake()->randomFloat(2, 10, 10000);
            $total = round($subtotal * (1 + $mermaPorcentaje / 100), 2);
            $resultadoJson = [
                'materiales' => [
                    [
                        'descripcion' => fake()->words(3, true),
                        'cantidad_total' => fake()->randomFloat(2, 1, 100),
                        'precio_unitario' => fake()->randomFloat(2, 1, 50),
                    ],
                ],
                'subtotal' => $subtotal,
                'merma' => $mermaPorcentaje,
                'total' => $total,
            ];

            // Store the quote via API
            $storeResponse = $this->actingAs($user, 'sanctum')
                ->postJson('/api/client/presupuestos', [
                    'product_id' => $product->id,
                    'm2' => $m2,
                    'merma_porcentaje' => $mermaPorcentaje,
                    'subtotal' => $subtotal,
                    'total' => $total,
                    'resultado_json' => $resultadoJson,
                ]);

            $storeResponse->assertStatus(201,
                "Iteration {$i}: Store quote should return 201"
            );

            $quoteId = $storeResponse->json('id');
            $this->assertNotNull($quoteId,
                "Iteration {$i}: Store response should contain quote id"
            );

            // Retrieve the quote history via API
            $historyResponse = $this->actingAs($user, 'sanctum')
                ->getJson('/api/client/presupuestos');

            $historyResponse->assertStatus(200,
                "Iteration {$i}: Quote history should return 200"
            );

            $historyData = $historyResponse->json('data');

            $this->assertNotEmpty($historyData,
                "Iteration {$i}: Quote history should not be empty"
            );

            // Find the quote we just created
            $foundQuote = collect($historyData)->firstWhere('id', $quoteId);

            $this->assertNotNull($foundQuote,
                "Iteration {$i}: Created quote (id={$quoteId}) should appear in history"
            );

            // Verify round-trip data integrity
            $this->assertEquals($product->nombre, $foundQuote['producto'],
                "Iteration {$i}: Quote product name should match"
            );

            $this->assertEqualsWithDelta(
                $m2,
                (float) $foundQuote['m2'],
                0.01,
                "Iteration {$i}: Quote m2 should match. Expected {$m2}, got {$foundQuote['m2']}"
            );

            $this->assertEqualsWithDelta(
                $mermaPorcentaje,
                (float) $foundQuote['merma_porcentaje'],
                0.01,
                "Iteration {$i}: Quote merma should match. Expected {$mermaPorcentaje}, got {$foundQuote['merma_porcentaje']}"
            );

            $this->assertEqualsWithDelta(
                $total,
                (float) $foundQuote['total'],
                0.01,
                "Iteration {$i}: Quote total should match. Expected {$total}, got {$foundQuote['total']}"
            );

            // Also verify directly in the database
            $dbQuote = Quote::find($quoteId);
            $this->assertNotNull($dbQuote,
                "Iteration {$i}: Quote should exist in database"
            );
            $this->assertEquals($product->id, $dbQuote->product_id,
                "Iteration {$i}: DB quote product_id should match"
            );
            $this->assertEquals($user->id, $dbQuote->user_id,
                "Iteration {$i}: DB quote user_id should match"
            );
        }
    }
}
