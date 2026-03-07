<?php

namespace App\Filament\Widgets;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductPrice;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MarginsByFamilyChart extends ChartWidget
{
    protected ?string $heading = 'Márgenes Promedio por Familia';
    
    protected static ?int $sort = 2;

    protected function getData(): array
    {
        // Obtener categorías de nivel 1 (familias principales)
        $categories = Category::whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        $data = [];
        $labels = [];

        foreach ($categories as $category) {
            // Obtener productos de esta familia (incluyendo subcategorías)
            $categoryIds = $this->getCategoryAndDescendantIds($category->id);
            
            // Calcular margen promedio de productos en esta familia
            $avgMargin = ProductPrice::whereHas('producto', function ($query) use ($categoryIds) {
                $query->whereIn('categoria_id', $categoryIds);
            })
            ->where('activo', true)
            ->avg('margen_porcentaje');

            if ($avgMargin !== null) {
                $labels[] = $category->nombre;
                $data[] = round($avgMargin, 2);
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Margen Promedio (%)',
                    'data' => $data,
                    'backgroundColor' => [
                        'rgba(59, 130, 246, 0.5)',
                        'rgba(16, 185, 129, 0.5)',
                        'rgba(245, 158, 11, 0.5)',
                        'rgba(239, 68, 68, 0.5)',
                        'rgba(139, 92, 246, 0.5)',
                        'rgba(236, 72, 153, 0.5)',
                    ],
                    'borderColor' => [
                        'rgb(59, 130, 246)',
                        'rgb(16, 185, 129)',
                        'rgb(245, 158, 11)',
                        'rgb(239, 68, 68)',
                        'rgb(139, 92, 246)',
                        'rgb(236, 72, 153)',
                    ],
                    'borderWidth' => 2,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * Obtener IDs de categoría y todas sus descendientes
     */
    private function getCategoryAndDescendantIds(int $categoryId): array
    {
        $ids = [$categoryId];
        
        $children = Category::where('parent_id', $categoryId)->pluck('id');
        
        foreach ($children as $childId) {
            $ids = array_merge($ids, $this->getCategoryAndDescendantIds($childId));
        }
        
        return $ids;
    }
}
