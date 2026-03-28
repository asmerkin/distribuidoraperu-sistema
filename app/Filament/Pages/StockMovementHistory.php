<?php

namespace App\Filament\Pages;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Filament\Forms\Components\DatePicker;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementHistory extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-arrow-path';

    protected static string | \UnitEnum | null $navigationGroup = 'Inventario';

    protected static ?string $navigationLabel = 'Movimientos';

    protected static ?string $title = 'Historial de Movimientos';

    protected static ?string $slug = 'stock-movements';

    protected static ?int $navigationSort = 3;

    protected string $view = 'filament.pages.stock-movement-history';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                StockMovement::query()
                    ->with(['variant.product', 'location', 'user'])
            )
            ->columns([
                TextColumn::make('created_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable()
                    ->copyable(),

                TextColumn::make('variant.product.name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable()
                    ->wrap(),

                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

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

                SelectFilter::make('reason')
                    ->label('Motivo')
                    ->options(collect(StockMovementReason::cases())->mapWithKeys(
                        fn (StockMovementReason $r) => [$r->value => $r->label()]
                    )),

                SelectFilter::make('location_id')
                    ->label('Ubicación')
                    ->relationship('location', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('created_at')
                    ->label('Fecha')
                    ->form([
                        DatePicker::make('from')
                            ->label('Desde')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                        DatePicker::make('until')
                            ->label('Hasta')
                            ->native(false)
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['from'],
                                fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '>=', $date)
                            )
                            ->when(
                                $data['until'],
                                fn (Builder $q, string $date): Builder => $q->whereDate('created_at', '<=', $date)
                            );
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];

                        if ($data['from'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }

                        if ($data['until'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }

                        return $indicators;
                    }),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Aún no hay movimientos de stock registrados.')
            ->emptyStateIcon('heroicon-o-arrow-path');
    }
}
