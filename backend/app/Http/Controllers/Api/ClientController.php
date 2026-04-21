<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Services\PriceCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientController extends Controller
{
    /**
     * GET /api/client/products
     *
     * Productos paginados con precios de tarifa del usuario autenticado.
     * Misma lógica de filtros que el catálogo público, pero usa getClientPrice().
     */
    public function products(Request $request, PriceCalculationService $priceService): JsonResponse
    {
        $user = $request->user();
        $customerType = $user->customer?->tipoCliente;

        $query = Product::query()
            ->where('visible_web', true)
            ->whereHas('categoria', fn ($q) => $q->where('activo', true));

        // Filtro por categoría (incluye subcategorías)
        if ($request->filled('category_id')) {
            $categoryId = (int) $request->input('category_id');
            $categoryIds = $this->getCategoryAndDescendantIds($categoryId);
            $query->whereIn('categoria_id', $categoryIds);
        }

        // Filtro por subcategoría directa
        if ($request->filled('subcategory_id')) {
            $query->where('categoria_id', (int) $request->input('subcategory_id'));
        }

        // Filtro por marca
        if ($request->filled('brand_id')) {
            $query->where('marca_id', (int) $request->input('brand_id'));
        }

        // Filtro por proveedor
        if ($request->filled('supplier_id')) {
            $query->where('proveedor_principal_id', (int) $request->input('supplier_id'));
        }

        // Búsqueda por nombre y descripción
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('descripcion', 'like', "%{$search}%");
            });
        }

        $products = $query
            ->with([
                'categoria:id,nombre',
                'marca:id,nombre',
                'unidadBase:id,abreviatura',
            ])
            ->orderBy('nombre', 'asc')
            ->paginate(15, ['id', 'nombre', 'slug', 'categoria_id', 'marca_id', 'unidad_base_id']);

        $products->getCollection()->transform(function (Product $product) use ($priceService, $customerType) {
            $precio = $customerType
                ? $priceService->getClientPrice($product, $customerType)
                : $priceService->getPvpPrice($product);

            return [
                'id' => $product->id,
                'nombre' => $product->nombre,
                'slug' => $product->slug,
                'categoria' => $product->categoria?->nombre,
                'marca' => $product->marca?->nombre,
                'unidad' => $product->unidadBase?->abreviatura,
                'precio' => $precio,
            ];
        });

        return response()->json($products);
    }

    /**
     * GET /api/client/products/{id}/detail
     *
     * Detalle de producto con precios de tarifa del usuario autenticado.
     */
    public function productDetail(int $id, Request $request, PriceCalculationService $priceService): JsonResponse
    {
        $user = $request->user();
        $customerType = $user->customer?->tipoCliente;

        $product = Product::with([
            'categoria:id,nombre',
            'marca:id,nombre',
            'unidadBase:id,abreviatura',
            'especificaciones',
            'codigos',
        ])
            ->where('visible_web', true)
            ->find($id);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $precioBase = $customerType
            ? $priceService->getClientPrice($product, $customerType)
            : $priceService->getPvpPrice($product);

        $precio = $priceService->calculateIva($precioBase);

        $specs = $product->especificaciones;

        $descripcion = (!empty($product->descripcion_larga_web))
            ? $product->descripcion_larga_web
            : $product->descripcion;

        return response()->json([
            'id' => $product->id,
            'nombre' => $product->nombre,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'descripcion' => $descripcion,
            'categoria' => $product->categoria?->nombre,
            'marca' => $product->marca?->nombre,
            'unidad' => $product->unidadBase?->abreviatura,
            'specs' => $specs ? [
                'peso_kg' => $specs->peso_kg,
                'largo_cm' => $specs->largo_cm,
                'ancho_cm' => $specs->ancho_cm,
                'm2_por_unidad' => $specs->m2_por_unidad,
                'unidades_por_embalaje' => $specs->unidades_por_embalaje,
                'unidades_por_palet' => $specs->unidades_por_palet,
            ] : null,
            'codigos' => $product->codigos->map(fn ($c) => [
                'tipo' => $c->tipo,
                'codigo' => $c->codigo,
            ])->toArray(),
            'precio' => $precio,
            'imagen_principal_url' => $product->imagen_principal_url,
        ]);
    }

    /**
     * GET /api/client/presupuestos
     *
     * Historial de presupuestos del usuario autenticado, ordenados por fecha desc.
     */
    public function presupuestos(Request $request): JsonResponse
    {
        $quotes = Quote::where('user_id', $request->user()->id)
            ->with('product:id,nombre,slug')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $quotes->getCollection()->transform(fn (Quote $quote) => [
            'id' => $quote->id,
            'producto' => $quote->product?->nombre,
            'producto_slug' => $quote->product?->slug,
            'm2' => (float) $quote->m2,
            'merma_porcentaje' => (float) $quote->merma_porcentaje,
            'subtotal' => (float) $quote->subtotal,
            'total' => (float) $quote->total,
            'fecha' => $quote->created_at->toIso8601String(),
        ]);

        return response()->json($quotes);
    }

    /**
     * POST /api/client/presupuestos
     *
     * Guardar un nuevo presupuesto en el historial del usuario.
     */
    public function storePresupuesto(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'm2' => 'required|numeric|min:0',
            'merma_porcentaje' => 'required|numeric|min:0|max:100',
            'subtotal' => 'required|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'resultado_json' => 'required|array',
        ]);

        $quote = Quote::create([
            'user_id' => $request->user()->id,
            'product_id' => $validated['product_id'],
            'm2' => $validated['m2'],
            'merma_porcentaje' => $validated['merma_porcentaje'],
            'subtotal' => $validated['subtotal'],
            'total' => $validated['total'],
            'resultado_json' => $validated['resultado_json'],
        ]);

        return response()->json([
            'message' => 'Presupuesto guardado correctamente.',
            'id' => $quote->id,
        ], 201);
    }

    /**
     * GET /api/client/profile
     *
     * Datos del perfil del usuario autenticado.
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $user->load('customer.tipoCliente');

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'telefono' => $user->telefono,
            'empresa' => $user->empresa,
            'nif_cif' => $user->nif_cif,
            'tipo_tarifa' => $user->customer?->tipoCliente?->nombre,
        ]);
    }

    /**
     * PUT /api/client/profile
     *
     * Actualizar datos de contacto del usuario (nombre, teléfono, empresa).
     * No permite cambiar tipo_cliente ni nif_cif.
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'telefono' => 'sometimes|nullable|string|max:50',
            'empresa' => 'sometimes|nullable|string|max:255',
        ]);

        $request->user()->update($validated);

        return response()->json([
            'message' => 'Perfil actualizado correctamente.',
        ]);
    }

    /**
     * Obtiene los IDs de una categoría y todos sus descendientes recursivamente.
     */
    private function getCategoryAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        $childIds = Category::where('parent_id', $categoryId)->pluck('id')->toArray();

        foreach ($childIds as $childId) {
            $ids = array_merge($ids, $this->getCategoryAndDescendantIds($childId));
        }

        return $ids;
    }
}
