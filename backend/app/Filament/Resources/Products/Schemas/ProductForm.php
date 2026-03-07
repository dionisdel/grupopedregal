<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Models\Product;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información Básica')
                    ->schema([
                        TextInput::make('sku')
                            ->label('SKU')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'PRD-' . str_pad(Product::max('id') + 1, 6, '0', STR_PAD_LEFT))
                            ->maxLength(50),
                        TextInput::make('nombre')
                            ->required()
                            ->maxLength(200)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Set $set) => $set('slug', Str::slug($state))),
                        TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(200),
                    ])->columns(3),

                Section::make('Clasificación')
                    ->schema([
                        Select::make('marca_id')
                            ->relationship('marca', 'nombre')
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                TextInput::make('nombre')->required(),
                            ]),
                        Select::make('categoria_id')
                            ->relationship('categoria', 'nombre')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Select::make('proveedor_principal_id')
                            ->label('Proveedor Principal')
                            ->relationship('proveedorPrincipal', 'nombre_comercial')
                            ->searchable()
                            ->preload(),
                    ])->columns(3),


                Section::make('Unidades')
                    ->schema([
                        Select::make('unidad_base_id')
                            ->label('Unidad de Venta')
                            ->relationship('unidadBase', 'nombre')
                            ->required()
                            ->preload(),
                        Select::make('unidad_compra_id')
                            ->label('Unidad de Compra')
                            ->relationship('unidadCompra', 'nombre')
                            ->preload(),
                    ])->columns(2),

                Section::make('Descripciones')
                    ->schema([
                        Textarea::make('descripcion')
                            ->rows(2),
                        Textarea::make('descripcion_corta_web')
                            ->rows(2),
                        RichEditor::make('descripcion_larga_web')
                            ->columnSpanFull(),
                    ]),

                Section::make('Configuración')
                    ->schema([
                        FileUpload::make('imagen_principal_url')
                            ->label('Imagen Principal')
                            ->image(),
                        Toggle::make('activo')
                            ->default(true),
                        Toggle::make('visible_web')
                            ->label('Visible en Web'),
                        Toggle::make('destacado'),
                    ])->columns(2),
            ]);
    }
}
