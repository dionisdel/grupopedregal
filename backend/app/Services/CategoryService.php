<?php

namespace App\Services;

use App\Models\Category;
use Illuminate\Support\Collection;

class CategoryService
{
    /**
     * Returns the full category tree as nested arrays.
     * Loads all categories in a single query and builds the tree in memory.
     * Each node includes: id, nombre, slug, descripcion, imagen_banner_url,
     * imagen_thumbnail_url, parent_id, orden, activo, children (recursive),
     * product_count.
     */
    public function getTree(): array
    {
        $categories = Category::withCount('products')
            ->orderBy('orden')
            ->orderBy('nombre')
            ->get();

        return $this->buildTree($categories, null);
    }

    /**
     * Resolves a category by its hierarchical slug path.
     * E.g. "banos/aparatos-sanitarios/inodoros" walks root→child→grandchild.
     * Returns null if any segment doesn't match.
     */
    public function resolveByPath(string $path): ?Category
    {
        $slugs = array_filter(explode('/', trim($path, '/')));

        if (empty($slugs)) {
            return null;
        }

        $parentId = null;

        foreach ($slugs as $slug) {
            $category = Category::where('slug', $slug)
                ->where(function ($query) use ($parentId) {
                    if ($parentId === null) {
                        $query->whereNull('parent_id');
                    } else {
                        $query->where('parent_id', $parentId);
                    }
                })
                ->first();

            if (!$category) {
                return null;
            }

            $parentId = $category->id;
        }

        return $category;
    }

    /**
     * Reorders sibling categories by updating the `orden` field
     * to match each category's position in the given array.
     */
    public function reorderSiblings(array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            Category::where('id', $id)->update(['orden' => $index]);
        }
    }

    /**
     * Returns an array of ancestors from root to the given category.
     * Each element has: id, nombre, slug, full_path.
     */
    public function getBreadcrumb(Category $category): array
    {
        $breadcrumb = [];
        $current = $category;

        // Walk up the tree collecting ancestors
        while ($current) {
            $breadcrumb[] = $current;
            $current = $current->parent;
        }

        // Reverse so root comes first
        $breadcrumb = array_reverse($breadcrumb);

        // Build full_path for each node
        $result = [];
        $slugParts = [];

        foreach ($breadcrumb as $node) {
            $slugParts[] = $node->slug;
            $result[] = [
                'id'        => $node->id,
                'nombre'    => $node->nombre,
                'slug'      => $node->slug,
                'full_path' => implode('/', $slugParts),
            ];
        }

        return $result;
    }

    /**
     * Returns the full slug path from root to the given category.
     * E.g. "banos/aparatos-sanitarios/inodoros".
     */
    public function getFullPath(Category $category): string
    {
        $slugs = [];
        $current = $category;

        while ($current) {
            array_unshift($slugs, $current->slug);
            $current = $current->parent;
        }

        return implode('/', $slugs);
    }

    /**
     * Recursively builds a nested tree array from a flat collection.
     */
    private function buildTree(Collection $categories, ?int $parentId): array
    {
        return $categories
            ->where('parent_id', $parentId)
            ->values()
            ->map(function (Category $category) use ($categories) {
                return [
                    'id'                   => $category->id,
                    'nombre'               => $category->nombre,
                    'slug'                 => $category->slug,
                    'descripcion'          => $category->descripcion,
                    'imagen_banner_url'    => $category->imagen_banner_url,
                    'imagen_thumbnail_url' => $category->imagen_thumbnail_url,
                    'parent_id'            => $category->parent_id,
                    'orden'                => $category->orden,
                    'activo'               => $category->activo,
                    'children'             => $this->buildTree($categories, $category->id),
                    'product_count'        => $category->products_count ?? 0,
                ];
            })
            ->all();
    }
}
