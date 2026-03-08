<?php

namespace App\Console\Commands;

use App\Models\CustomerType;
use App\Models\Product;
use App\Models\ProductPrice;
use Illuminate\Console\Command;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class ExportPricesToExcel extends Command
{
    protected $signature = 'export:prices 
                            {output=tarifas-export.xlsx : Ruta del archivo de salida}
                            {--format=horizontal : Formato: horizontal (columnas por tipo) o vertical (filas por tipo)}
                            {--active-only : Solo productos activos}';
    protected $description = 'Exportar tarifas con precios netos por tipo de cliente';

    public function handle()
    {
        $outputPath = $this->argument('output');
        
        // Si no tiene ruta completa, guardar en storage/app
        if (!str_contains($outputPath, ':') && !str_starts_with($outputPath, '/')) {
            $outputPath = storage_path('app/' . $outputPath);
        }

        $this->info('Exportando tarifas a: ' . $outputPath);

        // Obtener tipos de cliente ordenados
        $customerTypes = CustomerType::orderBy('nombre')->get();
        
        // Obtener productos que tienen precios
        $products = Product::whereHas('precios')
            ->with(['precios.tipoCliente', 'categoria', 'marca', 'unidadBase'])
            ->orderBy('nombre')
            ->get();

        $this->info('Productos con precios: ' . $products->count());

        // Crear archivo Excel
        $writer = new Writer();
        $writer->openToFile($outputPath);

        // Crear encabezados
        $headers = [
            'SKU',
            'Código',
            'Nombre Producto',
            'Categoría',
            'Marca',
            'Unidad',
        ];

        // Agregar columna por cada tipo de cliente
        foreach ($customerTypes as $type) {
            $headers[] = $type->nombre;
        }

        $writer->addRow(Row::fromValues($headers));

        // Agregar datos
        $rowCount = 0;
        foreach ($products as $product) {
            $row = [
                $product->sku,
                $product->codigo_interno ?? '',
                $product->nombre,
                $product->categoria->nombre ?? '',
                $product->marca->nombre ?? '',
                $product->unidadBase->codigo ?? '',
            ];

            // Agregar precio neto por cada tipo de cliente
            foreach ($customerTypes as $type) {
                $price = $product->precios
                    ->where('tipo_cliente_id', $type->id)
                    ->where('activo', true)
                    ->first();

                if ($price) {
                    // Calcular precio neto: precio_base * (1 - descuento_porcentaje/100)
                    $precioNeto = $price->precio_base * (1 - ($price->descuento_porcentaje / 100));
                    $row[] = number_format($precioNeto, 2, '.', '');
                } else {
                    $row[] = '';
                }
            }

            $writer->addRow(Row::fromValues($row));
            $rowCount++;

            if ($rowCount % 100 == 0) {
                $this->info("Procesados: $rowCount productos");
            }
        }

        $writer->close();

        $this->info('✅ Exportación completada');
        $this->info("Total productos exportados: $rowCount");
        $this->info("Archivo: $outputPath");

        return 0;
    }
}
