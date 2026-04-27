<?php

namespace App\Services;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Service for importing products from the Grupo Pedregal Excel tariff file.
 *
 * Column mapping based on actual Excel structure:
 * A(1): COD_ARTICULO (no header)
 * I(9): DESCRIPCION DEL ARTICULO
 * J(10): CODIGO PROVEEDOR
 * K(11): PROVEEDOR
 * L(12): CODIGO ARTICULO PROVEEDOR
 * M(13): MARCA
 * N(14): KG/LITRO
 * O(15): LARGO
 * P(16): ANCHO
 * Q(17): METROS ARTICULO
 * R(18): UNIDADES POR ARTICULO
 * S(19): ARTICULOS POR EMBALAJE
 * T(20): UNIDADES/PALET
 * U(21): PALET RETORNABLE
 * V(22): COD. FAMILIA
 * W(23): FAMILIA
 * X(24): COD. SUBFAMILIA 2
 * Y(25): SUBFAMILIA 2
 * Z(26): PVP PROVEEDOR
 * AA(27): DESC PROV 1
 * AC(29): COSTE TRANSP.
 * AL(38): DESC. CAMION VIP
 * AN(40): DESC. CAMION
 * AP(42): DESC. OFERTA
 * AR(44): DESC. VIP
 * AT(46): DESC EMPRESAS
 * AV(48): DESC EMPRESAS A
 * AX(50): IVA
 */
class ExcelImportService
{
    private array $familiaCache = [];
    private array $subfamiliaCache = [];
    private array $supplierCache = [];
    private array $brandCache = [];

    public function import(string $filePath): array
    {
        $rows = $this->readExcelRows($filePath);
        return $this->importFromRows($rows);
    }

    public function importFromRows(array $rows): array
    {
        $created = 0;
        $updated = 0;
        $errors = [];

        $this->warmCaches();

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            try {
                $codigoArticulo = trim((string) ($row['COD_ARTICULO'] ?? ''));
                if ($codigoArticulo === '') {
                    $errors[] = ['row' => $rowNumber, 'message' => 'COD_ARTICULO vacío'];
                    continue;
                }

                $descripcion = trim((string) ($row['DESCRIPCION'] ?? ''));
                if ($descripcion === '') {
                    $errors[] = ['row' => $rowNumber, 'message' => 'DESCRIPCION vacía'];
                    continue;
                }
                // Truncate description to 500 chars max
                $descripcion = mb_substr($descripcion, 0, 500);

                $categoriaId = $this->resolveCategory($row);
                // If no category found, assign to a default "Sin clasificar" category
                if ($categoriaId === null) {
                    $categoriaId = $this->getDefaultCategoryId();
                }
                $proveedorId = $this->resolveSupplier($row);
                $marcaId = $this->resolveBrand($row);

                $productData = [
                    'codigo_articulo'           => $codigoArticulo,
                    'descripcion'               => $descripcion,
                    'slug'                      => mb_substr(Str::slug($descripcion . '-' . $codigoArticulo), 0, 500),
                    'categoria_id'              => $categoriaId,
                    'proveedor_id'              => $proveedorId,
                    'codigo_proveedor'          => trim((string) ($row['COD_PROVEEDOR'] ?? '')) ?: null,
                    'codigo_articulo_proveedor' => trim((string) ($row['COD_ARTICULO_PROVEEDOR'] ?? '')) ?: null,
                    'marca_id'                  => $marcaId,
                    'kg_litro'                  => $this->parseFloat($row['KG_LITRO'] ?? null),
                    'largo'                     => $this->parseFloat($row['LARGO'] ?? null),
                    'ancho'                     => $this->parseFloat($row['ANCHO'] ?? null),
                    'metros_articulo'           => $this->parseFloat($row['METROS_ARTICULO'] ?? null),
                    'unidades_por_articulo'     => $this->parseInt($row['UNIDADES_POR_ARTICULO'] ?? null),
                    'articulos_por_embalaje'    => $this->parseInt($row['ARTICULOS_POR_EMBALAJE'] ?? null),
                    'unidades_palet'            => $this->parseInt($row['UNIDADES_PALET'] ?? null),
                    'palet_retornable'          => $this->parseBool($row['PALET_RETORNABLE'] ?? null),
                    'pvp_proveedor'             => $this->parseFloat($row['PVP_PROVEEDOR'] ?? null) ?? 0,
                    'desc_prov_1'               => $this->parseFloat($row['DESC_PROV_1'] ?? null) ?? 0,
                    'coste_transporte'          => $this->parseFloat($row['COSTE_TRANSPORTE'] ?? null) ?? 0,
                    'iva_porcentaje'            => $this->parseFloat($row['IVA'] ?? null) ?? 21,
                    'desc_camion_vip'           => $this->parseFloat($row['DESC_CAMION_VIP'] ?? null) ?? 0,
                    'desc_camion'               => $this->parseFloat($row['DESC_CAMION'] ?? null) ?? 0,
                    'desc_oferta'               => $this->parseFloat($row['DESC_OFERTA'] ?? null) ?? 0,
                    'desc_vip'                  => $this->parseFloat($row['DESC_VIP'] ?? null) ?? 0,
                    'desc_empresas'             => $this->parseFloat($row['DESC_EMPRESAS'] ?? null) ?? 0,
                    'desc_empresas_a'           => $this->parseFloat($row['DESC_EMPRESAS_A'] ?? null) ?? 0,
                    'estado_publicado'          => true,
                ];

                $existing = Product::where('codigo_articulo', $codigoArticulo)->first();

                if ($existing) {
                    $existing->update($productData);
                    $updated++;
                } else {
                    Product::create($productData);
                    $created++;
                }
            } catch (\Throwable $e) {
                $errors[] = ['row' => $rowNumber, 'message' => $e->getMessage()];
            }
        }

        return compact('created', 'updated', 'errors');
    }

    private function readExcelRows(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $rows = [];

        // Fixed positional mapping based on actual Excel structure
        for ($rowIdx = 2; $rowIdx <= $highestRow; $rowIdx++) {
            $codArticulo = trim((string) $sheet->getCell('A' . $rowIdx)->getValue());

            // Skip empty rows
            if ($codArticulo === '') {
                continue;
            }

            $rows[] = [
                'COD_ARTICULO'              => $codArticulo,
                'DESCRIPCION'               => trim((string) $sheet->getCell('I' . $rowIdx)->getValue()),
                'COD_PROVEEDOR'             => trim((string) $sheet->getCell('J' . $rowIdx)->getValue()),
                'PROVEEDOR'                 => trim((string) $sheet->getCell('K' . $rowIdx)->getValue()),
                'COD_ARTICULO_PROVEEDOR'    => trim((string) $sheet->getCell('L' . $rowIdx)->getValue()),
                'MARCA'                     => trim((string) $sheet->getCell('M' . $rowIdx)->getValue()),
                'KG_LITRO'                  => $sheet->getCell('N' . $rowIdx)->getValue(),
                'LARGO'                     => $sheet->getCell('O' . $rowIdx)->getValue(),
                'ANCHO'                     => $sheet->getCell('P' . $rowIdx)->getValue(),
                'METROS_ARTICULO'           => $sheet->getCell('Q' . $rowIdx)->getValue(),
                'UNIDADES_POR_ARTICULO'     => $sheet->getCell('R' . $rowIdx)->getValue(),
                'ARTICULOS_POR_EMBALAJE'    => $sheet->getCell('S' . $rowIdx)->getValue(),
                'UNIDADES_PALET'            => $sheet->getCell('T' . $rowIdx)->getValue(),
                'PALET_RETORNABLE'          => $sheet->getCell('U' . $rowIdx)->getValue(),
                'COD_FAMILIA'               => trim((string) $sheet->getCell('V' . $rowIdx)->getValue()),
                'FAMILIA'                   => trim((string) $sheet->getCell('W' . $rowIdx)->getValue()),
                'COD_SUBFAMILIA'            => trim((string) $sheet->getCell('X' . $rowIdx)->getValue()),
                'SUBFAMILIA'                => trim((string) $sheet->getCell('Y' . $rowIdx)->getValue()),
                'PVP_PROVEEDOR'             => $sheet->getCell('Z' . $rowIdx)->getValue(),
                'DESC_PROV_1'               => $sheet->getCell('AA' . $rowIdx)->getValue(),
                'COSTE_TRANSPORTE'          => $sheet->getCell('AC' . $rowIdx)->getValue(),
                'DESC_CAMION_VIP'           => $sheet->getCell('AL' . $rowIdx)->getValue(),
                'DESC_CAMION'               => $sheet->getCell('AN' . $rowIdx)->getValue(),
                'DESC_OFERTA'               => $sheet->getCell('AP' . $rowIdx)->getValue(),
                'DESC_VIP'                  => $sheet->getCell('AR' . $rowIdx)->getValue(),
                'DESC_EMPRESAS'             => $sheet->getCell('AT' . $rowIdx)->getValue(),
                'DESC_EMPRESAS_A'           => $sheet->getCell('AV' . $rowIdx)->getValue(),
                'IVA'                       => $sheet->getCell('AX' . $rowIdx)->getValue(),
            ];
        }

        return $rows;
    }

    private function warmCaches(): void
    {
        foreach (Category::whereNull('parent_id')->get() as $cat) {
            $this->familiaCache[mb_strtolower($cat->nombre)] = $cat;
        }
        foreach (Category::whereNotNull('parent_id')->get() as $cat) {
            $key = $cat->parent_id . ':' . mb_strtolower($cat->nombre);
            $this->subfamiliaCache[$key] = $cat;
        }
        foreach (Supplier::all() as $supplier) {
            $this->supplierCache[$supplier->codigo] = $supplier;
        }
        foreach (Brand::all() as $brand) {
            $this->brandCache[mb_strtolower($brand->nombre)] = $brand;
        }
    }

    private function resolveCategory(array $row): ?int
    {
        $familiaNombre = trim((string) ($row['FAMILIA'] ?? ''));
        if ($familiaNombre === '') {
            return null;
        }

        $familiaKey = mb_strtolower($familiaNombre);

        if (!isset($this->familiaCache[$familiaKey])) {
            $familia = Category::firstOrCreate(
                ['parent_id' => null, 'slug' => Str::slug($familiaNombre)],
                ['nombre' => $familiaNombre, 'slug' => Str::slug($familiaNombre), 'orden' => 0, 'activo' => true]
            );
            $this->familiaCache[$familiaKey] = $familia;
        }

        $familia = $this->familiaCache[$familiaKey];

        $subfamiliaNombre = trim((string) ($row['SUBFAMILIA'] ?? ''));
        if ($subfamiliaNombre === '') {
            return $familia->id;
        }

        $subfamiliaKey = $familia->id . ':' . mb_strtolower($subfamiliaNombre);

        if (!isset($this->subfamiliaCache[$subfamiliaKey])) {
            $subfamilia = Category::firstOrCreate(
                ['parent_id' => $familia->id, 'slug' => Str::slug($subfamiliaNombre)],
                ['nombre' => $subfamiliaNombre, 'slug' => Str::slug($subfamiliaNombre), 'parent_id' => $familia->id, 'orden' => 0, 'activo' => true]
            );
            $this->subfamiliaCache[$subfamiliaKey] = $subfamilia;
        }

        return $this->subfamiliaCache[$subfamiliaKey]->id;
    }

    private function resolveSupplier(array $row): ?int
    {
        $codigo = trim((string) ($row['COD_PROVEEDOR'] ?? ''));
        if ($codigo === '') {
            return null;
        }

        if (!isset($this->supplierCache[$codigo])) {
            $nombre = trim((string) ($row['PROVEEDOR'] ?? '')) ?: $codigo;
            $supplier = Supplier::firstOrCreate(
                ['codigo' => $codigo],
                ['nombre_comercial' => $nombre, 'activo' => true]
            );
            $this->supplierCache[$codigo] = $supplier;
        }

        return $this->supplierCache[$codigo]->id;
    }

    private function resolveBrand(array $row): ?int
    {
        $nombre = trim((string) ($row['MARCA'] ?? ''));
        if ($nombre === '') {
            return null;
        }

        $key = mb_strtolower($nombre);

        if (!isset($this->brandCache[$key])) {
            $brand = Brand::firstOrCreate(
                ['nombre' => $nombre],
                ['activo' => true]
            );
            $this->brandCache[$key] = $brand;
        }

        return $this->brandCache[$key]->id;
    }

    private function parseFloat(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $cleaned = str_replace(',', '.', trim((string) $value));
        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    private function parseInt(mixed $value): ?int
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }
        $cleaned = trim((string) $value);
        return is_numeric($cleaned) ? (int) $cleaned : null;
    }

    private function parseBool(mixed $value): bool
    {
        if ($value === null) {
            return false;
        }
        $str = mb_strtolower(trim((string) $value));
        return in_array($str, ['si', 'sí', '1', 'true', 'yes'], true);
    }

    private function getDefaultCategoryId(): int
    {
        static $defaultId = null;
        if ($defaultId === null) {
            $cat = Category::firstOrCreate(
                ['parent_id' => null, 'slug' => 'sin-clasificar'],
                ['nombre' => 'Sin clasificar', 'slug' => 'sin-clasificar', 'orden' => 999, 'activo' => true]
            );
            $defaultId = $cat->id;
        }
        return $defaultId;
    }
}
