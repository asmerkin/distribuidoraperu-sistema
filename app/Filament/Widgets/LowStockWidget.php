<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\ProductResource;
use App\Models\InventoryLevel;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class LowStockWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'half';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Stock Bajo')
            ->query(
                InventoryLevel::query()
                    ->with(['variant.product', 'location'])
                    ->whereColumn('quantity', '<=', 'min_stock')
                    ->orderBy('quantity')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->font('mono'),

                TextColumn::make('variant.product.name')
                    ->label('Producto')
                    ->searchable()
                    ->url(fn (InventoryLevel $record): string => ProductResource::getUrl('edit', ['record' => $record->variant->product_id]))
                    ->color('primary'),

                TextColumn::make('location.name')
                    ->label('Ubicación'),

                TextColumn::make('quantity')
                    ->label('Stock')
                    ->color('danger')
                    ->badge(),

                TextColumn::make('min_stock')
                    ->label('Mínimo')
                    ->color('gray'),
            ])
            ->paginated(false);
    }
}
