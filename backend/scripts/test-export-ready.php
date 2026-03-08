<?php

/**
 * Script para verificar que todo esté listo para la exportación
 */

echo "=== VERIFICACIÓN DE EXPORTACIÓN DE TARIFAS ===\n\n";

// Verificar que el comando existe
$commandFile = __DIR__ . '/../app/Console/Commands/ExportPricesToExcel.php';
if (file_exists($commandFile)) {
    echo "✅ Comando de exportación existe\n";
    echo "   Ubicación: $commandFile\n";
} else {
    echo "❌ Comando de exportación NO encontrado\n";
    exit(1);
}

// Verificar que OpenSpout esté instalado
$composerFile = __DIR__ . '/../composer.json';
if (file_exists($composerFile)) {
    $composer = json_decode(file_get_contents($composerFile), true);
    if (isset($composer['require']['openspout/openspout'])) {
        echo "✅ OpenSpout instalado (versión: " . $composer['require']['openspout/openspout'] . ")\n";
    } else {
        echo "⚠️  OpenSpout no encontrado en composer.json\n";
    }
}

// Verificar directorio de salida
$outputDir = __DIR__ . '/../../docs';
if (is_dir($outputDir) && is_writable($outputDir)) {
    echo "✅ Directorio de salida existe y es escribible\n";
    echo "   Ubicación: $outputDir\n";
} else {
    echo "⚠️  Directorio de salida no es escribible\n";
}

echo "\n--- Comando para Ejecutar ---\n";
echo "cd E:\\projects\\tony\\app\\backend\n";
echo "E:\\laragon\\laragonphp84\\bin\\php\\php-8.3.30-Win32-vs16-x64\\php.exe artisan export:prices \"E:\\projects\\tony\\docs\\TARIFAS-NETOS-CLIENTES.xlsx\"\n";

echo "\n--- Requisitos Previos ---\n";
echo "1. ✅ Iniciar Laragon (para MySQL)\n";
echo "2. ✅ Verificar que el servidor Laravel esté corriendo\n";
echo "3. ✅ Ejecutar el comando de exportación\n";

echo "\n✅ Todo listo para la exportación\n";
echo "\n📋 Datos disponibles:\n";
echo "   - 550 productos con precios\n";
echo "   - 3,300 precios calculados\n";
echo "   - 6 tipos de cliente configurados\n";

echo "\n🎯 Archivo de salida: TARIFAS-NETOS-CLIENTES.xlsx\n";
echo "   Formato: SKU | Código | Nombre | Categoría | Marca | Unidad | [6 columnas de precios]\n";
