<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature: web-portal-product-catalog
 *
 * Property-based tests for category display.
 * Uses Faker with iteration loops to simulate PBT (100+ iterations).
 */
class CategoryPropertyTest extends TestCase
{
    use RefreshDatabase;

    private const PBT_ITERATIONS = 100;

    /**
     * Feature: web-portal-product-catalog, Property 1: Category cards display all required fields
     *
     * For any set of active level-1 categories, the API response should contain all required
     * fields (id, nombre, slug, descripcion_web, imagen_url, orden) for each category.
     * When imagen_url is null, it should still be present in the response.
     *
     * **Validates: Requirements 1.2, 1.3**
     */
    public function test_property1_category_cards_display_all_required_fields(): void
    {
        $requiredFields = ['id', 'nombre', 'slug', 'descripcion_web', 'imagen_url', 'orden'];

        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean categories between iterations
            Category::query()->delete();

            $count = fake()->numberBetween(1, 5);
            $categories = [];

            for ($j = 0; $j < $count; $j++) {
                // Randomly decide if imagen_url is null or has a value
                $imagenUrl = fake()->boolean(50) ? fake()->imageUrl() : null;

                $categories[] = Category::factory()->create([
                    'nivel' => 1,
                    'activo' => true,
                    'imagen_url' => $imagenUrl,
                    'orden' => $j + 1,
                ]);
            }

            $response = $this->getJson('/api/categories/public');
            $response->assertStatus(200);

            $data = $response->json();

            $this->assertCount(
                $count,
                $data,
                "Iteration {$i}: Expected {$count} categories, got " . count($data)
            );

            foreach ($data as $index => $categoryData) {
                // Every required field must be present as a key in the response
                foreach ($requiredFields as $field) {
                    $this->assertArrayHasKey(
                        $field,
                        $categoryData,
                        "Iteration {$i}, category {$index}: Missing required field '{$field}'"
                    );
                }

                // Verify the data matches what was created
                $original = $categories[$index];
                $this->assertEquals(
                    $original->id,
                    $categoryData['id'],
                    "Iteration {$i}, category {$index}: id mismatch"
                );
                $this->assertEquals(
                    $original->nombre,
                    $categoryData['nombre'],
                    "Iteration {$i}, category {$index}: nombre mismatch"
                );
                $this->assertEquals(
                    $original->slug,
                    $categoryData['slug'],
                    "Iteration {$i}, category {$index}: slug mismatch"
                );
                $this->assertEquals(
                    $original->descripcion_web,
                    $categoryData['descripcion_web'],
                    "Iteration {$i}, category {$index}: descripcion_web mismatch"
                );

                // imagen_url must be present even when null
                $this->assertTrue(
                    array_key_exists('imagen_url', $categoryData),
                    "Iteration {$i}, category {$index}: imagen_url key must be present even when null"
                );
                $this->assertEquals(
                    $original->imagen_url,
                    $categoryData['imagen_url'],
                    "Iteration {$i}, category {$index}: imagen_url mismatch"
                );

                $this->assertEquals(
                    $original->orden,
                    $categoryData['orden'],
                    "Iteration {$i}, category {$index}: orden mismatch"
                );
            }
        }
    }

    /**
     * Feature: web-portal-product-catalog, Property 2: Categories are ordered by the orden field
     *
     * For any set of active level-1 categories with distinct orden values,
     * the response should be ordered by orden ascending.
     *
     * **Validates: Requirements 1.7**
     */
    public function test_property2_categories_are_ordered_by_orden_field(): void
    {
        for ($i = 0; $i < self::PBT_ITERATIONS; $i++) {
            // Clean categories between iterations
            Category::query()->delete();

            $count = fake()->numberBetween(2, 8);

            // Generate distinct random orden values
            $ordenValues = fake()->unique(true)->randomElements(range(1, 1000), $count);

            foreach ($ordenValues as $orden) {
                Category::factory()->create([
                    'nivel' => 1,
                    'activo' => true,
                    'orden' => $orden,
                ]);
            }

            $response = $this->getJson('/api/categories/public');
            $response->assertStatus(200);

            $data = $response->json();

            $this->assertCount(
                $count,
                $data,
                "Iteration {$i}: Expected {$count} categories, got " . count($data)
            );

            // Verify ascending orden order
            $returnedOrdens = array_column($data, 'orden');
            $sortedOrdens = $returnedOrdens;
            sort($sortedOrdens);

            $this->assertEquals(
                $sortedOrdens,
                $returnedOrdens,
                "Iteration {$i}: Categories not ordered by orden ascending. Got: " . implode(', ', $returnedOrdens)
            );

            // Verify strictly ascending (no duplicates, each next > previous)
            for ($j = 1; $j < count($returnedOrdens); $j++) {
                $this->assertGreaterThan(
                    $returnedOrdens[$j - 1],
                    $returnedOrdens[$j],
                    "Iteration {$i}: orden at position {$j} ({$returnedOrdens[$j]}) should be greater than position " . ($j - 1) . " ({$returnedOrdens[$j - 1]})"
                );
            }
        }
    }
}
