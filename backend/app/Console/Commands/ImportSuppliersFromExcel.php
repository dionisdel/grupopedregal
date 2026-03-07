<?php

namespace App\Console\Commands;

use App\Models\Supplier;
use Illuminate\Console\Command;
use OpenSpout\Reader\XLSX\Reader;

class ImportSuppliersFromExcel extends Command
{
    protected $signature = 'import:suppliers {file}';
    protected $description = 'Importar proveedores desde archivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: $filePath");
            return 1;
        }

        $this->info("Importando proveedores desde: $filePath");
        
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
                
                // Saltar filas de título (primeras 2 filas)
                if ($rowIndex <= 2) {
                    continue;
                }
                
                if ($rowIndex === 3) {
                    $headers = $rowData;
                    continue;
                }
                
                try {
                    $this->importSupplier($headers, $rowData);
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
        $this->info("  - Proveedores importados: $imported");
        $this->info("  - Errores: $errors");
        
        return 0;
    }

    private function importSupplier(array $headers, array $rowData): void
    {
        $headerCount = count($headers);
        $dataCount = count($rowData);
        
        if ($dataCount < $headerCount) {
            $rowData = array_pad($rowData, $headerCount, null);
        } elseif ($dataCount > $headerCount) {
            $rowData = array_slice($rowData, 0, $headerCount);
        }
        
        $data = array_combine($headers, $rowData);
        
        $codigo = trim($data['Código'] ?? $data['CODIGO'] ?? $data['Codigo'] ?? '');
        $nombre = trim($data['Nombre comercial / Forma de pago'] ?? $data['NOMBRE'] ?? $data['Nombre'] ?? '');
        
        if (empty($codigo) || empty($nombre) || !is_numeric($codigo)) {
            throw new \Exception("Código o nombre vacío");
        }

        Supplier::updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre_comercial' => $nombre,
                'razon_social' => $data['Razón social / IBAN'] ?? $nombre,
                'nif_cif' => $data['Nif/Cif'] ?? $data['NIF/CIF'] ?? $data['CIF'] ?? null,
                'telefono' => $data['Teléfono'] ?? $data['TELEFONO'] ?? $data['Telefono'] ?? null,
                'email' => $data['Email'] ?? $data['EMAIL'] ?? null,
                'direccion' => $data['Dirección completa'] ?? $data['DIRECCION'] ?? $data['Direccion'] ?? null,
                'contacto_principal' => $data['CONTACTO'] ?? null,
                'notas' => $data['NOTAS'] ?? null,
                'activo' => true,
            ]
        );
    }
}
