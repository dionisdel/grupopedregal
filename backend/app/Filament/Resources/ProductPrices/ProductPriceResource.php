<?php

namespace App\Filament\Resources\ProductPrices;

use App\Filament\Resources\ProductPrices\Pages;
use App\Filament\Resources\ProductPrices\Schemas\ProductPriceForm;
use App\Filament\Resources\ProductPrices\Tables\ProductPricesTable;
use App\Models\ProductPrice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ProductPriceResource extends Resource
{
    protected static ?string $model = ProductPrice::class;

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;
    
    protected static ?string $navigationLabel = 'Precios de Venta';
    
    protected static ?string $modelLabel = 'Precio de Venta';
    
    protected static ?string $pluralModelLabel = 'Precios de Venta';
    
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return ProductPriceForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductPricesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductPrices::route('/'),
            'create' => Pages\CreateProductPrice::route('/create'),
            'edit' => Pages\EditProductPrice::route('/{record}/edit'),
        ];
    }
}
