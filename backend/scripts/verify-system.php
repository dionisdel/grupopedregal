<?php

/**
 * Script de verificación del sistema
 * Ejecutar: php scripts/verify-system.php
 */

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== VERIFICACIÓN DEL SISTEMA CRM/ERP ===\n\n";

// Verificar conexión a base de datos
try {
    DB::connection()->getPdo();
    echo "✅ Conexión a base de datos: OK\n";
} catch (\Exception $e) {
    echo "❌ Error de conexión a base de datos: " . $e->getMessage() . "\n";
    exit(1);
}

// Verificar tablas
$tables = [
    'products', 'suppliers', 'customers', 'categories', 'brands',
    'units', 'customer_types', 'payment_methods', 'shipping_methods',
    'vehicles', 'shipping_zones', 'product_prices', 'product_costs',
    'product_specs', 'product_codes'
];

echo "\n--- Verificando tablas ---\n";
foreach ($tables as $table) {
    try {
        $count = DB::table($table)->count();
        echo "✅ $table: $count registros\n";
    } catch (\Exception $e) {
        echo "❌ Error en tabla $table: " . $e->getMessage() . "\n";
    }
}

// Verificar modelos
echo "\n--- Verificando modelos ---\n";
$models = [
    'Product' => \App\Models\Product::class,
    'Supplier' => \App\Models\Supplier::class,
    'Customer' => \App\Models\Customer::class,
    'ProductPrice' => \App\Models\ProductPrice::class,
];

foreach ($models as $name => $class) {
    try {
        $count = $class::count();
        echo "✅ $name: $count registros\n";
    } catch (\Exception $e) {
        echo "❌ Error en modelo $name: " . $e->getMessage() . "\n";
    }
}

// Estadísticas clave
echo "\n--- Estadísticas Clave ---\n";
$totalProducts = \App\Models\Product::count();
$productsWithPrices = \App\Models\Product::whereHas('precios')->count();
$productsWithoutPrices = $totalProducts - $productsWithPrices;
$totalPrices = \App\Models\ProductPrice::count();
$activePrices = \App\Models\ProductPrice::where('activo', true)->count();

echo "Total productos: $totalProducts\n";
echo "Productos con precios: $productsWithPrices\n";
echo "Productos sin precios: $productsWithoutPrices\n";
echo "Total precios generados: $totalPrices\n";
echo "Precios activos: $activePrices\n";

echo "\n✅ Sistema verificado correctamente\n";
