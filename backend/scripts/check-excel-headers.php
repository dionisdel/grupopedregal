<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenSpout\Reader\XLSX\Reader;

$file = $argv[1] ?? null;

if (!$file || !file_exists($file)) {
    echo "Uso: php check-excel-headers.php <archivo.xlsx>\n";
    exit(1);
}

$reader = new Reader();
$reader->open($file);

foreach ($reader->getSheetIterator() as $sheet) {
    echo "Hoja: {$sheet->getName()}\n";
    echo str_repeat('=', 80) . "\n";
    
    $rowIndex = 0;
    foreach ($sheet->getRowIterator() as $row) {
        $rowIndex++;
        $cells = $row->getCells();
        $rowData = array_map(fn($cell) => $cell->getValue(), $cells);
        
        if ($rowIndex === 1) {
            echo "HEADERS:\n";
            foreach ($rowData as $i => $header) {
                echo "  [$i] => " . ($header ?: '[vacío]') . "\n";
            }
        } elseif ($rowIndex <= 5) {
            echo "\nFILA $rowIndex:\n";
            foreach ($rowData as $i => $value) {
                echo "  [$i] => " . ($value ?: '[vacío]') . "\n";
            }
        } elseif ($rowIndex > 5) {
            break;
        }
    }
}

$reader->close();
