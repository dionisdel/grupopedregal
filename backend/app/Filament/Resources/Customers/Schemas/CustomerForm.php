<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Información General')
                    ->schema([
                        TextInput::make('codigo')
                            ->label('Código')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50),
                        TextInput::make('nombre_comercial')
                            ->required()
                            ->maxLength(200),
                        TextInput::make('razon_social')
                            ->maxLength(200),
                    ])->columns(3),

                Section::make('Clasificación')
                    ->schema([
                        Select::make('tipo_cliente_id')
                            ->label('Tipo de Cliente')
                            ->relationship('tipoCliente', 'nombre')
                            ->required()
                            ->preload(),
                        Select::make('zona_envio_id')
                            ->label('Zona de Envío')
                            ->relationship('zonaEnvio', 'nombre')
                            ->preload(),
                    ])->columns(2),

                Section::make('Contacto')
                    ->schema([
                        TextInput::make('telefono')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(100),
                        TextInput::make('contacto_principal')
                            ->maxLength(100),
                    ])->columns(3),

                Section::make('Dirección')
                    ->schema([
                        Textarea::make('direccion')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),

                Section::make('Información Fiscal')
                    ->schema([
                        TextInput::make('nif_cif')
                            ->label('NIF/CIF')
                            ->maxLength(50),
                        Textarea::make('notas')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Toggle::make('activo')
                    ->default(true),
            ]);
    }
}
