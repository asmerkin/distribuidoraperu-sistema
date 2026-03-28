<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PurchaseOrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'purchaseOrders';

    protected static ?string $title = 'Órdenes de compra';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color(fn ($state) => match ($state) {
                        \App\Enums\PurchaseOrderStatus::Draft => 'gray',
                        \App\Enums\PurchaseOrderStatus::Sent => 'info',
                        \App\Enums\PurchaseOrderStatus::Confirmed => 'success',
                        \App\Enums\PurchaseOrderStatus::Rejected => 'danger',
                        \App\Enums\PurchaseOrderStatus::PartiallyReceived => 'warning',
                        \App\Enums\PurchaseOrderStatus::Received => 'success',
                        \App\Enums\PurchaseOrderStatus::Cancelled => 'danger',
                    }),

                TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('order_date', 'desc')
            ->actions([
                Action::make('ver')
                    ->label('Ver / Editar')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->color('gray')
                    ->url(fn ($record) => PurchaseOrderResource::getUrl('edit', ['record' => $record]))
                    ->openUrlInNewTab(),
            ])
            ->headerActions([])
            ->paginated([10, 25, 50]);
    }
}
