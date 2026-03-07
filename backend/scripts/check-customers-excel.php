<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenSpout\Reader\XLSX\Reader;

$filePath = 'E:\projects\tony\docs\2026-03-06 tmp_excelCLIENTES.xlsx';

if (!file_exists($filePath)) {
    echo "Archivo no encontrado: $filePath\n";
    exit(1);
}

$reader = new Reader();
$reader->open($filePath);

foreach ($reader->getSheetIterator() as $sheet) {
    echo "=== HOJA: {$sheet->getName()} ===\n\n";
    
    $rowIndex = 0;
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex++;
        $cells = $row->getCells();
        $rowData = array_map(fn($cell) => $cell->getValue(), $cells);
        
        // Mostrar primeras 10 filas
        if ($rowIndex <= 10) {
            echo "Fila $rowIndex: " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n";
        }
        
        // Buscar primera fila con datos reales
        if ($rowIndex > 10 && $rowIndex <= 100) {
            $hasData = false;
            foreach ($rowData as $value) {
                if (!empty(trim($value ?? ''))) {
                    $hasData = true;
                    break;
                }
            }
            
            if ($hasData) {
                echo "\nPrimera fila con datos (fila $rowIndex): " . json_encode($rowData, JSON_UNESCAPED_UNICODE) . "\n";
                break;
            }
        }
    }
    
    echo "\nTotal de filas en la hoja: $rowIndex\n";
}

$reader->close();
