<?php

namespace App\Filament\Resources;

use App\Enums\UnitOfMeasure;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\InventoryRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\StockMovementsRelationManager;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Category;
use App\Models\Product;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-cube';

    protected static string | \UnitEnum | null $navigationGroup = 'Catálogo';

    protected static ?string $modelLabel = 'Producto';

    protected static ?string $pluralModelLabel = 'Productos';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255)
                ->columnSpan(2),

            Textarea::make('description')
                ->label('Descripción')
                ->rows(3)
                ->columnSpan(2),

            Select::make('category_id')
                ->label('Categoría')
                ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),

            Select::make('unit_of_measure')
                ->label('Unidad de Medida')
                ->options(collect(UnitOfMeasure::cases())->mapWithKeys(
                    fn (UnitOfMeasure $unit) => [$unit->value => $unit->label()]
                ))
                ->required(),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->columnSpan(2),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): ?string => $record->variants->pluck('sku')->join(', ') ?: null),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->placeholder('—'),

                TextColumn::make('unit_of_measure')
                    ->label('Unidad')
                    ->badge()
                    ->formatStateUsing(fn (UnitOfMeasure $state): string => $state->label())
                    ->color('gray'),

                TextColumn::make('variants_count')
                    ->label('Variantes')
                    ->counts('variants')
                    ->sortable()
                    ->alignCenter(),

                TextColumn::make('total_stock')
                    ->label('Stock Total')
                    ->getStateUsing(function (Product $record): int {
                        return $record->variants->sum(
                            fn ($variant) => $variant->inventoryLevels->sum('quantity')
                        );
                    })
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->defaultSort('name')
            ->recordAction('edit')
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['category', 'variants.inventoryLevels']);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
            InventoryRelationManager::class,
            StockMovementsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit'   => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'variants.sku', 'variants.barcode'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        $skus = $record->variants->pluck('sku')->join(', ');

        return array_filter([
            'SKU' => $skus ?: null,
            'Categoría' => $record->category?->name,
        ]);
    }
}
