<?php

namespace App\Filament\Resources\ProductPrices\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ProductPriceForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make('Información del Precio')
                ->schema([
                    Select::make('producto_id')
                        ->label('Producto')
                        ->relationship('producto', 'nombre')
                        ->searchable()
                        ->preload()
                        ->required(),
                    
                    Select::make('tipo_cliente_id')
                        ->label('Tipo de Cliente')
                        ->relationship('tipoCliente', 'nombre')
                        ->required(),
                    
                    TextInput::make('precio_base')
                        ->label('Precio Base')
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01)
                        ->required(),
                    
                    TextInput::make('descuento_porcentaje')
                        ->label('Descuento (%)')
                        ->numeric()
                        ->suffix('%')
                        ->step(0.01)
                        ->default(0),
                    
                    TextInput::make('margen_porcentaje')
                        ->label('Margen (%)')
                        ->numeric()
                        ->suffix('%')
                        ->step(0.01),
                    
                    TextInput::make('margen_absoluto')
                        ->label('Margen Absoluto')
                        ->numeric()
                        ->prefix('€')
                        ->step(0.01),
                ])
                ->columns(2),
            
            Section::make('Vigencia')
                ->schema([
                    DatePicker::make('fecha_vigencia_desde')
                        ->label('Vigente Desde')
                        ->required()
                        ->default(now()),
                    
                    DatePicker::make('fecha_vigencia_hasta')
                        ->label('Vigente Hasta'),
                    
                    TextInput::make('mes_tarifa')
                        ->label('Mes Tarifa')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(12),
                    
                    TextInput::make('año_tarifa')
                        ->label('Año Tarifa')
                        ->numeric()
                        ->minValue(2020)
                        ->maxValue(2030),
                    
                    Toggle::make('activo')
                        ->label('Activo')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }
}
