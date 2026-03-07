<?php

namespace App\Filament\Widgets;

use App\Models\Product;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class ProductsWithoutPricesWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Productos sin Precios Configurados')
            ->query(
                Product::query()
                    ->whereDoesntHave('precios')
                    ->where('activo', true)
                    ->orderBy('nombre')
            )
            ->columns([
                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable(),
                
                TextColumn::make('nombre')
                    ->label('Producto')
                    ->searchable()
                    ->limit(50),
                
                TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->badge(),
                
                TextColumn::make('marca.nombre')
                    ->label('Marca')
                    ->badge(),
                
                TextColumn::make('proveedor.nombre')
                    ->label('Proveedor')
                    ->limit(30),
            ])
            ->paginated([10, 25, 50]);
    }
}
