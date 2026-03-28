<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Movimientos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StockMovement::query()
                ->whereIn('variant_id', $this->getOwnerRecord()->variants()->pluck('id'))
                ->with(['variant', 'location', 'user'])
                ->latest()
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (StockMovementType $state) => $state->label())
                    ->color(fn (StockMovementType $state) => match ($state) {
                        StockMovementType::In => 'success',
                        StockMovementType::Out => 'danger',
                        StockMovementType::Adjustment => 'warning',
                    }),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->formatStateUsing(fn ($state) => $state->label()),

                TextColumn::make('quantity')
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
                    }),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('Sistema')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('Tipo')
                    ->options(collect(StockMovementType::cases())->mapWithKeys(
                        fn (StockMovementType $t) => [$t->value => $t->label()]
                    )),

                SelectFilter::make('location_id')
                    ->label('Ubicación')
                    ->relationship('location', 'name'),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Los movimientos de stock se registran automáticamente.');
    }
}
