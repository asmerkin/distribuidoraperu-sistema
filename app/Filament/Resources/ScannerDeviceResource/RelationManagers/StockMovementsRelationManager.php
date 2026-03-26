<?php

namespace App\Filament\Resources\ScannerDeviceResource\RelationManagers;

use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Escaneos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('variant.product.name')
                    ->label('Producto')
                    ->limit(30)
                    ->searchable(),

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
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin escaneos')
            ->emptyStateDescription('Los movimientos realizados desde este dispositivo apareceran aca.');
    }
}
