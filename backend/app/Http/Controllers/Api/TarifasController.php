<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\CustomerType;
use App\Models\Product;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class TarifasController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::with(['categoria', 'marca', 'unidadBase', 'precios.tipoCliente'])
            ->whereHas('precios');

        // Filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->filled('categoria_id')) {
            $query->where('categoria_id', $request->categoria_id);
        }

        if ($request->filled('marca_id')) {
            $query->where('marca_id', $request->marca_id);
        }

        if ($request->filled('tipo_cliente_id')) {
            $query->whereHas('precios', function ($q) use ($request) {
                $q->where('tipo_cliente_id', $request->tipo_cliente_id);
            });
        }

        $products = $query->orderBy('nombre')->paginate(50);

        return response()->json($products);
    }

    public function export(Request $request)
    {
        $tipoClienteId = $request->input('tipo_cliente_id');
        $categoriaId   = $request->input('categoria_id');
        $marcaId       = $request->input('marca_id');
        $search        = $request->input('search');

        // ── Query ──────────────────────────────────────────────────────────
        $query = Product::with([
            'categoria', 'marca',
            'precios' => function ($q) use ($tipoClienteId) {
                if ($tipoClienteId) {
                    $q->where('tipo_cliente_id', $tipoClienteId)->where('activo', true);
                } else {
                    $q->where('activo', true);
                }
            },
        ]);

        if ($tipoClienteId) {
            $query->whereHas('precios', fn($q) => $q->where('tipo_cliente_id', $tipoClienteId));
        } else {
            $query->whereHas('precios');
        }

        if ($search) {
            $query->where(fn($q) => $q->where('nombre', 'like', "%{$search}%")->orWhere('sku', 'like', "%{$search}%"));
        }
        if ($categoriaId) $query->where('categoria_id', $categoriaId);
        if ($marcaId)     $query->where('marca_id', $marcaId);

        $products = $query->orderBy('categoria_id')->orderBy('nombre')->get();

        // ── Nombres ────────────────────────────────────────────────────────
        $tipoNombre = $tipoClienteId
            ? strtoupper(CustomerType::find($tipoClienteId)?->nombre ?? 'TODOS')
            : 'TODOS';

        $mesAnio  = strtoupper(now()->locale('es')->isoFormat('MMMM YYYY'));
        $filename = 'TARIFA_' . $tipoNombre . '_' . date('Y-m-d') . '.xlsx';
        $filepath = storage_path('app/' . $filename);

        // ── Spreadsheet ────────────────────────────────────────────────────
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Anchos de columna
        $sheet->getColumnDimension('A')->setWidth(50);
        $sheet->getColumnDimension('B')->setWidth(5);
        $sheet->getColumnDimension('C')->setWidth(5);
        $sheet->getColumnDimension('D')->setWidth(14);

        $row = 1;

        // ── Fila 1: encabezado ─────────────────────────────────────────────
        $sheet->setCellValue('A1', 'TARIFA ' . $mesAnio);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(11);

        $sheet->setCellValue('D1', 'CLIENTES ' . $tipoNombre);
        $sheet->getStyle('D1')->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('D1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Fila 2: vacía
        $row = 3;

        // ── Categorías ─────────────────────────────────────────────────────
        $productsByCategory = $products->groupBy('categoria_id');

        foreach ($productsByCategory as $categoryProducts) {
            $category  = $categoryProducts->first()->categoria;
            $catNombre = strtoupper($category->nombre ?? 'SIN CATEGORÍA');

            // Fila vacía antes del bloque
            $row++;

            // Fila título de categoría
            $sheet->setCellValue('A' . $row, $catNombre);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(11);
            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('A' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F2F2F2');

            // Aplicar fondo gris también a B y C para que se vea continuo
            foreach (['B', 'C'] as $col) {
                $sheet->getStyle($col . $row)->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('F2F2F2');
            }

            $sheet->setCellValue('D' . $row, 'PRECIO NETO');
            $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setSize(10);
            $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle('D' . $row)->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setRGB('F2F2F2');

            // Fila vacía después del título
            $row++;
            $row++;

            // Productos
            foreach ($categoryProducts as $product) {
                $precio     = $product->precios->first();
                $precioNeto = $precio ? (float) $precio->precio_neto : 0.0;
                $precioStr  = number_format($precioNeto, 2, ',', '.') . ' €';

                $sheet->setCellValue('A' . $row, $product->nombre);
                $sheet->getStyle('A' . $row)->getFont()->setSize(10);

                $sheet->setCellValue('D' . $row, $precioStr);
                $sheet->getStyle('D' . $row)->getFont()->setBold(true)->setSize(10);
                $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

                $row++;
            }

            // Fila vacía al final del bloque
            $row++;
        }

        // ── Footer ─────────────────────────────────────────────────────────
        $row++;
        $sheet->setCellValue('A' . $row, 'DEPARTAMENTO COMERCIAL  624 27 96 14');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(10);

        // ── Guardar y descargar ────────────────────────────────────────────
        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return response()->download($filepath, $filename)->deleteFileAfterSend(true);
    }

    public function customerTypes()
    {
        $types = CustomerType::orderBy('nombre')->get();
        return response()->json($types);
    }

    public function categories()
    {
        $categories = Category::orderBy('nombre')->get();
        return response()->json($categories);
    }

    public function brands()
    {
        $brands = \App\Models\Brand::orderBy('nombre')->get();
        return response()->json($brands);
    }
}
