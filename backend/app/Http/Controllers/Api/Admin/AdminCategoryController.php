<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService
    ) {}

    /**
     * Returns the full category tree for admin management.
     */
    public function index(): JsonResponse
    {
        return response()->json($this->categoryService->getTree());
    }

    /**
     * Create a new category.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nombre'              => 'required|string|max:255',
            'slug'                => 'required|string|max:255',
            'parent_id'           => 'nullable|exists:categories,id',
            'descripcion'         => 'nullable|string',
            'imagen_banner_url'   => 'nullable|string|max:500',
            'imagen_thumbnail_url'=> 'nullable|string|max:500',
            'orden'               => 'nullable|integer',
        ]);

        $category = Category::create($validated);

        return response()->json($category, 201);
    }

    /**
     * Update an existing category.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'nombre'              => 'required|string|max:255',
            'slug'                => 'required|string|max:255',
            'parent_id'           => 'nullable|exists:categories,id',
            'descripcion'         => 'nullable|string',
            'imagen_banner_url'   => 'nullable|string|max:500',
            'imagen_thumbnail_url'=> 'nullable|string|max:500',
            'orden'               => 'nullable|integer',
        ]);

        $category->update($validated);

        return response()->json($category);
    }

    /**
     * Delete a category.
     */
    public function destroy(int $id): JsonResponse
    {
        $category = Category::findOrFail($id);
        $category->delete();

        return response()->json(null, 204);
    }

    /**
     * Reorder sibling categories.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'items'   => 'required|array',
            'items.*' => 'integer|exists:categories,id',
        ]);

        $this->categoryService->reorderSiblings($validated['items']);

        return response()->json(['message' => 'Categorías reordenadas correctamente.']);
    }
}
