<?php

namespace App\Filament\Resources\Permissions\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class PermissionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Permiso')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required()
                            ->maxLength(100),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        TextInput::make('description')
                            ->label('Descripción')
                            ->maxLength(255),
                        Select::make('module')
                            ->label('Módulo')
                            ->options([
                                'productos' => 'Productos',
                                'clientes' => 'Clientes',
                                'precios' => 'Precios',
                                'portal' => 'Portal',
                                'usuarios' => 'Usuarios',
                            ])
                            ->required(),
                    ])->columns(2),
            ]);
    }
}
