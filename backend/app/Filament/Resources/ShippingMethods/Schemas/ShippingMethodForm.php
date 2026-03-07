<?php

namespace App\Filament\Resources\ShippingMethods\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class ShippingMethodForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100),
                Select::make('vehiculo_id')
                    ->label('Vehículo')
                    ->relationship('vehiculo', 'nombre')
                    ->preload(),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
