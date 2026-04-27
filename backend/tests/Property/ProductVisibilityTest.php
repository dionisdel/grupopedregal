<?php

namespace Tests\Property;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 5: Product Visibility by Publication Status
 *
 * Property-based test that generates products with mixed estado_publicado values
 * and verifies the public catalog API returns only published products.
 *
 * Validates: Requirements 3.3
 */
class ProductVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 20;

    /**
     * @test
     * Property 5: Product Visibility by Publication Status
     *
     * For any set of products with mixed estado_publicado values (true/false),
     * the public catalog API SHALL return only products where estado_publicado = true.
     * The count of returned products SHALL equal the count of published products in the set.
     *
     * Validates: Requirements 3.3
     */
    public function public_api_returns_only_published_products(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // 1. Create a category
            $category = Category::factory()->create();

            // 2. Generate N random products (5-15) with random publication status
            $totalProducts = mt_rand(5, 15);
            $publishedCount = 0;

            for ($j = 0; $j < $totalProducts; $j++) {
                $isPublished = (bool) mt_rand(0, 1);

                $factory = Product::factory()->state([
                    'categoria_id' => $category->id,
                ]);

                if (!$isPublished) {
                    $factory = $factory->unpublished();
                }

                $factory->create();

                if ($isPublished) {
                    $publishedCount++;
                }
            }

            // 3. Call the public API endpoint
            $response = $this->getJson("/api/categories/{$category->id}/products");
            $response->assertOk();

            $data = $response->json('data');

            // 4. Assert: count of returned products equals count of published products
            $this->assertCount(
                $publishedCount,
                $data,
                "Iteration $i: Expected $publishedCount published products out of $totalProducts total, got " . count($data)
            );

            // 5. Assert: every returned product has estado_publicado = true
            foreach ($data as $index => $product) {
                $this->assertTrue(
                    (bool) $product['estado_publicado'],
                    "Iteration $i: Product at index $index should have estado_publicado=true, got false. Product ID: {$product['id']}"
                );
            }

            // 6. Assert: no returned product has estado_publicado = false
            $returnedIds = array_column($data, 'id');
            $unpublishedInResponse = Product::whereIn('id', $returnedIds)
                ->where('estado_publicado', false)
                ->count();

            $this->assertEquals(
                0,
                $unpublishedInResponse,
                "Iteration $i: Found $unpublishedInResponse unpublished products in the API response"
            );

            // Clean up for next iteration
            Product::where('categoria_id', $category->id)->forceDelete();
            $category->forceDelete();
        }
    }
}
