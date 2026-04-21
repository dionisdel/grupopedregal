<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\EmailService;
use App\Services\MaterialCalculatorService;
use App\Services\PdfGeneratorService;
use App\Services\PriceCalculationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CatalogController extends Controller
{
    /**
     * GET /api/categories/public
     *
     * Devuelve categorías nivel 1 activas, ordenadas por campo orden ascendente.
     */
    public function publicCategories(): JsonResponse
    {
        $categories = Category::where('nivel', 1)
            ->where('activo', true)
            ->orderBy('orden', 'asc')
            ->get(['id', 'nombre', 'slug', 'descripcion_web', 'imagen_url', 'orden']);

        return response()->json($categories);
    }

    /**
     * GET /api/products/catalog
     *
     * Devuelve productos paginados con filtros dinámicos.
     * Solo productos con visible_web=true y categoría activa.
     * Incluye: nombre, categoría (nombre), marca (nombre), unidad (abreviatura), precio PVP.
     */
    public function catalog(Request $request): JsonResponse
    {
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
                'precios' => fn ($q) => $q->where('activo', true)->orderBy('tipo_cliente_id', 'asc'),
            ])
            ->orderBy('nombre', 'asc')
            ->paginate(15, ['id', 'nombre', 'slug', 'categoria_id', 'marca_id', 'unidad_base_id']);

        $products->getCollection()->transform(function (Product $product) {
            $pvpPrice = $product->precios->first();

            return [
                'id' => $product->id,
                'nombre' => $product->nombre,
                'slug' => $product->slug,
                'categoria' => $product->categoria?->nombre,
                'marca' => $product->marca?->nombre,
                'unidad' => $product->unidadBase?->abreviatura,
                'precio_pvp' => $pvpPrice ? (float) $pvpPrice->precio_base : null,
            ];
        });

        return response()->json($products);
    }

    /**
     * GET /api/products/filters
     *
     * Devuelve opciones de filtro disponibles (subcategorías, marcas, proveedores)
     * para una categoría dada. Si no se proporciona category_id, devuelve todas
     * las opciones activas que tienen productos con visible_web=true.
     */
    public function filters(Request $request): JsonResponse
    {
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        // Base query: productos visibles en web con categoría activa
        $baseProductQuery = Product::query()
            ->where('visible_web', true)
            ->whereHas('categoria', fn ($q) => $q->where('activo', true));

        if ($categoryId) {
            $categoryIds = $this->getCategoryAndDescendantIds($categoryId);
            $baseProductQuery->whereIn('categoria_id', $categoryIds);
        }

        // Subcategorías: hijos directos de la categoría dada (o todas las categorías con productos visibles)
        if ($categoryId) {
            $subcategories = Category::where('parent_id', $categoryId)
                ->where('activo', true)
                ->whereHas('productos', fn ($q) => $q->where('visible_web', true))
                ->orderBy('orden', 'asc')
                ->get(['id', 'nombre']);
        } else {
            // Sin categoría: devolver categorías que tienen productos visibles y no son nivel 1
            $subcategories = Category::where('activo', true)
                ->where('nivel', '>', 1)
                ->whereHas('productos', fn ($q) => $q->where('visible_web', true))
                ->orderBy('orden', 'asc')
                ->get(['id', 'nombre']);
        }

        // Marcas que tienen productos visibles en la categoría
        $brandIds = (clone $baseProductQuery)->whereNotNull('marca_id')->pluck('marca_id')->unique();
        $brands = Brand::whereIn('id', $brandIds)
            ->where('activo', true)
            ->orderBy('nombre', 'asc')
            ->get(['id', 'nombre']);

        // Proveedores que tienen productos visibles en la categoría
        $supplierIds = (clone $baseProductQuery)->whereNotNull('proveedor_principal_id')->pluck('proveedor_principal_id')->unique();
        $suppliers = Supplier::whereIn('id', $supplierIds)
            ->where('activo', true)
            ->orderBy('nombre_comercial', 'asc')
            ->get(['id', 'nombre_comercial as nombre']);

        return response()->json([
            'subcategories' => $subcategories,
            'brands' => $brands,
            'suppliers' => $suppliers,
        ]);
    }

    /**
     * GET /api/products/{id}/detail
     *
     * Devuelve detalle completo de un producto: info básica, specs, códigos
     * alternativos y precio PVP con IVA desglosado.
     * Solo productos con visible_web=true. Devuelve 404 si no encontrado.
     */
    public function detail(int $id, PriceCalculationService $priceService): JsonResponse
    {
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

        $pvpPrice = $priceService->getPvpPrice($product);
        $precio = $priceService->calculateIva($pvpPrice);

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
     * POST /api/products/{id}/calculate
     *
     * Calcula el desglose de materiales para un producto dado m² y % merma.
     */
    public function calculateMaterials(int $id, Request $request, MaterialCalculatorService $calculatorService): JsonResponse
    {
        $validated = $request->validate([
            'm2' => 'required|numeric',
            'merma_porcentaje' => 'numeric',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $m2 = (float) $validated['m2'];
        $mermaPorcentaje = (float) ($validated['merma_porcentaje'] ?? 5.0);

        $result = $calculatorService->calculate($product, $m2, $mermaPorcentaje);

        return response()->json($result);
    }

    /**
     * POST /api/products/{id}/pdf
     *
     * Genera y descarga PDF del producto.
     * Si se proporciona m2, genera presupuesto con desglose de materiales.
     * Si no, genera ficha técnica del producto.
     */
    public function downloadPdf(
        int $id,
        Request $request,
        PdfGeneratorService $pdfService,
        MaterialCalculatorService $calculatorService,
    ): Response|JsonResponse {
        $validated = $request->validate([
            'm2' => 'nullable|numeric',
            'merma_porcentaje' => 'nullable|numeric',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $m2 = isset($validated['m2']) ? (float) $validated['m2'] : null;

        if ($m2 !== null) {
            $mermaPorcentaje = (float) ($validated['merma_porcentaje'] ?? 5.0);
            $calculatorResult = $calculatorService->calculate($product, $m2, $mermaPorcentaje);
            $pdfContent = $pdfService->generateQuote($product, $calculatorResult);
            $filename = 'presupuesto-' . ($product->slug ?? $product->id) . '.pdf';
        } else {
            $pdfContent = $pdfService->generateProductSheet($product);
            $filename = 'ficha-' . ($product->slug ?? $product->id) . '.pdf';
        }

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    /**
     * POST /api/products/{id}/send-email
     *
     * Genera PDF y lo envía por email a la dirección indicada.
     * Si se proporciona m2, genera presupuesto; si no, ficha técnica.
     */
    public function sendEmail(
        int $id,
        Request $request,
        PdfGeneratorService $pdfService,
        MaterialCalculatorService $calculatorService,
        EmailService $emailService,
    ): JsonResponse {
        $validated = $request->validate([
            'email' => 'required|email',
            'm2' => 'nullable|numeric',
            'merma_porcentaje' => 'nullable|numeric',
        ]);

        $product = Product::find($id);

        if (!$product) {
            return response()->json(['message' => 'Producto no encontrado.'], 404);
        }

        $m2 = isset($validated['m2']) ? (float) $validated['m2'] : null;

        if ($m2 !== null) {
            $mermaPorcentaje = (float) ($validated['merma_porcentaje'] ?? 5.0);
            $calculatorResult = $calculatorService->calculate($product, $m2, $mermaPorcentaje);
            $pdfContent = $pdfService->generateQuote($product, $calculatorResult);
        } else {
            $pdfContent = $pdfService->generateProductSheet($product);
        }

        $sent = $emailService->sendQuotePdf($validated['email'], $pdfContent, $product);

        if (!$sent) {
            return response()->json([
                'message' => 'No se pudo enviar el email. Inténtalo más tarde.',
            ], 500);
        }

        return response()->json([
            'message' => 'Email enviado correctamente.',
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
