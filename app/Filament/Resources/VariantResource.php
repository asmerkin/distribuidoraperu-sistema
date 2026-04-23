<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VariantResource\Pages;
use App\Filament\Resources\VariantResource\RelationManagers\InventoryLevelsRelationManager;
use App\Filament\Resources\VariantResource\RelationManagers\StockMovementsRelationManager;
use App\Filament\Resources\VariantResource\RelationManagers\SupplierVariantsRelationManager;
use App\Models\Variant;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Model;

class VariantResource extends Resource
{
    protected static ?string $model = Variant::class;

    protected static ?string $modelLabel = 'Variante';

    protected static ?string $pluralModelLabel = 'Variantes';

    protected static bool $shouldRegisterNavigation = false;

    public static function getIndexUrl(array $parameters = [], bool $isAbsolute = true, ?string $panel = null, ?Model $tenant = null, bool $shouldGuessMissingParameters = false): string
    {
        return ProductResource::getUrl('index');
    }

    public static function getRelations(): array
    {
        return [
            SupplierVariantsRelationManager::class,
            InventoryLevelsRelationManager::class,
            StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'view' => Pages\ViewVariant::route('/{record}'),
        ];
    }
}
