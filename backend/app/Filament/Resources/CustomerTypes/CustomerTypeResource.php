<?php

namespace App\Filament\Resources\CustomerTypes;

use App\Filament\Resources\CustomerTypes\Pages\ManageCustomerTypes;
use App\Filament\Resources\CustomerTypes\Schemas\CustomerTypeForm;
use App\Filament\Resources\CustomerTypes\Tables\CustomerTypesTable;
use App\Models\CustomerType;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CustomerTypeResource extends Resource
{
    protected static ?string $model = CustomerType::class;
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;
    protected static ?string $navigationLabel = 'Tipos de Cliente';
    protected static ?string $modelLabel = 'Tipo de Cliente';
    protected static ?string $pluralModelLabel = 'Tipos de Cliente';
    protected static ?string $recordTitleAttribute = 'nombre';
    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    public static function form(Schema $schema): Schema
    {
        return CustomerTypeForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CustomerTypesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageCustomerTypes::route('/'),
        ];
    }
}
