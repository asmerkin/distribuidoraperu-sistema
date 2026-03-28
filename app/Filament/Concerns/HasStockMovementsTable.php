<?php

namespace App\Filament\Concerns;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;

trait HasStockMovementsTable
{
    protected function stockMovementColumns(bool $showSku = false, bool $showProduct = false): array
    {
        $columns = [
            TextColumn::make('created_at')
                ->label('Fecha')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
        ];

        if ($showSku) {
            $columns[] = TextColumn::make('variant.sku')
                ->label('SKU')
                ->sortable()
                ->searchable();
        }

        if ($showProduct) {
            $columns[] = TextColumn::make('variant.product.name')
                ->label('Producto')
                ->limit(30)
                ->searchable();
        }

        $columns[] = TextColumn::make('location.name')
            ->label('Ubicación')
            ->sortable();

        $columns[] = TextColumn::make('type')
            ->label('Tipo')
            ->badge()
            ->formatStateUsing(fn (StockMovementType $state) => $state->label())
            ->color(fn (StockMovementType $state) => match ($state) {
                StockMovementType::In => 'success',
                StockMovementType::Out => 'danger',
                StockMovementType::Adjustment => 'warning',
            });

        $columns[] = TextColumn::make('reason')
            ->label('Motivo')
            ->formatStateUsing(fn ($state) => $state->label());

        $columns[] = TextColumn::make('quantity')
            ->label('Cantidad')
            ->alignCenter()
            ->formatStateUsing(function (StockMovement $record) {
                $qty = $record->quantity;

                return match ($record->type) {
                    StockMovementType::In => "+{$qty}",
                    StockMovementType::Out => "-{$qty}",
                    default => ($qty >= 0 ? "+{$qty}" : (string) $qty),
                };
            })
            ->color(function (StockMovement $record) {
                return match ($record->type) {
                    StockMovementType::In => 'success',
                    StockMovementType::Out => 'danger',
                    default => $record->quantity >= 0 ? 'success' : 'danger',
                };
            });

        $columns[] = TextColumn::make('notes')
            ->label('Notas')
            ->limit(40)
            ->placeholder('—')
            ->toggleable();

        $columns[] = TextColumn::make('user.name')
            ->label('Usuario')
            ->placeholder('Sistema')
            ->toggleable(isToggledHiddenByDefault: true);

        return $columns;
    }

    protected function stockMovementFilters(): array
    {
        return [
            SelectFilter::make('type')
                ->label('Tipo')
                ->options(collect(StockMovementType::cases())->mapWithKeys(
                    fn (StockMovementType $t) => [$t->value => $t->label()]
                )),

            SelectFilter::make('location_id')
                ->label('Ubicación')
                ->relationship('location', 'name'),
        ];
    }
}
