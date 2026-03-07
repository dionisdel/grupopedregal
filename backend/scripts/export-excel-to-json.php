<?php

require __DIR__ . '/../vendor/autoload.php';

use OpenSpout\Reader\XLSX\Reader as XLSXReader;

$docsPath = realpath(__DIR__ . '/../../../docs/');
if (!$docsPath) {
    die("❌ No se encuentra el directorio docs\n");
}
$docsPath .= DIRECTORY_SEPARATOR;

$outputPath = $docsPath . 'analisis-estructura-datos.json';

$allFiles = glob($docsPath . '*.xlsx', GLOB_BRACE);
$excelFiles = array_map('basename', $allFiles);

$analysis = [
    'fecha_analisis' => date('Y-m-d H:i:s'),
    'archivos' => []
];

foreach ($excelFiles as $file) {
    $filePath = $docsPath . $file;
    
    echo "📊 Procesando: $file\n";
    
    try {
        $reader = new XLSXReader();
        $reader->open($filePath);
        
        $fileData = [
            'nombre' => $file,
            'hojas' => []
        ];
        
        foreach ($reader->getSheetIterator() as $sheet) {
            $sheetData = [
                'nombre' => $sheet->getName(),
                'columnas' => [],
                'filas_muestra' => [],
                'total_filas' => 0
            ];
            
            $rowIndex = 0;
            $headers = [];
            
            foreach ($sheet->getRowIterator() as $row) {
                $rowIndex++;
                $cells = $row->getCells();
                $rowData = array_map(fn($cell) => $cell->getValue(), $cells);
                
                if ($rowIndex === 1) {
                    $headers = $rowData;
                    $sheetData['columnas'] = $headers;
                } elseif ($rowIndex <= 20) {
                    $assocRow = [];
                    foreach ($rowData as $idx => $value) {
                        $header = $headers[$idx] ?? "col_$idx";
                        $assocRow[$header] = $value;
                    }
                    $sheetData['filas_muestra'][] = $assocRow;
                }
                
                if ($rowIndex > 100) break;
            }
            
            $sheetData['total_filas'] = $rowIndex;
            $fileData['hojas'][] = $sheetData;
        }
        
        $reader->close();
        $analysis['archivos'][] = $fileData;
        
    } catch (Exception $e) {
        echo "❌ Error: " . $e->getMessage() . "\n";
    }
}

file_put_contents($outputPath, json_encode($analysis, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
echo "\n✅ Análisis guardado en: analisis-estructura-datos.json\n";
