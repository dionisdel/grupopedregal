<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use App\Models\Quote;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class PortalDashboard extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        return [
            Stat::make('Productos visibles en web', Product::where('visible_web', true)->count())
                ->description('Productos publicados en el portal')
                ->descriptionIcon('heroicon-m-globe-alt')
                ->color('success'),

            Stat::make('Categorías activas', Category::where('activo', true)->count())
                ->description('Categorías visibles en el portal')
                ->descriptionIcon('heroicon-m-folder-open')
                ->color('info'),

            Stat::make('Usuarios registrados', User::count())
                ->description(User::where('estado', 'pendiente')->count() . ' pendientes de aprobación')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('warning'),

            Stat::make('Presupuestos generados', Quote::count())
                ->description('Total de presupuestos en el sistema')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
