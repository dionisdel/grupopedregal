<?php

$jsonPath = __DIR__ . '/../../../docs/analisis-estructura-datos.json';
$data = json_decode(file_get_contents($jsonPath), true);

echo "=== ANÁLISIS DETALLADO DE ESTRUCTURA ===\n\n";

// Analizar archivo principal de artículos
$mainFile = null;
foreach ($data['archivos'] as $archivo) {
    if (strpos($archivo['nombre'], 'TABLA TARIFA') !== false) {
        $mainFile = $archivo;
        break;
    }
}

if ($mainFile && !empty($mainFile['hojas'])) {
    $hoja = $mainFile['hojas'][0];
    echo "📦 ARCHIVO MAESTRO DE PRODUCTOS\n";
    echo "Archivo: {$mainFile['nombre']}\n";
    echo "Total productos: " . ($hoja['total_filas'] - 1) . "\n\n";
    
    echo "🔍 ESTRUCTURA DE COLUMNAS:\n\n";
    
    // Agrupar columnas por categoría
    $categorias = [
        'Identificación' => ['DESCRIPCION', 'CODIGO', 'MARCA'],
        'Proveedor' => ['PROVEEDOR', 'CODIGO ARTICULO PROVEEDOR'],
        'Dimensiones' => ['KG/LITRO', 'LARGO', 'ANCHO', 'METROS'],
        'Embalaje' => ['UNIDADES', 'EMBALAJE', 'PALET'],
        'Clasificación' => ['FAMILIA', 'SUBFAMILIA'],
        'Costes' => ['COSTE', 'PVP PROVEEDOR', 'DESC PROV'],
        'Precios' => ['NETO', 'DESC.', 'PVP'],
        'Web' => ['WEB'],
    ];
    
    foreach ($categorias as $cat => $keywords) {
        echo "$cat:\n";
        foreach ($hoja['columnas'] as $idx => $col) {
            $col = trim($col);
            if (empty($col)) continue;
            
            foreach ($keywords as $kw) {
                if (stripos($col, $kw) !== false) {
                    echo "  - $col\n";
                    break;
                }
            }
        }
        echo "\n";
    }
    
    echo "\n📊 TIPOS DE TARIFA DETECTADOS:\n";
    $tarifas = [];
    foreach ($hoja['columnas'] as $col) {
        if (stripos($col, 'NETO') !== false && stripos($col, 'COSTE') === false) {
            $tarifas[] = str_replace('NETO ', '', $col);
        }
    }
    foreach (array_unique($tarifas) as $t) {
        echo "  - $t\n";
    }
}

echo "\n\n=== OTROS ARCHIVOS ===\n\n";

foreach ($data['archivos'] as $archivo) {
    if (strpos($archivo['nombre'], 'tmp_excel') === false) continue;
    
    $nombre = str_replace(['2026-03-06 tmp_excel', '.xlsx'], '', $archivo['nombre']);
    echo "📋 $nombre\n";
    
    if (!empty($archivo['hojas'][0]['filas_muestra'])) {
        $muestra = $archivo['hojas'][0]['filas_muestra'];
        
        // Buscar la fila de headers (generalmente fila 3)
        $headers = null;
        $dataStart = 0;
        foreach ($muestra as $idx => $fila) {
            $values = array_values($fila);
            if (in_array('Código', $values) || in_array('Nombre', $values)) {
                $headers = $fila;
                $dataStart = $idx + 1;
                break;
            }
        }
        
        if ($headers) {
            echo "  Columnas: " . implode(', ', array_filter(array_values($headers))) . "\n";
            
            if (isset($muestra[$dataStart])) {
                echo "  Ejemplo: ";
                $ejemplo = array_filter(array_values($muestra[$dataStart]));
                echo implode(' | ', array_slice($ejemplo, 0, 3)) . "\n";
            }
        }
    }
    
    echo "\n";
}
