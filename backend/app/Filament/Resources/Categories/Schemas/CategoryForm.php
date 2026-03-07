<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('codigo')
                    ->label('Código')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100),
                Select::make('parent_id')
                    ->label('Categoría Padre')
                    ->relationship('parent', 'nombre')
                    ->searchable()
                    ->preload(),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
