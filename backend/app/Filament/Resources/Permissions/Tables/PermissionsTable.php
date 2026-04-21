<?php

namespace App\Filament\Resources\Permissions\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class PermissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('module')
                    ->label('Módulo')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'productos' => 'success',
                        'clientes' => 'info',
                        'precios' => 'warning',
                        'portal' => 'primary',
                        'usuarios' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->sortable(),
            ])
            ->defaultSort('module')
            ->defaultGroup(
                Group::make('module')
                    ->label('Módulo')
                    ->collapsible()
            )
            ->filters([
                SelectFilter::make('module')
                    ->label('Módulo')
                    ->options([
                        'productos' => 'Productos',
                        'clientes' => 'Clientes',
                        'precios' => 'Precios',
                        'portal' => 'Portal',
                        'usuarios' => 'Usuarios',
                    ]),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
