<?php

namespace App\Filament\Resources;

use App\Enums\UnitOfMeasure;
use App\Filament\Resources\ProductResource\Pages;
use App\Filament\Resources\ProductResource\RelationManagers\VariantsRelationManager;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Services\ProductMergeService;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cube';

    protected static string|\UnitEnum|null $navigationGroup = 'Catálogo';

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

            FileUpload::make('images')
                ->label('Imágenes')
                ->disk('public')
                ->directory('product-images')
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->maxFiles(10)
                ->maxSize(5120)
                ->panelLayout('grid')
                ->columnSpan(2),

            Select::make('category_id')
                ->label('Categoría')
                ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->nullable(),

            Select::make('brand_id')
                ->label('Marca')
                ->relationship('brand', 'name')
                ->searchable()
                ->preload()
                ->nullable()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nombre')
                        ->required()
                        ->unique(Brand::class, 'name'),
                ]),

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
                ImageColumn::make('images')
                    ->label('Foto')
                    ->disk('public')
                    ->limit(1)
                    ->square()
                    ->size(40),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Product $record): ?string => $record->variants->pluck('sku')->join(', ') ?: null),

                TextColumn::make('brand.name')
                    ->label('Marca')
                    ->sortable()
                    ->searchable()
                    ->placeholder('—')
                    ->toggleable(),

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
            ->filters([
                SelectFilter::make('brand_id')
                    ->label('Marca')
                    ->relationship('brand', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('category_id')
                    ->label('Categoría')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    BulkAction::make('change_brand')
                        ->label('Cambiar marca')
                        ->icon('heroicon-o-bookmark')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Cambiar marca de productos seleccionados')
                        ->modalSubmitActionLabel('Guardar')
                        ->form([
                            Select::make('brand_id')
                                ->label('Nueva marca')
                                ->options(fn () => Brand::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            Product::whereIn('id', $records->pluck('id'))->update(['brand_id' => $data['brand_id']]);
                            Notification::make()
                                ->success()
                                ->title('Marca actualizada')
                                ->body("{$records->count()} producto(s) actualizados.")
                                ->send();
                        }),
                    BulkAction::make('change_category')
                        ->label('Cambiar categoría')
                        ->icon('heroicon-o-tag')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Cambiar categoría de productos seleccionados')
                        ->modalSubmitActionLabel('Guardar')
                        ->form([
                            Select::make('category_id')
                                ->label('Nueva categoría')
                                ->options(fn () => Category::query()->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->nullable(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            Product::whereIn('id', $records->pluck('id'))->update(['category_id' => $data['category_id']]);
                            Notification::make()
                                ->success()
                                ->title('Categoría actualizada')
                                ->body("{$records->count()} producto(s) actualizados.")
                                ->send();
                        }),
                    BulkAction::make('merge')
                        ->label('Fusionar seleccionados')
                        ->icon('heroicon-o-arrows-pointing-in')
                        ->color('warning')
                        ->deselectRecordsAfterCompletion()
                        ->modalHeading('Fusionar productos seleccionados')
                        ->modalDescription('Elegí qué producto queda como canónico. El resto se fusiona en ese (sus variantes se mueven y los productos se eliminan).')
                        ->modalSubmitActionLabel('Fusionar')
                        ->modalWidth('lg')
                        ->form(fn (Collection $records) => [
                            Select::make('canonical_id')
                                ->label('Producto canónico (se conserva)')
                                ->options(
                                    $records
                                        ->mapWithKeys(fn (Product $p) => [
                                            $p->id => $p->name.' ('.$p->variants->count().' variante(s))',
                                        ])
                                        ->all()
                                )
                                ->required(),
                        ])
                        ->action(function (array $data, Collection $records): void {
                            if ($records->count() < 2) {
                                Notification::make()
                                    ->warning()
                                    ->title('Seleccioná al menos 2 productos')
                                    ->send();

                                return;
                            }

                            /** @var Product $target */
                            $target = $records->firstWhere('id', $data['canonical_id']);
                            $sources = $records->reject(fn (Product $p) => $p->id === $target->id);
                            $totalMoved = 0;
                            $totalRenamed = 0;

                            DB::transaction(function () use ($sources, $target, &$totalMoved, &$totalRenamed): void {
                                $service = app(ProductMergeService::class);
                                foreach ($sources as $source) {
                                    $result = $service->merge($source, $target);
                                    $totalMoved += $result['variants_moved'];
                                    $totalRenamed += $result['variants_renamed'];
                                }
                            });

                            $body = sprintf(
                                'Se fusionaron %d producto(s) en «%s». %d variante(s) movida(s).',
                                $sources->count(),
                                $target->name,
                                $totalMoved,
                            );
                            if ($totalRenamed > 0) {
                                $body .= " {$totalRenamed} renombrada(s) desde «Default».";
                            }

                            Notification::make()
                                ->success()
                                ->title('Productos fusionados')
                                ->body($body)
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['brand', 'category', 'variants.inventoryLevels']);
    }

    public static function getRelations(): array
    {
        return [
            VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'brand.name', 'variants.sku', 'variants.barcode'];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        $skus = $record->variants->pluck('sku')->join(', ');

        return array_filter([
            'SKU' => $skus ?: null,
            'Marca' => $record->brand?->name,
            'Categoría' => $record->category?->name,
        ]);
    }
}
