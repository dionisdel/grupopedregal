<?php

$jsonPath = __DIR__ . '/../../../docs/analisis-estructura-datos.json';
$data = json_decode(file_get_contents($jsonPath), true);

$report = "# ANÁLISIS DE ESTRUCTURA DE DATOS - SISTEMA ERP ACTUAL\n\n";
$report .= "Fecha de análisis: {$data['fecha_analisis']}\n\n";
$report .= "---\n\n";

foreach ($data['archivos'] as $archivo) {
    $report .= "## 📄 {$archivo['nombre']}\n\n";
    
    foreach ($archivo['hojas'] as $hoja) {
        $report .= "### Hoja: {$hoja['nombre']}\n";
        $report .= "- Total filas: {$hoja['total_filas']}\n";
        $report .= "- Total columnas: " . count($hoja['columnas']) . "\n\n";
        
        if (!empty($hoja['columnas'])) {
            $report .= "**Columnas:**\n";
            $colNum = 1;
            foreach ($hoja['columnas'] as $col) {
                $colName = trim($col) ?: '[vacío]';
                if ($colName !== '[vacío]') {
                    $report .= "- $colNum. $colName\n";
                }
                $colNum++;
            }
            $report .= "\n";
        }
        
        if (!empty($hoja['filas_muestra'])) {
            $report .= "**Muestra de datos (primeras 5 filas):**\n\n";
            $count = 0;
            foreach ($hoja['filas_muestra'] as $idx => $fila) {
                if ($count >= 5) break;
                $count++;
                
                $report .= "Fila " . ($idx + 2) . ":\n";
                foreach ($fila as $key => $value) {
                    if (trim($key) === '' || $value === '' || $value === null) continue;
                    
                    $displayValue = is_string($value) ? substr($value, 0, 80) : $value;
                    $report .= "  - $key: $displayValue\n";
                }
                $report .= "\n";
            }
        }
        
        $report .= "---\n\n";
    }
}

$outputPath = __DIR__ . '/../../../docs/ANALISIS-ESTRUCTURA-DATOS.md';
file_put_contents($outputPath, $report);
echo "✅ Reporte generado: ANALISIS-ESTRUCTURA-DATOS.md\n";
