<?php

namespace App\Filament\Concerns;

use App\Models\SupplierVariant;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;

trait HasSupplierVariantsTable
{
    protected function supplierVariantFormFields(): array
    {
        return [
            TextInput::make('supplier_code')
                ->label('Código del proveedor')
                ->required()
                ->maxLength(255),

            TextInput::make('cost_price')
                ->label('Precio por unidad de compra')
                ->numeric()
                ->prefix('$')
                ->required(),

            TextInput::make('purchase_unit')
                ->label('Unidad de compra')
                ->placeholder('Ej: Caja x12, Pack x6')
                ->maxLength(255),

            TextInput::make('purchase_unit_qty')
                ->label('Uds. base por unidad de compra')
                ->integer()
                ->default(1)
                ->minValue(1)
                ->required()
                ->helperText('Cuántas unidades base contiene cada unidad de compra'),

            Toggle::make('is_default')
                ->label('Proveedor predeterminado')
                ->columnSpan(2),
        ];
    }

    protected function supplierVariantColumns(bool $showSupplier = false, bool $showVariant = false): array
    {
        $columns = [];

        if ($showSupplier) {
            $columns[] = TextColumn::make('supplier.name')
                ->label('Proveedor')
                ->sortable()
                ->searchable();
        }

        if ($showVariant) {
            $columns[] = TextColumn::make('variant.sku')
                ->label('SKU')
                ->sortable()
                ->searchable();

            $columns[] = TextColumn::make('variant.product.name')
                ->label('Producto')
                ->sortable()
                ->searchable();

            $columns[] = TextColumn::make('variant.name')
                ->label('Variante')
                ->sortable();
        }

        $columns[] = TextColumn::make('supplier_code')
            ->label('Cód. Proveedor')
            ->placeholder('—')
            ->searchable();

        $columns[] = TextColumn::make('purchase_unit')
            ->label('Unidad compra')
            ->placeholder('—');

        $columns[] = TextColumn::make('cost_price')
            ->label('Precio')
            ->money('ARS')
            ->sortable();

        $columns[] = IconColumn::make('is_default')
            ->label('Default')
            ->boolean()
            ->alignCenter();

        return $columns;
    }

    protected function priceHistoryAction(): Action
    {
        return Action::make('price_history')
            ->label('Historial')
            ->icon('heroicon-o-clock')
            ->color('gray')
            ->modalHeading('Historial de precios')
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('Cerrar')
            ->infolist(function (SupplierVariant $record): array {
                $logs = $record->priceLogs()->with('user')->limit(20)->get();

                if ($logs->isEmpty()) {
                    return [
                        TextEntry::make('empty')
                            ->label('')
                            ->default('Sin cambios de precio registrados.')
                            ->columnSpanFull(),
                    ];
                }

                return [
                    RepeatableEntry::make('priceLogs')
                        ->label('')
                        ->schema([
                            TextEntry::make('changed_at')
                                ->label('Fecha')
                                ->dateTime('d/m/Y H:i'),
                            TextEntry::make('old_price')
                                ->label('Anterior')
                                ->money('ARS'),
                            TextEntry::make('new_price')
                                ->label('Nuevo')
                                ->money('ARS'),
                            TextEntry::make('variation')
                                ->label('Variación')
                                ->state(function ($record) {
                                    $old = (float) $record->old_price;
                                    if ($old <= 0) {
                                        return '—';
                                    }
                                    $diff = (float) $record->new_price - $old;
                                    $pct = round(($diff / $old) * 100, 1);
                                    $sign = $pct > 0 ? '+' : '';
                                    return "{$sign}{$pct}%";
                                })
                                ->badge()
                                ->color(fn ($record) => (float) $record->new_price > (float) $record->old_price
                                    ? 'danger'
                                    : ((float) $record->new_price < (float) $record->old_price ? 'success' : 'gray')),
                            TextEntry::make('purchase_unit_qty')
                                ->label('Uds./compra')
                                ->formatStateUsing(fn ($state) => $state > 1 ? "×{$state}" : '—'),
                            TextEntry::make('user.name')
                                ->label('Usuario')
                                ->placeholder('Sistema'),
                        ])
                        ->columns(6),
                ];
            });
    }
}
