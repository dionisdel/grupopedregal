<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenSpout\Reader\XLSX\Reader as XLSXReader;

// Buscar archivos Excel en el directorio docs
$docsPath = realpath(__DIR__ . '/../../../docs/');
if (!$docsPath) {
    die("❌ No se encuentra el directorio docs\n");
}
$docsPath .= DIRECTORY_SEPARATOR;

echo "📁 Buscando archivos en: $docsPath\n\n";

$allFiles = glob($docsPath . '*.{xls,xlsx}', GLOB_BRACE);
$excelFiles = array_map('basename', $allFiles);

if (empty($excelFiles)) {
    die("❌ No se encontraron archivos Excel\n");
}

echo "📋 Archivos encontrados: " . count($excelFiles) . "\n";
foreach ($excelFiles as $f) {
    echo "  - $f\n";
}
echo "\n";

$analysis = [];

foreach ($excelFiles as $file) {
    $filePath = $docsPath . $file;
    
    if (!file_exists($filePath)) {
        echo "⚠️  Archivo no encontrado: $file\n\n";
        continue;
    }
    
    echo "📊 Analizando: $file\n";
    echo str_repeat('=', 80) . "\n";
    
    try {
        $extension = pathinfo($file, PATHINFO_EXTENSION);
        
        if ($extension === 'xls') {
            echo "⚠️  Formato XLS no soportado por OpenSpout. Por favor convierte a XLSX.\n\n";
            continue;
        }
        
        $reader = new XLSXReader();
        $reader->open($filePath);
        
        $sheetIndex = 0;
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetIndex++;
            echo "\n📄 Hoja {$sheetIndex}: {$sheet->getName()}\n";
            echo str_repeat('-', 80) . "\n";
            
            $rowIndex = 0;
            $headers = [];
            $sampleRows = [];
            
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->getCells();
                $rowData = array_map(fn($cell) => $cell->getValue(), $cells);
                
                if ($rowIndex === 1) {
                    $headers = $rowData;
                    echo "Columnas (" . count($headers) . "):\n";
                    foreach ($headers as $idx => $header) {
                        echo "  " . ($idx + 1) . ". " . ($header ?: '[vacío]') . "\n";
                    }
                } elseif ($rowIndex <= 4) {
                    $sampleRows[] = $rowData;
                }
                
                if ($rowIndex > 4) break;
            }
            
            echo "\nPrimeras 3 filas de datos:\n";
            foreach ($sampleRows as $idx => $row) {
                echo "\nFila " . ($idx + 2) . ":\n";
                foreach ($row as $colIdx => $value) {
                    $header = $headers[$colIdx] ?? "Col" . ($colIdx + 1);
                    $displayValue = is_string($value) ? substr($value, 0, 50) : $value;
                    echo "  {$header}: " . ($displayValue ?? '[null]') . "\n";
                }
            }
        }
        
        $reader->close();
        echo "\n" . str_repeat('=', 80) . "\n\n";
        
    } catch (Exception $e) {
        echo "❌ Error al leer el archivo: " . $e->getMessage() . "\n\n";
    }
}

echo "\n✅ Análisis completado\n";
