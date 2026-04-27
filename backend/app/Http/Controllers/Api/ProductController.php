<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    /**
     * GET /api/products/{slug}
     * Product detail by slug (only published).
     */
    public function show(string $slug): JsonResponse
    {
        $product = Product::where('slug', $slug)
            ->where('estado_publicado', true)
            ->with(['marca:id,nombre', 'proveedor:id,nombre_comercial', 'categoria:id,nombre,slug,parent_id'])
            ->first();

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        return response()->json($product);
    }

    /**
     * GET /api/products/{id}/stock
     * Stock per warehouse for a product.
     */
    public function stock(int $id): JsonResponse
    {
        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $stock = $product->warehouses()
            ->where('active', true)
            ->get()
            ->map(fn ($warehouse) => [
                'almacen'  => $warehouse->name,
                'cantidad' => $warehouse->pivot->stock_quantity,
            ]);

        return response()->json($stock);
    }
}
