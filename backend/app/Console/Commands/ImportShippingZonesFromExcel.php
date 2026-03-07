<?php

namespace App\Console\Commands;

use App\Models\ShippingZone;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use OpenSpout\Reader\XLSX\Reader as XLSXReader;

class ImportShippingZonesFromExcel extends Command
{
    protected $signature = 'import:zones {file}';
    protected $description = 'Importar zonas de envío desde archivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: $filePath");
            return 1;
        }

        // Verificar extensión
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        if ($extension === 'xls') {
            $this->error("Los archivos .xls no son soportados. Por favor convierte el archivo a .xlsx");
            $this->info("Puedes abrirlo en Excel y guardarlo como .xlsx");
            return 1;
        }

        $this->info("Importando zonas desde: $filePath");
        
        $reader = new XLSXReader();
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
                    $this->importZone($headers, $rowData);
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
        $this->info("  - Zonas importadas: $imported");
        $this->info("  - Errores: $errors");
        
        return 0;
    }

    private function importZone(array $headers, array $rowData): void
    {
        $headerCount = count($headers);
        $dataCount = count($rowData);
        
        if ($dataCount < $headerCount) {
            $rowData = array_pad($rowData, $headerCount, null);
        } elseif ($dataCount > $headerCount) {
            $rowData = array_slice($rowData, 0, $headerCount);
        }
        
        $data = array_combine($headers, $rowData);
        
        $codigo = trim($data['CODIGO'] ?? $data['Codigo'] ?? '');
        $nombre = trim($data['NOMBRE'] ?? $data['Nombre'] ?? $data['ZONA'] ?? '');
        
        if (empty($codigo) || empty($nombre)) {
            throw new \Exception("Código o nombre vacío");
        }

        ShippingZone::updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre' => $nombre,
                'activo' => true,
            ]
        );
    }
}
