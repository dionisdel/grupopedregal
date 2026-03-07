<?php

namespace App\Filament\Resources\ShippingZones;

use App\Filament\Resources\ShippingZones\Pages\ManageShippingZones;
use App\Filament\Resources\ShippingZones\Schemas\ShippingZoneForm;
use App\Filament\Resources\ShippingZones\Tables\ShippingZonesTable;
use App\Models\ShippingZone;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ShippingZoneResource extends Resource
{
    protected static ?string $model = ShippingZone::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedMapPin;
    protected static ?string $navigationLabel = 'Zonas de Envío';
    protected static ?string $modelLabel = 'Zona de Envío';
    protected static ?string $pluralModelLabel = 'Zonas de Envío';
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    public static function form(Schema $schema): Schema
    {
        return ShippingZoneForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShippingZonesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageShippingZones::route('/'),
        ];
    }
}
