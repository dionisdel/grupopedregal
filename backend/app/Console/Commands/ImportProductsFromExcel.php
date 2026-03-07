<?php

namespace App\Console\Commands;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductCost;
use App\Models\ProductSpec;
use App\Models\ProductCode;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader;

class ImportProductsFromExcel extends Command
{
    protected $signature = 'import:products {file}';
    protected $description = 'Importar productos desde archivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: $filePath");
            return 1;
        }

        $this->info("Importando productos desde: $filePath");
        
        $reader = new Reader();
        $reader->open($filePath);
        
        $imported = 0;
        $errors = 0;
        
        foreach ($reader->getSheetIterator() as $sheet) {
            $this->info("Procesando hoja: {$sheet->getName()}");
            
            $rowIndex = 0;
            $headers = [];
            
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->getCells();
                $rowData = array_map(fn($cell) => $cell->getValue(), $cells);
                
                if ($rowIndex === 1) {
                    $headers = $rowData;
                    continue;
                }
                
                try {
                    $this->importProduct($headers, $rowData);
                    $imported++;
                    $this->info("✓ Fila $rowIndex importada");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Error en fila $rowIndex: " . $e->getMessage());
                }
            }
        }
        
        $reader->close();
        
        $this->info("\n✅ Importación completada:");
        $this->info("  - Productos importados: $imported");
        $this->info("  - Errores: $errors");
        
        return 0;
    }


    private function importProduct(array $headers, array $rowData): void
    {
        // Asegurar que ambos arrays tengan la misma longitud
        $headerCount = count($headers);
        $dataCount = count($rowData);
        
        if ($dataCount < $headerCount) {
            $rowData = array_pad($rowData, $headerCount, null);
        } elseif ($dataCount > $headerCount) {
            $rowData = array_slice($rowData, 0, $headerCount);
        }
        
        $data = array_combine($headers, $rowData);
        
        // Extraer datos
        $nombre = trim($data['DESCRIPCION DEL ARTICULO'] ?? '');
        
        // Saltar filas con nombres inválidos
        if (empty($nombre) || $nombre === '=' || strlen($nombre) < 2) {
            throw new \Exception("Nombre de producto vacío o inválido");
        }
        
        // Buscar o crear marca
        $marcaNombre = $data['MARCA '] ?? 'Sin Marca';
        $marca = Brand::firstOrCreate(
            ['nombre' => trim($marcaNombre)],
            ['activo' => true]
        );
        
        // Buscar o crear categoría
        $codigoCategoria = $data['COD. SUBFAMILIA 1'] ?? $data['COD. FAMILIA'] ?? '1';
        $nombreCategoria = trim($data['SUBFAMILIA 2'] ?? $data['FAMILIA'] ?? 'Sin Categoría');
        
        // Generar slug único para categoría
        $slugCategoria = Str::slug($nombreCategoria);
        $categoria = Category::where('codigo', $codigoCategoria)->first();
        
        if (!$categoria) {
            $categoria = Category::create([
                'codigo' => $codigoCategoria,
                'nombre' => $nombreCategoria,
                'slug' => $slugCategoria,
                'nivel' => 2,
                'activo' => true,
            ]);
        }
        
        // Buscar o crear proveedor
        $codigoProveedor = $data['CODIGO PROVEEDOR'] ?? null;
        $proveedor = null;
        if ($codigoProveedor) {
            $nombreProveedor = $data['PROVEEDOR'] ?? 'Proveedor ' . $codigoProveedor;
            $proveedor = Supplier::firstOrCreate(
                ['codigo' => $codigoProveedor],
                [
                    'nombre_comercial' => $nombreProveedor,
                    'razon_social' => $nombreProveedor,
                    'nif_cif' => 'B' . str_pad($codigoProveedor, 8, '0', STR_PAD_LEFT),
                    'activo' => true,
                ]
            );
        }
        
        // Unidad base (por defecto: unidad)
        $unidadBase = Unit::where('codigo', 'UNI')->first();
        
        // Generar SKU único
        $sku = 'PRD-' . str_pad(Product::max('id') + 1, 6, '0', STR_PAD_LEFT);
        
        // Crear producto
        $producto = Product::create([
            'sku' => $sku,
            'nombre' => $nombre,
            'slug' => Str::slug($nombre),
            'descripcion' => $data['SUBDESCRIPCION DEL ARTICULO (PARA WEB)'] ?? null,
            'descripcion_larga_web' => $data['DESCRIPCION DEL ARTICULO(WEB)'] ?? null,
            'marca_id' => $marca->id,
            'categoria_id' => $categoria->id,
            'proveedor_principal_id' => $proveedor?->id,
            'unidad_base_id' => $unidadBase->id,
            'activo' => true,
        ]);
        
        // Helper para convertir valores vacíos o fórmulas a null
        $toDecimal = function($value) {
            if (empty($value) || !is_numeric($value) || str_starts_with($value, '=')) {
                return null;
            }
            return $value;
        };
        
        $toInteger = function($value) {
            if (empty($value) || !is_numeric($value)) {
                return null;
            }
            return (int) $value;
        };
        
        // Crear especificaciones
        ProductSpec::create([
            'producto_id' => $producto->id,
            'peso_kg' => $toDecimal($data['KG/LITRO'] ?? null),
            'largo_cm' => $toDecimal($data['LARGO'] ?? null),
            'ancho_cm' => $toDecimal($data['ANCHO'] ?? null),
            'metros_por_unidad' => $toDecimal($data['METROS ARTICULO'] ?? null),
            'unidades_por_embalaje' => $toInteger($data['ARTICULOS POR EMBALAJE'] ?? null),
            'unidades_por_palet' => $toInteger($data['UNIDADES/PALET'] ?? null),
            'palet_retornable' => ($data['PALET RETORNABLE'] ?? 'NO') === 'SI',
        ]);
        
        // Crear código de proveedor
        $codigoArticuloProveedor = $data['CODIGO ARTICULO PROVEEDOR'] ?? null;
        if ($codigoArticuloProveedor && $proveedor) {
            ProductCode::create([
                'producto_id' => $producto->id,
                'tipo' => 'proveedor',
                'codigo' => $codigoArticuloProveedor,
                'proveedor_id' => $proveedor->id,
                'principal' => true,
            ]);
        }
        
        // Crear coste si existe
        $pvpProveedor = $toDecimal($data['PVP PROVEEDOR'] ?? null);
        if ($pvpProveedor && $proveedor) {
            ProductCost::create([
                'producto_id' => $producto->id,
                'proveedor_id' => $proveedor->id,
                'precio_compra' => $pvpProveedor,
                'descuento_1' => $toDecimal($data['DESC PROV 1'] ?? 0) ?? 0,
                'descuento_2' => $toDecimal($data['DESC PROV 2'] ?? 0) ?? 0,
                'descuento_3' => $toDecimal($data['DESC PROV 3'] ?? 0) ?? 0,
                'unidad_id' => $unidadBase->id,
                'fecha_vigencia_desde' => now(),
                'activo' => true,
            ]);
        }
    }
}
