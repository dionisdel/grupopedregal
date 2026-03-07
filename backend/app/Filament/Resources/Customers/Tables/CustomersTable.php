<?php

namespace App\Filament\Resources\Customers\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CustomersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nombre_comercial')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tipoCliente.nombre')
                    ->label('Tipo')
                    ->sortable(),
                TextColumn::make('zonaEnvio.nombre')
                    ->label('Zona')
                    ->toggleable(),
                TextColumn::make('telefono')
                    ->toggleable(),
                TextColumn::make('email')
                    ->toggleable(),
                IconColumn::make('activo')
                    ->boolean(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
