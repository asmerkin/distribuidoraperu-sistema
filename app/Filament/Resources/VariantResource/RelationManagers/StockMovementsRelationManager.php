<?php

namespace App\Filament\Resources\VariantResource\RelationManagers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\Location;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Movimientos de stock';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['location', 'user'])->latest())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

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
                        StockMovementType::Transfer => 'info',
                    }),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->formatStateUsing(fn (StockMovementReason $state) => $state->label()),

                TextColumn::make('quantity')
                    ->label('Cantidad')
                    ->alignCenter()
                    ->formatStateUsing(function (StockMovement $record) {
                        $prefix = in_array($record->type, [StockMovementType::In, StockMovementType::Adjustment])
                            && $record->quantity > 0 ? '+' : '';

                        return $prefix . $record->quantity;
                    })
                    ->color(fn (StockMovement $record) => match (true) {
                        $record->quantity > 0 => 'success',
                        $record->quantity < 0 => 'danger',
                        default => null,
                    }),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('—')
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
                    ->options(Location::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
