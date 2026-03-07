<?php

namespace App\Filament\Widgets;

use App\Models\ProductPrice;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class OutdatedPricesAlert extends Widget
{
    protected string $view = 'filament.widgets.outdated-prices-alert';
    
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function getViewData(): array
    {
        $thirtyDaysAgo = Carbon::now()->subDays(30);
        
        $outdatedCount = ProductPrice::where('activo', true)
            ->where('updated_at', '<', $thirtyDaysAgo)
            ->count();
        
        $recentlyUpdated = ProductPrice::where('activo', true)
            ->where('updated_at', '>=', $thirtyDaysAgo)
            ->count();

        return [
            'outdatedCount' => $outdatedCount,
            'recentlyUpdated' => $recentlyUpdated,
            'showAlert' => $outdatedCount > 0,
        ];
    }
}
