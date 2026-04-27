<?php

namespace Tests\Property;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 14: Unique Product Code Constraint
 *
 * Property-based test that attempts to insert products with duplicate
 * codigo_articulo and verifies the database rejects the second insert
 * with a unique constraint violation.
 *
 * Validates: Requirements 3.2
 */
class UniqueProductCodeTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 20;

    /**
     * Generate a random codigo_articulo string.
     */
    private function randomCodigoArticulo(): string
    {
        $prefixes = ['ART', 'PRD', 'COD', 'REF', 'MAT', 'ITM'];
        $prefix = $prefixes[array_rand($prefixes)];
        $number = str_pad((string) mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
        $suffix = chr(mt_rand(65, 90)); // A-Z

        return "{$prefix}-{$number}{$suffix}";
    }

    /**
     * @test
     * Property 14: Unique Product Code Constraint
     *
     * For any two products, if they have the same codigo_articulo, the database
     * SHALL reject the second insert with a unique constraint violation.
     * No two products SHALL coexist with the same codigo_articulo.
     *
     * Validates: Requirements 3.2
     */
    public function duplicate_codigo_articulo_is_rejected(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            // 1. Generate a random codigo_articulo string
            $codigoArticulo = $this->randomCodigoArticulo();

            // 2. Create a product with that codigo_articulo
            $category = Category::factory()->create();
            $firstProduct = Product::factory()->create([
                'categoria_id' => $category->id,
                'codigo_articulo' => $codigoArticulo,
            ]);

            $this->assertDatabaseHas('products', [
                'id' => $firstProduct->id,
                'codigo_articulo' => $codigoArticulo,
            ]);

            // 3. Attempt to create a second product with the same codigo_articulo
            $threwException = false;
            try {
                Product::factory()->create([
                    'categoria_id' => $category->id,
                    'codigo_articulo' => $codigoArticulo,
                ]);
            } catch (QueryException $e) {
                $threwException = true;
            }

            // 4. Assert: the second insert throws a QueryException
            $this->assertTrue(
                $threwException,
                "Iteration $i: Expected QueryException for duplicate codigo_articulo '$codigoArticulo', but no exception was thrown."
            );

            // 5. Assert: only one product exists with that codigo_articulo
            $count = Product::where('codigo_articulo', $codigoArticulo)->count();
            $this->assertEquals(
                1,
                $count,
                "Iteration $i: Expected exactly 1 product with codigo_articulo '$codigoArticulo', found $count."
            );

            // Clean up for next iteration
            $firstProduct->forceDelete();
            $category->forceDelete();
        }
    }
}
