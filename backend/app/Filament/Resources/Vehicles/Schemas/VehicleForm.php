<?php

namespace App\Filament\Resources\Vehicles\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class VehicleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->maxLength(100),
                TextInput::make('matricula')
                    ->maxLength(20),
                TextInput::make('capacidad_kg')
                    ->label('Capacidad (kg)')
                    ->numeric()
                    ->suffix('kg'),
                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
