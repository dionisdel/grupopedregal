<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * GET /api/categories/tree
     * Returns the full category tree.
     */
    public function tree(): JsonResponse
    {
        return response()->json($this->categoryService->getTree());
    }

    /**
     * GET /api/categories/by-path?path=...
     * Resolves a category by its hierarchical slug path.
     */
    public function byPath(Request $request): JsonResponse
    {
        $path = $request->query('path', '');

        if (empty($path)) {
            return response()->json(['message' => 'El parámetro path es obligatorio.'], 400);
        }

        $category = $this->categoryService->resolveByPath($path);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $category->load('children');

        return response()->json([
            'id'                   => $category->id,
            'nombre'               => $category->nombre,
            'slug'                 => $category->slug,
            'descripcion'          => $category->descripcion,
            'imagen_banner_url'    => $category->imagen_banner_url,
            'imagen_thumbnail_url' => $category->imagen_thumbnail_url,
            'parent_id'            => $category->parent_id,
            'orden'                => $category->orden,
            'activo'               => $category->activo,
            'breadcrumb'           => $this->categoryService->getBreadcrumb($category),
            'children'             => $category->children
                ->sortBy('orden')
                ->values()
                ->map(fn (Category $child) => [
                    'id'                   => $child->id,
                    'nombre'               => $child->nombre,
                    'slug'                 => $child->slug,
                    'imagen_thumbnail_url' => $child->imagen_thumbnail_url,
                    'orden'                => $child->orden,
                ]),
        ]);
    }

    /**
     * GET /api/categories/{id}/products
     * Paginated products for a category (only published).
     */
    public function products(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $products = Product::where('categoria_id', $id)
            ->where('estado_publicado', true)
            ->orderBy('descripcion')
            ->select([
                'id',
                'codigo_articulo',
                'descripcion',
                'slug',
                'marca_id',
                'imagen_url',
                'pvp',
                'pre_pvp',
                'iva_porcentaje',
                'filtros_dinamicos',
                'estado_publicado',
            ])
            ->with('marca:id,nombre')
            ->paginate(20);

        return response()->json($products);
    }

    /**
     * GET /api/categories/{id}/filters
     * Dynamic filter keys/values for a category's published products.
     */
    public function filters(int $id): JsonResponse
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Categoría no encontrada.'], 404);
        }

        $allFilters = Product::where('categoria_id', $id)
            ->where('estado_publicado', true)
            ->whereNotNull('filtros_dinamicos')
            ->pluck('filtros_dinamicos');

        $aggregated = [];

        foreach ($allFilters as $filters) {
            if (!is_array($filters)) {
                continue;
            }
            foreach ($filters as $key => $value) {
                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = [];
                }
                if (!in_array($value, $aggregated[$key], true)) {
                    $aggregated[$key][] = $value;
                }
            }
        }

        // Sort values within each key
        foreach ($aggregated as &$values) {
            sort($values);
        }

        return response()->json($aggregated);
    }
}
