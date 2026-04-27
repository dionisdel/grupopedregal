<?php

namespace Tests\Property;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 2: Category Path Round-Trip
 *
 * Property-based test that creates categories at various depths, constructs
 * URL paths from slugs, resolves via CategoryService, and verifies the
 * original category is returned. Also verifies breadcrumb contains all
 * ancestors in order.
 *
 * **Validates: Requirements 2.2, 2.5**
 */
class CategoryPathRoundTripTest extends TestCase
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
     * Generate a unique slug for testing.
     */
    private function uniqueSlug(): string
    {
        return 'cat-' . Str::random(8) . '-' . mt_rand(1000, 9999);
    }

    /**
     * Create a chain of nested categories at a random depth (1 to 6).
     *
     * @return Category[] Array of categories from root to deepest
     */
    private function createCategoryChain(int $depth): array
    {
        $chain = [];
        $parentId = null;

        for ($d = 0; $d < $depth; $d++) {
            $slug = $this->uniqueSlug();
            $category = Category::create([
                'parent_id' => $parentId,
                'nombre' => 'Category ' . ($d + 1) . ' ' . Str::random(4),
                'slug' => $slug,
                'descripcion' => 'Test category at depth ' . ($d + 1),
                'orden' => 0,
                'activo' => true,
            ]);
            $chain[] = $category;
            $parentId = $category->id;
        }

        return $chain;
    }

    /**
     * @test
     * Property 2: Category Path Round-Trip
     *
     * For any category at any depth, constructing the URL path by concatenating
     * ancestor slugs from root to the category and then resolving that path via
     * CategoryService SHALL return the original category.
     *
     * Validates: Requirements 2.2, 2.5
     */
    public function path_round_trip_returns_original_category(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $depth = mt_rand(1, 6);
            $chain = $this->createCategoryChain($depth);
            $deepest = end($chain);

            // Construct URL path from slugs: root/child/grandchild/...
            $path = implode('/', array_map(fn (Category $c) => $c->slug, $chain));

            // Resolve via CategoryService
            $resolved = $this->categoryService->resolveByPath($path);

            $this->assertNotNull(
                $resolved,
                "Iteration $i (depth=$depth): resolveByPath('$path') returned null"
            );

            $this->assertEquals(
                $deepest->id,
                $resolved->id,
                "Iteration $i (depth=$depth): Expected category ID {$deepest->id}, got {$resolved->id} for path '$path'"
            );

            // Clean up
            foreach (array_reverse($chain) as $cat) {
                $cat->forceDelete();
            }
        }
    }

    /**
     * @test
     * Property 2: Category Path Round-Trip — Breadcrumb verification
     *
     * For any category at any depth, the breadcrumb SHALL contain all ancestors
     * in order from root to the current node.
     *
     * Validates: Requirements 2.5
     */
    public function breadcrumb_contains_all_ancestors_in_order(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $depth = mt_rand(1, 6);
            $chain = $this->createCategoryChain($depth);
            $deepest = end($chain);

            // Get breadcrumb for the deepest category
            $breadcrumb = $this->categoryService->getBreadcrumb($deepest);

            // Breadcrumb should have exactly $depth entries
            $this->assertCount(
                $depth,
                $breadcrumb,
                "Iteration $i (depth=$depth): Breadcrumb should have $depth entries, got " . count($breadcrumb)
            );

            // Each breadcrumb entry should match the corresponding chain category
            foreach ($chain as $idx => $cat) {
                $this->assertEquals(
                    $cat->id,
                    $breadcrumb[$idx]['id'],
                    "Iteration $i: Breadcrumb[$idx] should be category ID {$cat->id}, got {$breadcrumb[$idx]['id']}"
                );
                $this->assertEquals(
                    $cat->slug,
                    $breadcrumb[$idx]['slug'],
                    "Iteration $i: Breadcrumb[$idx] slug mismatch"
                );
            }

            // Verify full_path of the last breadcrumb entry matches the full path
            $expectedFullPath = implode('/', array_map(fn (Category $c) => $c->slug, $chain));
            $lastBreadcrumb = end($breadcrumb);
            $this->assertEquals(
                $expectedFullPath,
                $lastBreadcrumb['full_path'],
                "Iteration $i: Last breadcrumb full_path mismatch"
            );

            // Clean up
            foreach (array_reverse($chain) as $cat) {
                $cat->forceDelete();
            }
        }
    }
}
