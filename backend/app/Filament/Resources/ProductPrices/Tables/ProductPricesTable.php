<?php

namespace App\Filament\Resources\ProductPrices\Tables;

use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductPricesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('producto.sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),
                
                TextColumn::make('producto.nombre')
                    ->label('Producto')
                    ->searchable()
                    ->limit(40)
                    ->sortable(),
                
                TextColumn::make('tipoCliente.nombre')
                    ->label('Tipo Cliente')
                    ->badge()
                    ->sortable(),
                
                TextColumn::make('precio_base')
                    ->label('Precio Base')
                    ->money('EUR')
                    ->sortable(),
                
                TextColumn::make('precio_neto')
                    ->label('Precio Neto')
                    ->money('EUR')
                    ->sortable(),
                
                TextColumn::make('margen_porcentaje')
                    ->label('Margen %')
                    ->suffix('%')
                    ->sortable(),
                
                TextColumn::make('fecha_vigencia_desde')
                    ->label('Vigente Desde')
                    ->date('d/m/Y')
                    ->sortable(),
                
                IconColumn::make('activo')
                    ->label('Activo')
                    ->boolean()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo_cliente_id')
                    ->label('Tipo de Cliente')
                    ->relationship('tipoCliente', 'nombre'),
                
                SelectFilter::make('activo')
                    ->label('Estado')
                    ->options([
                        1 => 'Activo',
                        0 => 'Inactivo',
                    ]),
            ])
            ->actions([
                EditAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('producto.nombre', 'asc');
    }
}
