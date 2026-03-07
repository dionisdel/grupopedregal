<?php

namespace App\Filament\Widgets;

use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductPrice;
use App\Models\Supplier;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalProducts = Product::count();
        $activeProducts = Product::where('activo', true)->count();
        $productsWithPrices = Product::whereHas('precios')->count();
        $productsWithoutPrices = $totalProducts - $productsWithPrices;
        
        return [
            Stat::make('Total Productos', $totalProducts)
                ->description($activeProducts . ' activos')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
            
            Stat::make('Productos con Precios', $productsWithPrices)
                ->description($productsWithoutPrices . ' sin precios')
                ->descriptionIcon($productsWithoutPrices > 0 ? 'heroicon-m-exclamation-triangle' : 'heroicon-m-check-circle')
                ->color($productsWithoutPrices > 0 ? 'warning' : 'success'),
            
            Stat::make('Total Clientes', Customer::count())
                ->description(Customer::where('activo', true)->count() . ' activos')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('info'),
            
            Stat::make('Total Proveedores', Supplier::count())
                ->description(Supplier::where('activo', true)->count() . ' activos')
                ->descriptionIcon('heroicon-m-building-storefront')
                ->color('info'),
            
            Stat::make('Precios Generados', ProductPrice::count())
                ->description(ProductPrice::where('activo', true)->count() . ' activos')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success'),
        ];
    }
}
