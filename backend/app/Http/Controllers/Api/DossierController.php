<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class DossierController extends Controller
{
    /**
     * Genera un dossier PDF de tarifas para un tipo de cliente.
     */
    public function generate(Request $request)
    {
        $tipoClienteId  = $request->input('tipo_cliente_id');
        $tipoClienteId2 = $request->input('tipo_cliente_id_2'); // para dossier multi-precio

        if (!$tipoClienteId) {
            return response()->json(['error' => 'Debe seleccionar un tipo de cliente'], 422);
        }

        $tipoCliente = CustomerType::find($tipoClienteId);
        if (!$tipoCliente) {
            return response()->json(['error' => 'Tipo de cliente no encontrado'], 404);
        }

        $multiPrice   = (bool) $tipoClienteId2;
        $tipoCliente2 = $multiPrice ? CustomerType::find($tipoClienteId2) : null;

        // ── Obtener productos con precios ──────────────────────────────
        $query = Product::with(['categoria.parent', 'precios' => function ($q) use ($tipoClienteId, $tipoClienteId2) {
            $q->where('activo', true)
              ->whereIn('tipo_cliente_id', array_filter([$tipoClienteId, $tipoClienteId2]));
        }])
        ->whereHas('precios', fn($q) => $q->where('tipo_cliente_id', $tipoClienteId)->where('activo', true))
        ->where('activo', true)
        ->orderBy('categoria_id')
        ->orderBy('nombre')
        ->get();

        // ── Agrupar por categoría padre (familia) ──────────────────────
        $familias = [];
        foreach ($query as $product) {
            $cat = $product->categoria;
            // Determinar familia (categoría padre) y subcategoría
            $familiaNombre = 'SIN CATEGORÍA';
            $subcatNombre  = null;

            if ($cat) {
                if ($cat->parent_id && $cat->parent) {
                    $familiaNombre = strtoupper($cat->parent->nombre);
                    $subcatNombre  = strtoupper($cat->nombre);
                } else {
                    $familiaNombre = strtoupper(trim($cat->nombre)) ?: 'OTROS';
                }
            }

            $precio1 = $product->precios->firstWhere('tipo_cliente_id', $tipoClienteId);
            $precio2 = $multiPrice ? $product->precios->firstWhere('tipo_cliente_id', $tipoClienteId2) : null;

            $prodData = [
                'nombre'  => $product->nombre,
                'precio'  => $precio1 ? number_format((float)$precio1->precio_neto, 2, '.', '') . ' €' : '—',
                'precio1' => $precio1 ? number_format((float)$precio1->precio_neto, 2, '.', '') . ' €' : '—',
                'precio2' => $precio2 ? number_format((float)$precio2->precio_neto, 2, '.', '') . ' €' : '—',
            ];

            if (!isset($familias[$familiaNombre])) {
                $familias[$familiaNombre] = [];
            }
            $subKey = $subcatNombre ?? '__main__';
            if (!isset($familias[$familiaNombre][$subKey])) {
                $familias[$familiaNombre][$subKey] = [];
            }
            $familias[$familiaNombre][$subKey][] = $prodData;
        }

        // ── Paginar: ~35 líneas por página ─────────────────────────────
        $maxLines = 35;
        $pages    = [];
        $currentPage = ['sections' => []];
        $lineCount   = 0;

        foreach ($familias as $famNombre => $subcats) {
            foreach ($subcats as $subKey => $prods) {
                $chunks = array_chunk($prods, $maxLines - 3); // reservar espacio para headers/footer

                foreach ($chunks as $chunkIdx => $chunk) {
                    $linesNeeded = count($chunk) + ($chunkIdx === 0 ? 1 : 1); // +1 for cat header
                    if ($subKey !== '__main__' && $chunkIdx === 0) $linesNeeded++;

                    // Si no cabe, nueva página
                    if ($lineCount > 0 && ($lineCount + $linesNeeded) > $maxLines) {
                        $pages[] = $currentPage;
                        $currentPage = ['sections' => []];
                        $lineCount = 0;
                    }

                    $catLabel = $famNombre;
                    if ($chunkIdx > 0 || (count($currentPage['sections']) > 0 && end($currentPage['sections'])['category'] === $famNombre)) {
                        $catLabel = $famNombre . ' (cont.)';
                    }

                    $section = [
                        'category'      => $chunkIdx === 0 ? $famNombre : $famNombre . ' (cont.)',
                        'subcategories' => [[
                            'name'     => ($subKey === '__main__' || $chunkIdx > 0) ? null : $subKey,
                            'products' => $chunk,
                        ]],
                    ];

                    $currentPage['sections'][] = $section;
                    $lineCount += $linesNeeded;

                    if ($lineCount >= $maxLines) {
                        $pages[] = $currentPage;
                        $currentPage = ['sections' => []];
                        $lineCount = 0;
                    }
                }
            }
        }

        // Última página
        if (!empty($currentPage['sections'])) {
            $pages[] = $currentPage;
        }

        // ── Labels ─────────────────────────────────────────────────────
        $mesAnio = strtoupper(now()->locale('es')->isoFormat('MMMM YYYY'));

        $tipoClienteLabel = 'CLIENTES ' . strtoupper($tipoCliente->nombre);
        $priceLabels = [];
        if ($multiPrice && $tipoCliente2) {
            $tipoClienteLabel = 'TARIFA ' . strtoupper($tipoCliente->nombre);
            $priceLabels = [
                strtoupper($tipoCliente->nombre),
                strtoupper($tipoCliente2->nombre),
            ];
        }

        // ── Generar PDF ────────────────────────────────────────────────
        $pdf = Pdf::loadView('dossier.tarifa', [
            'pages'             => $pages,
            'mesAnio'           => $mesAnio,
            'tipoClienteLabel'  => $tipoClienteLabel,
            'multiPrice'        => $multiPrice,
            'priceLabels'       => $priceLabels,
        ]);

        $pdf->setPaper('a4', 'portrait');

        $filename = 'TARIFA_' . strtoupper($tipoCliente->nombre) . '_' . strtoupper(now()->locale('es')->isoFormat('MMMM_YYYY')) . '.pdf';

        return $pdf->download($filename);
    }
}
