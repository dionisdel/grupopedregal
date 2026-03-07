<?php

namespace App\Console\Commands;

use App\Models\Customer;
use App\Models\CustomerType;
use App\Models\ShippingZone;
use Illuminate\Console\Command;
use OpenSpout\Reader\XLSX\Reader;

class ImportCustomersFromExcel extends Command
{
    protected $signature = 'import:customers {file}';
    protected $description = 'Importar clientes desde archivo Excel';

    public function handle()
    {
        $filePath = $this->argument('file');
        
        if (!file_exists($filePath)) {
            $this->error("Archivo no encontrado: $filePath");
            return 1;
        }

        $this->info("Importando clientes desde: $filePath");
        
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
                
                // Fila 3 contiene los headers
                if ($rowIndex === 3) {
                    $headers = $rowData;
                    $this->info("Headers encontrados: " . implode(', ', $headers));
                    continue;
                }
                
                // Saltar filas antes de headers
                if ($rowIndex < 3 || empty($headers)) {
                    continue;
                }
                
                // Solo procesar filas que empiezan con un código numérico
                $firstCell = trim($rowData[0] ?? '');
                if (!is_numeric($firstCell) || empty($firstCell)) {
                    continue;
                }
                
                try {
                    $this->importCustomer($headers, $rowData);
                    $imported++;
                    $this->info("✓ Fila $rowIndex importada: {$rowData[1]}");
                } catch (\Exception $e) {
                    $errors++;
                    $this->error("✗ Error en fila $rowIndex: " . $e->getMessage());
                }
            }
        }
        
        $reader->close();
        
        $this->info("\n✅ Importación completada:");
        $this->info("  - Clientes importados: $imported");
        $this->info("  - Errores: $errors");
        
        return 0;
    }

    private function importCustomer(array $headers, array $rowData): void
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
            throw new \Exception("Código o nombre inválido");
        }

        // Buscar tipo de cliente - por defecto EMPRESAS
        $tipoCliente = CustomerType::where('nombre', 'EMPRESAS')->first();
        if (!$tipoCliente) {
            $tipoCliente = CustomerType::first();
        }

        Customer::updateOrCreate(
            ['codigo' => $codigo],
            [
                'nombre_comercial' => $nombre,
                'razon_social' => $data['Razón social / IBAN'] ?? $nombre,
                'nif_cif' => $data['Nif/Cif'] ?? $data['NIF/CIF'] ?? $data['CIF'] ?? null,
                'tipo_cliente_id' => $tipoCliente->id,
                'telefono' => $data['Teléfono'] ?? $data['TELEFONO'] ?? $data['Telefono'] ?? null,
                'direccion' => $data['Dirección completa'] ?? $data['DIRECCION'] ?? $data['Direccion'] ?? null,
                'activo' => true,
            ]
        );
    }
}
