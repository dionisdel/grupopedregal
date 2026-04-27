<?php

namespace Tests\Property;

use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Feature: portal-ecommerce-v2, Property 3: Category Tree Unlimited Depth
 *
 * Property-based test that creates chains of N nested categories (N=1 to 10),
 * verifies traversal from deepest to root yields N nodes, and the full tree
 * query returns all N nodes with correct parent-child relationships.
 *
 * **Validates: Requirements 2.1**
 */
class CategoryTreeUnlimitedDepthTest extends TestCase
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
     * Create a chain of N nested categories.
     *
     * @return Category[] Array from root to deepest
     */
    private function createChain(int $n): array
    {
        $chain = [];
        $parentId = null;

        for ($i = 0; $i < $n; $i++) {
            $cat = Category::create([
                'parent_id' => $parentId,
                'nombre' => 'Depth' . ($i + 1) . '-' . Str::random(5),
                'slug' => 'depth-' . ($i + 1) . '-' . Str::random(8),
                'descripcion' => null,
                'orden' => 0,
                'activo' => true,
            ]);
            $chain[] = $cat;
            $parentId = $cat->id;
        }

        return $chain;
    }

    /**
     * @test
     * Property 3: Category Tree Unlimited Depth
     *
     * For any depth N >= 1, creating a chain of N nested categories SHALL result
     * in a tree where traversing from the deepest node to the root yields exactly
     * N nodes.
     *
     * Validates: Requirements 2.1
     */
    public function traversal_from_deepest_to_root_yields_n_nodes(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $n = mt_rand(1, 10);
            $chain = $this->createChain($n);
            $deepest = end($chain);

            // Traverse from deepest to root
            $traversed = [];
            $current = $deepest;
            while ($current) {
                $traversed[] = $current;
                $current = $current->parent;
            }

            $this->assertCount(
                $n,
                $traversed,
                "Iteration $i (N=$n): Traversal from deepest to root should yield $n nodes, got " . count($traversed)
            );

            // Verify the traversal order is deepest → root
            $this->assertEquals(
                $deepest->id,
                $traversed[0]->id,
                "Iteration $i: First traversed node should be the deepest"
            );

            if ($n > 1) {
                $root = $chain[0];
                $this->assertEquals(
                    $root->id,
                    end($traversed)->id,
                    "Iteration $i: Last traversed node should be the root"
                );
            }

            // Clean up
            foreach (array_reverse($chain) as $cat) {
                $cat->forceDelete();
            }
        }
    }

    /**
     * @test
     * Property 3: Category Tree Unlimited Depth — Tree query
     *
     * The full tree query returns all N nodes with correct parent-child relationships.
     *
     * Validates: Requirements 2.1
     */
    public function full_tree_contains_all_nodes_with_correct_relationships(): void
    {
        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $n = mt_rand(1, 10);
            $chain = $this->createChain($n);

            // Get the full tree
            $tree = $this->categoryService->getTree();

            // Find our root node in the tree
            $rootId = $chain[0]->id;
            $rootNode = $this->findNodeInTree($tree, $rootId);

            $this->assertNotNull(
                $rootNode,
                "Iteration $i (N=$n): Root category ID $rootId not found in tree"
            );

            // Walk down the tree verifying each level
            $currentNode = $rootNode;
            for ($level = 1; $level < $n; $level++) {
                $expectedChildId = $chain[$level]->id;
                $childNode = null;

                foreach ($currentNode['children'] as $child) {
                    if ($child['id'] === $expectedChildId) {
                        $childNode = $child;
                        break;
                    }
                }

                $this->assertNotNull(
                    $childNode,
                    "Iteration $i: Expected child ID $expectedChildId at level $level not found in tree"
                );

                $this->assertEquals(
                    $chain[$level - 1]->id,
                    $childNode['parent_id'],
                    "Iteration $i: parent_id mismatch at level $level"
                );

                $currentNode = $childNode;
            }

            // Clean up
            foreach (array_reverse($chain) as $cat) {
                $cat->forceDelete();
            }
        }
    }

    /**
     * Recursively find a node by ID in the tree array.
     */
    private function findNodeInTree(array $tree, int $id): ?array
    {
        foreach ($tree as $node) {
            if ($node['id'] === $id) {
                return $node;
            }
            $found = $this->findNodeInTree($node['children'], $id);
            if ($found) {
                return $found;
            }
        }
        return null;
    }
}
