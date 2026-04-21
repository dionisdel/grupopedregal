<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ViewField;
use Filament\Schemas\Schema;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
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
                    ])->columns(3),

                Section::make('Contenido Web')
                    ->description('Campos visibles en el portal público')
                    ->schema([
                        Textarea::make('descripcion_web')
                            ->label('Descripción Web')
                            ->rows(3)
                            ->maxLength(500)
                            ->helperText('Texto que aparece en la tarjeta de categoría del portal'),
                        FileUpload::make('imagen_url')
                            ->label('Imagen de Categoría')
                            ->image()
                            ->directory('categories')
                            ->helperText('Imagen para la tarjeta de categoría en el portal'),
                        TextInput::make('orden')
                            ->label('Orden de visualización')
                            ->numeric()
                            ->default(0)
                            ->helperText('Orden en que aparece en el portal (menor = primero)'),
                    ])->columns(2),

                Section::make('Estado')
                    ->schema([
                        Toggle::make('activo')
                            ->label('Activo')
                            ->default(true)
                            ->helperText('Si está desactivado, la categoría no aparece en el portal público'),
                    ]),
            ]);
    }
}
