<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos de acceso')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nombre')
                            ->required(),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true),
                        TextInput::make('password')
                            ->label('Contraseña')
                            ->password()
                            ->required(fn (string $operation): bool => $operation === 'create')
                            ->dehydrated(fn (?string $state) => filled($state)),
                        DateTimePicker::make('email_verified_at')
                            ->label('Email verificado'),
                    ])->columns(2),

                Section::make('Datos de empresa')
                    ->schema([
                        TextInput::make('telefono')
                            ->label('Teléfono')
                            ->tel(),
                        TextInput::make('empresa')
                            ->label('Empresa / Razón Social'),
                        TextInput::make('nif_cif')
                            ->label('NIF/CIF'),
                    ])->columns(3),

                Section::make('Rol y estado')
                    ->schema([
                        Select::make('role_id')
                            ->label('Rol')
                            ->relationship('role', 'name')
                            ->searchable()
                            ->preload(),
                        Select::make('estado')
                            ->label('Estado')
                            ->options([
                                'activo' => 'Activo',
                                'pendiente' => 'Pendiente',
                                'inactivo' => 'Inactivo',
                            ])
                            ->default('activo')
                            ->required(),
                        Select::make('customer_id')
                            ->label('Cliente vinculado')
                            ->relationship('customer', 'nombre_comercial')
                            ->searchable()
                            ->preload()
                            ->helperText('Vincular con un cliente para asignar tarifa de precios'),
                    ])->columns(3),
            ]);
    }
}
