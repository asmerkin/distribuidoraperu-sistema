<?php

namespace App\Filament\Pages;

use App\Models\Category;
use App\Models\InventoryLevel;
use App\Models\Location;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InventoryOverview extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Stock Actual';

    protected static ?string $title = 'Stock Actual';

    protected static ?string $slug = 'inventory';

    protected static ?int $navigationSort = 0;

    protected string $view = 'filament.pages.inventory-overview';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                InventoryLevel::query()
                    ->with([
                        'variant.product.category',
                        'location',
                    ])
            )
            ->columns([
                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable()
                    ->copyable()
                    ->weight('medium'),

                TextColumn::make('variant.product.name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('variant.name')
                    ->label('Variante')
                    ->sortable()
                    ->formatStateUsing(fn (string $state): string => $state === 'Default' ? '—' : $state)
                    ->placeholder('—'),

                TextColumn::make('variant.product.category.name')
                    ->label('Categoría')
                    ->sortable()
                    ->placeholder('—')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->sortable()
                    ->badge(),

                TextColumn::make('quantity')
                    ->label('Stock')
                    ->sortable()
                    ->alignCenter()
                    ->weight('bold')
                    ->color(fn (InventoryLevel $record): string => $record->isLowStock() ? 'danger' : 'success'),

                TextColumn::make('pending_po')
                    ->label('Pendiente PO')
                    ->getStateUsing(fn (InventoryLevel $record): int => $record->variant->pendingFromPurchaseOrders())
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : '—')
                    ->color(fn (InventoryLevel $record): string => $record->variant->pendingFromPurchaseOrders() > 0 ? 'info' : 'gray')
                    ->alignCenter(),

                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->sortable()
                    ->alignCenter()
                    ->color('gray'),

                TextColumn::make('value')
                    ->label('Valor (ARS)')
                    ->alignEnd()
                    ->getStateUsing(
                        fn (InventoryLevel $record): string => '$' . number_format(
                            $record->quantity * (float) ($record->variant->cost_price ?? 0),
                            2,
                            ',',
                            '.'
                        )
                    )
                    ->color('gray'),
            ])
            ->filters([
                SelectFilter::make('location_id')
                    ->label('Ubicación')
                    ->options(fn () => Location::where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),

                SelectFilter::make('category')
                    ->label('Categoría')
                    ->options(fn () => Category::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload()
                    ->query(fn (Builder $query, array $data): Builder => filled($data['value'])
                        ? $query->whereHas(
                            'variant.product',
                            fn (Builder $q) => $q->where('category_id', $data['value'])
                        )
                        : $query
                    ),

                TernaryFilter::make('low_stock')
                    ->label('Stock bajo')
                    ->trueLabel('Solo stock bajo')
                    ->falseLabel('Stock normal')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereColumn('quantity', '<=', 'min_stock'),
                        false: fn (Builder $query): Builder => $query->whereColumn('quantity', '>', 'min_stock'),
                    ),
            ])
            ->defaultSort('variant.sku')
            ->emptyStateHeading('Sin niveles de inventario')
            ->emptyStateDescription('No hay variantes con stock registrado.')
            ->emptyStateIcon('heroicon-o-archive-box');
    }
}
