<?php

// Script para crear recursos Filament básicos

$resources = [
    'Customer' => ['label' => 'Cliente', 'plural' => 'Clientes', 'icon' => 'heroicon-o-user-group', 'title' => 'nombre_comercial'],
    'Brand' => ['label' => 'Marca', 'plural' => 'Marcas', 'icon' => 'heroicon-o-tag', 'title' => 'nombre'],
    'Category' => ['label' => 'Categoría', 'plural' => 'Categorías', 'icon' => 'heroicon-o-folder', 'title' => 'nombre'],
    'Unit' => ['label' => 'Unidad', 'plural' => 'Unidades', 'icon' => 'heroicon-o-scale', 'title' => 'nombre'],
    'CustomerType' => ['label' => 'Tipo Cliente', 'plural' => 'Tipos de Cliente', 'icon' => 'heroicon-o-user-circle', 'title' => 'nombre'],
    'PaymentMethod' => ['label' => 'Forma de Pago', 'plural' => 'Formas de Pago', 'icon' => 'heroicon-o-credit-card', 'title' => 'nombre'],
    'ShippingMethod' => ['label' => 'Forma de Envío', 'plural' => 'Formas de Envío', 'icon' => 'heroicon-o-truck', 'title' => 'nombre'],
    'Vehicle' => ['label' => 'Vehículo', 'plural' => 'Vehículos', 'icon' => 'heroicon-o-truck', 'title' => 'nombre'],
    'ShippingZone' => ['label' => 'Zona', 'plural' => 'Zonas', 'icon' => 'heroicon-o-map', 'title' => 'nombre'],
];

foreach ($resources as $model => $config) {
    echo "Creando recurso para: $model\n";
    echo "  - Label: {$config['label']}\n";
    echo "  - Title attribute: {$config['title']}\n";
    echo "  - Icon: {$config['icon']}\n\n";
}

echo "\n✅ Información de recursos preparada\n";
echo "\nPara crear manualmente, usa:\n";
echo "php artisan make:filament-resource {Model} --simple\n";
echo "Y cuando pregunte por 'title attribute', usa el campo indicado arriba.\n";
