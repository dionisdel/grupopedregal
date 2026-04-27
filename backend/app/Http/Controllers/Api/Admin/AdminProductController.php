<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\ExcelImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminProductController extends Controller
{
    /**
     * Paginated product list with search and category filter.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['marca', 'proveedor', 'categoria']);

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('descripcion', 'like', "%{$search}%")
                  ->orWhere('codigo_articulo', 'like', "%{$search}%");
            });
        }

        if ($categoryId = $request->query('category_id')) {
            $query->where('categoria_id', $categoryId);
        }

        $perPage = (int) $request->query('per_page', 20);

        $products = $query->orderBy('descripcion')->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Create a new product. Calculated fields are auto-set by model boot event.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate($this->validationRules());

        $product = Product::create($validated);
        $product->load(['marca', 'proveedor', 'categoria']);

        return response()->json($product, 201);
    }

    /**
     * Update an existing product. Calculated fields are auto-set by model boot event.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate($this->validationRules());

        $product->update($validated);
        $product->load(['marca', 'proveedor', 'categoria']);

        return response()->json($product);
    }

    /**
     * Soft-delete a product.
     */
    public function destroy(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(null, 204);
    }

    /**
     * Import products from an uploaded Excel file.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $service = new ExcelImportService();
        $result = $service->import($path);

        return response()->json($result);
    }

    /**
     * Get price history for a product, ordered by changed_at desc.
     */
    public function priceHistory(int $id): JsonResponse
    {
        $product = Product::findOrFail($id);

        $history = $product->priceHistory()
            ->with('changedBy:id,name,email')
            ->orderByDesc('changed_at')
            ->get();

        return response()->json($history);
    }

    /**
     * Validation rules for product create/update.
     */
    private function validationRules(): array
    {
        return [
            'codigo_articulo'           => 'sometimes|required|string|max:40',
            'descripcion'               => 'sometimes|required|string|max:500',
            'slug'                      => 'sometimes|required|string|max:500',
            'categoria_id'              => 'sometimes|required|exists:categories,id',
            'proveedor_id'              => 'nullable|exists:suppliers,id',
            'codigo_proveedor'          => 'nullable|string|max:40',
            'codigo_articulo_proveedor' => 'nullable|string|max:40',
            'marca_id'                  => 'nullable|exists:brands,id',
            'kg_litro'                  => 'nullable|numeric',
            'largo'                     => 'nullable|numeric',
            'ancho'                     => 'nullable|numeric',
            'metros_articulo'           => 'nullable|numeric',
            'unidades_por_articulo'     => 'nullable|integer',
            'articulos_por_embalaje'    => 'nullable|integer',
            'unidades_palet'            => 'nullable|integer',
            'palet_retornable'          => 'nullable|boolean',
            'pvp_proveedor'             => 'nullable|numeric|min:0',
            'desc_prov_1'               => 'nullable|numeric|min:0|max:100',
            'coste_transporte'          => 'nullable|numeric|min:0',
            'desc_camion_vip'           => 'nullable|numeric|min:0|max:100',
            'desc_camion'               => 'nullable|numeric|min:0|max:100',
            'desc_oferta'               => 'nullable|numeric|min:0|max:100',
            'desc_vip'                  => 'nullable|numeric|min:0|max:100',
            'desc_empresas'             => 'nullable|numeric|min:0|max:100',
            'desc_empresas_a'           => 'nullable|numeric|min:0|max:100',
            'iva_porcentaje'            => 'nullable|numeric|min:0|max:100',
            'filtros_dinamicos'         => 'nullable|array',
            'imagen_url'                => 'nullable|string|max:500',
            'estado_publicado'          => 'nullable|boolean',
        ];
    }

    /**
     * Export all products as Excel file with the same column structure as the import.
     */
    public function export()
    {
        $products = Product::with(['categoria.parent', 'proveedor', 'marca'])
            ->orderBy('codigo_articulo')
            ->get();

        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Headers matching the client's Excel structure exactly
        $headers = [
            'A1' => 'COD_ARTICULO',
            'B1' => '', 'C1' => '', 'D1' => '', 'E1' => '', 'F1' => '', 'G1' => '', 'H1' => '',
            'I1' => 'DESCRIPCION DEL ARTICULO',
            'J1' => 'CODIGO PROVEEDOR',
            'K1' => 'PROVEEDOR',
            'L1' => 'CODIGO ARTICULO PROVEEDOR',
            'M1' => 'MARCA',
            'N1' => 'KG/LITRO',
            'O1' => 'LARGO',
            'P1' => 'ANCHO',
            'Q1' => 'METROS ARTICULO',
            'R1' => 'UNIDADES POR ARTICULO',
            'S1' => 'ARTICULOS POR EMBALAJE',
            'T1' => 'UNIDADES/PALET',
            'U1' => 'PALET RETORNABLE',
            'V1' => 'COD. FAMILIA',
            'W1' => 'FAMILIA',
            'X1' => 'COD. SUBFAMILIA 2',
            'Y1' => 'SUBFAMILIA 2',
            'Z1' => 'PVP PROVEEDOR',
            'AA1' => 'DESC PROV 1',
            'AB1' => 'COSTE NETO M2',
            'AC1' => 'COSTE TRANSP.',
            'AD1' => 'COSTE M2+TRANS',
            'AE1' => 'COSTE NETO',
            'AF1' => 'PRE PVP',
            'AG1' => '',
            'AH1' => 'PVP',
            'AI1' => '', 'AJ1' => '',
            'AK1' => 'NETO GRUPO',
            'AL1' => 'DESC. CAMION VIP',
            'AM1' => 'NETO CAMION VIP',
            'AN1' => 'DESC. CAMION',
            'AO1' => 'NETO CAMION',
            'AP1' => 'DESC. OFERTA',
            'AQ1' => 'NETO OFERTA',
            'AR1' => 'DESC. VIP',
            'AS1' => 'NETO VIP',
            'AT1' => 'DESC EMPRESAS',
            'AU1' => 'NETO EMPRESAS',
            'AV1' => 'DESC EMPRESAS A',
            'AW1' => 'NETO EMPRESAS A',
            'AX1' => 'IVA',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        // Number formats
        $eurFmt = '#,##0.00\ "€"';
        $pctFmt = '0.00"%"';
        $lastRow = count($products) + 1;

        // Apply € format to money columns
        $eurCols = ['Z', 'AB', 'AC', 'AD', 'AE', 'AF', 'AH', 'AK', 'AM', 'AO', 'AQ', 'AS', 'AU', 'AW'];
        foreach ($eurCols as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode($eurFmt);
        }
        // Apply % format to percentage columns
        $pctCols = ['AA', 'AL', 'AN', 'AP', 'AR', 'AT', 'AV', 'AX'];
        foreach ($pctCols as $col) {
            $sheet->getStyle("{$col}2:{$col}{$lastRow}")->getNumberFormat()->setFormatCode($pctFmt);
        }

        $row = 2;
        foreach ($products as $p) {
            $familia = $p->categoria?->parent ?? $p->categoria;
            $subfamilia = $p->categoria?->parent ? $p->categoria : null;

            $sheet->setCellValue("A{$row}", $p->codigo_articulo);
            $sheet->setCellValue("I{$row}", $p->descripcion);
            $sheet->setCellValue("J{$row}", $p->codigo_proveedor);
            $sheet->setCellValue("K{$row}", $p->proveedor?->nombre_comercial);
            $sheet->setCellValue("L{$row}", $p->codigo_articulo_proveedor);
            $sheet->setCellValue("M{$row}", $p->marca?->nombre);
            $sheet->setCellValue("N{$row}", $p->kg_litro);
            $sheet->setCellValue("O{$row}", $p->largo);
            $sheet->setCellValue("P{$row}", $p->ancho);
            $sheet->setCellValue("Q{$row}", $p->metros_articulo);
            $sheet->setCellValue("R{$row}", $p->unidades_por_articulo);
            $sheet->setCellValue("S{$row}", $p->articulos_por_embalaje);
            $sheet->setCellValue("T{$row}", $p->unidades_palet);
            $sheet->setCellValue("U{$row}", $p->palet_retornable ? 'SI' : 'NO');
            $sheet->setCellValue("V{$row}", $familia?->slug);
            $sheet->setCellValue("W{$row}", $familia?->nombre);
            $sheet->setCellValue("X{$row}", $subfamilia?->slug);
            $sheet->setCellValue("Y{$row}", $subfamilia?->nombre);
            $sheet->setCellValue("Z{$row}", $p->pvp_proveedor);       // € PVP Proveedor
            $sheet->setCellValue("AA{$row}", $p->desc_prov_1);         // % Desc Prov 1
            $sheet->setCellValue("AB{$row}", $p->coste_neto_m2);       // € Coste Neto M2
            $sheet->setCellValue("AC{$row}", $p->coste_transporte);    // € Coste Transp
            $sheet->setCellValue("AD{$row}", $p->coste_m2_trans);      // € Coste M2+Trans
            $sheet->setCellValue("AE{$row}", $p->coste_neto);          // € Coste Neto
            $sheet->setCellValue("AF{$row}", $p->pre_pvp);             // € Pre PVP
            $sheet->setCellValue("AH{$row}", $p->pvp);                 // € PVP
            $sheet->setCellValue("AL{$row}", $p->desc_camion_vip);     // % Desc Camión VIP
            $sheet->setCellValue("AM{$row}", $p->neto_camion_vip);     // € Neto Camión VIP
            $sheet->setCellValue("AN{$row}", $p->desc_camion);         // % Desc Camión
            $sheet->setCellValue("AO{$row}", $p->neto_camion);         // € Neto Camión
            $sheet->setCellValue("AP{$row}", $p->desc_oferta);         // % Desc Oferta
            $sheet->setCellValue("AQ{$row}", $p->neto_oferta);         // € Neto Oferta
            $sheet->setCellValue("AR{$row}", $p->desc_vip);            // % Desc VIP
            $sheet->setCellValue("AS{$row}", $p->neto_vip);            // € Neto VIP
            $sheet->setCellValue("AT{$row}", $p->desc_empresas);       // % Desc Empresas
            $sheet->setCellValue("AU{$row}", $p->neto_empresas);       // € Neto Empresas
            $sheet->setCellValue("AV{$row}", $p->desc_empresas_a);     // % Desc Empresas A
            $sheet->setCellValue("AW{$row}", $p->neto_empresas_a);     // € Neto Empresas A
            $sheet->setCellValue("AX{$row}", $p->iva_porcentaje);      // % IVA
            $row++;
        }

        $filename = date('Ymd') . '-productos.xlsx';
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $temp = tempnam(sys_get_temp_dir(), 'export');
        $writer->save($temp);

        return response()->download($temp, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
