<?php

namespace Tests\Property;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 4: Category Reorder Persistence
 *
 * Property-based test that creates sibling categories, submits a random
 * permutation via CategoryService::reorderSiblings(), and verifies the
 * order persists after reload.
 *
 * **Validates: Requirements 2.9**
 */
class CategoryReorderPersistenceTest extends TestCase
{
    use RefreshDatabase;

    private const ITERATIONS = 30;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = new CategoryService();
    }

    /**
     * Create N sibling categories under the same parent.
     *
     * @return Category[]
     */
    private function createSiblings(int $count, ?int $parentId = null): array
    {
        $siblings = [];
        for ($i = 0; $i < $count; $i++) {
            $siblings[] = Category::create([
                'parent_id' => $parentId,
                'nombre' => 'Sibling ' . ($i + 1) . ' ' . Str::random(4),
                'slug' => 'sibling-' . ($i + 1) . '-' . Str::random(8),
                'descripcion' => null,
                'orden' => $i,
                'activo' => true,
            ]);
        }
        return $siblings;
    }

    /**
     * Generate a random permutation of an array.
     */
    private function randomPermutation(array $items): array
    {
        $shuffled = $items;
        shuffle($shuffled);
        return $shuffled;
    }

    /**
     * @test
     * Property 4: Category Reorder Persistence
     *
     * For any set of sibling categories and any permutation of their order,
     * after submitting the reorder request and reloading the tree, the categories
     * SHALL appear in the exact order specified by the permutation.
     *
     * Validates: Requirements 2.9
     */
    public function reorder_persists_after_reload(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $count = mt_rand(2, 8);
            $siblings = $this->createSiblings($count);

            // Extract IDs and create a random permutation
            $ids = array_map(fn (Category $c) => $c->id, $siblings);
            $permutedIds = $this->randomPermutation($ids);

            // Submit reorder
            $this->categoryService->reorderSiblings($permutedIds);

            // Reload from DB and verify order
            $reloaded = Category::whereIn('id', $ids)
                ->orderBy('orden')
                ->get();

            $reloadedIds = $reloaded->pluck('id')->all();

            $this->assertEquals(
                $permutedIds,
                $reloadedIds,
                "Iteration $i (count=$count): Reloaded order does not match submitted permutation. "
                . "Expected: [" . implode(',', $permutedIds) . "], "
                . "Got: [" . implode(',', $reloadedIds) . "]"
            );

            // Verify each category's orden field matches its position
            foreach ($reloaded as $idx => $cat) {
                $this->assertEquals(
                    $idx,
                    $cat->orden,
                    "Iteration $i: Category ID {$cat->id} should have orden=$idx, got {$cat->orden}"
                );
            }

            // Clean up
            foreach ($siblings as $cat) {
                $cat->forceDelete();
            }
        }
    }
}
