<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Variant;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InventoryRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Inventario';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $locations = Location::where('is_active', true)->orderBy('name')->get();

        $columns = [
            TextColumn::make('sku')
                ->label('SKU')
                ->sortable()
                ->searchable(),

            TextColumn::make('name')
                ->label('Variante')
                ->sortable()
                ->formatStateUsing(fn (Variant $record) => $record->name === 'Default' ? '—' : $record->name),
        ];

        // One column per location
        foreach ($locations as $location) {
            $columns[] = TextColumn::make("stock_{$location->id}")
                ->label($location->name)
                ->getStateUsing(function (Variant $record) use ($location) {
                    $level = $record->inventoryLevels->firstWhere('location_id', $location->id);

                    return $level?->quantity ?? '—';
                })
                ->alignCenter()
                ->color(function (Variant $record) use ($location) {
                    $level = $record->inventoryLevels->firstWhere('location_id', $location->id);
                    if ($level && $level->isLowStock()) {
                        return 'danger';
                    }

                    return null;
                });
        }

        // Total column
        $columns[] = TextColumn::make('total_stock')
            ->label('Total')
            ->getStateUsing(fn (Variant $record) => $record->inventoryLevels->sum('quantity'))
            ->alignCenter()
            ->weight('bold');

        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('inventoryLevels'))
            ->columns($columns)
            ->actions([
                Action::make('adjust')
                    ->label('Ajustar')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->modalHeading(fn (Variant $record) => "Ajustar stock — {$record->sku}")
                    ->modalDescription(fn (Variant $record) => $record->name !== 'Default'
                        ? "{$record->product->name} — {$record->name}"
                        : $record->product->name)
                    ->modalSubmitActionLabel('Aplicar ajustes')
                    ->modalWidth('lg')
                    ->form(function (Variant $record) use ($locations): array {
                        $fields = [];
                        $levels = $record->inventoryLevels->keyBy('location_id');

                        foreach ($locations as $location) {
                            $level = $levels->get($location->id);

                            $hasStock = $level !== null;
                            $currentQty = $level?->quantity ?? 0;

                            $currentMinStock = $level?->min_stock ?? 0;

                            $fields[] = Section::make($location->name)
                                ->description(fn ($get) => $get("enabled_{$location->id}")
                                    ? "Stock actual: {$currentQty}"
                                    : 'No disponible en esta ubicación')
                                ->schema([
                                    Toggle::make("enabled_{$location->id}")
                                        ->label('Disponible')
                                        ->default($hasStock)
                                        ->reactive(),

                                    Grid::make(2)->schema([
                                        TextInput::make("qty_{$location->id}")
                                            ->label('Cantidad')
                                            ->integer()
                                            ->default($currentQty)
                                            ->minValue(0),

                                        TextInput::make("min_stock_{$location->id}")
                                            ->label('Stock mínimo')
                                            ->integer()
                                            ->default($currentMinStock)
                                            ->minValue(0),
                                    ])->visible(fn ($get) => $get("enabled_{$location->id}")),
                                ])
                                ->compact();
                        }

                        $fields[] = Textarea::make('notes')
                            ->label('Motivo del ajuste')
                            ->rows(2);

                        return $fields;
                    })
                    ->action(function (Variant $record, array $data) use ($locations) {
                        $inventory = app(InventoryService::class);
                        $adjustments = 0;
                        $levels = $record->inventoryLevels()->get()->keyBy('location_id');

                        foreach ($locations as $location) {
                            $enabled = (bool) ($data["enabled_{$location->id}"] ?? false);
                            $level = $levels->get($location->id);

                            $currentQty = $level?->quantity ?? 0;

                            if (! $enabled) {
                                if ($level) {
                                    if ($level->quantity > 0) {
                                        $inventory->recordMovement(
                                            variant: $record,
                                            location: $location,
                                            type: StockMovementType::Out,
                                            reason: StockMovementReason::Shrinkage,
                                            quantity: $level->quantity,
                                            notes: $data['notes'] ?? 'Ubicación desactivada para esta variante',
                                            userId: auth()->id(),
                                        );
                                    }
                                    $adjustments++;
                                    $level->update(['min_stock' => 0]);
                                }

                                continue;
                            }

                            $newQty = (int) ($data["qty_{$location->id}"] ?? $currentQty);
                            $newMinStock = (int) ($data["min_stock_{$location->id}"] ?? $level?->min_stock ?? 0);
                            $diff = $newQty - $currentQty;
                            $minStockChanged = $level && $newMinStock !== ($level->min_stock ?? 0);

                            if ($diff === 0 && $level && ! $minStockChanged) {
                                continue;
                            }

                            if (! $level && $newQty === 0) {
                                InventoryLevel::create([
                                    'variant_id' => $record->id,
                                    'location_id' => $location->id,
                                    'quantity' => 0,
                                    'min_stock' => $newMinStock,
                                ]);
                                $adjustments++;

                                continue;
                            }

                            if ($diff !== 0) {
                                $inventory->recordMovement(
                                    variant: $record,
                                    location: $location,
                                    type: StockMovementType::Adjustment,
                                    reason: StockMovementReason::StockCount,
                                    quantity: $diff,
                                    notes: $data['notes'] ?? null,
                                    userId: auth()->id(),
                                );
                            }

                            if ($diff !== 0 || $minStockChanged) {
                                $adjustments++;
                                if ($diff !== 0) {
                                    $level = InventoryLevel::where('variant_id', $record->id)
                                        ->where('location_id', $location->id)
                                        ->first();
                                }
                                $level?->update(['min_stock' => $newMinStock]);
                            }
                        }

                        if ($adjustments === 0) {
                            Notification::make()
                                ->title('Sin cambios')
                                ->body('No se realizaron ajustes.')
                                ->info()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Inventario actualizado')
                            ->body("{$adjustments} ubicación(es) actualizada(s).")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('sku')
            ->emptyStateHeading('Sin variantes')
            ->emptyStateDescription('Creá variantes en la tab "Variantes" primero.');
    }
}
