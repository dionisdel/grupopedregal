<?php

namespace App\Filament\Resources\ShippingMethods;

use App\Filament\Resources\ShippingMethods\Pages\ManageShippingMethods;
use App\Filament\Resources\ShippingMethods\Schemas\ShippingMethodForm;
use App\Filament\Resources\ShippingMethods\Tables\ShippingMethodsTable;
use App\Models\ShippingMethod;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingMethodResource extends Resource
{
    protected static ?string $model = ShippingMethod::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedTruck;
    protected static ?string $navigationLabel = 'Métodos de Envío';
    protected static ?string $modelLabel = 'Método de Envío';
    protected static ?string $pluralModelLabel = 'Métodos de Envío';
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    public static function form(Schema $schema): Schema
    {
        return ShippingMethodForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingMethodsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageShippingMethods::route('/'),
        ];
    }
}
