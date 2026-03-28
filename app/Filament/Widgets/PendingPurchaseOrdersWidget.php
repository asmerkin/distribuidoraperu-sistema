<?php

namespace App\Filament\Widgets;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use App\Models\PurchaseOrder;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Enums\FontFamily;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class PendingPurchaseOrdersWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->heading('Órdenes Pendientes de Recepción')
            ->query(
                PurchaseOrder::query()
                    ->with('supplier')
                    ->whereIn('status', [
                        PurchaseOrderStatus::Sent,
                        PurchaseOrderStatus::Confirmed,
                        PurchaseOrderStatus::PartiallyReceived,
                    ])
                    ->orderBy('order_date')
            )
            ->columns([
                TextColumn::make('po_number')
                    ->label('N° Orden')
                    ->fontFamily(FontFamily::Mono)
                    ->url(fn (PurchaseOrder $record): string => PurchaseOrderResource::getUrl('view', ['record' => $record])),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (PurchaseOrderStatus $state): string => $state->label())
                    ->color(fn (PurchaseOrderStatus $state): string => match ($state) {
                        PurchaseOrderStatus::Sent => 'info',
                        PurchaseOrderStatus::Confirmed => 'success',
                        PurchaseOrderStatus::PartiallyReceived => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date('d/m/Y'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->alignRight(),
            ])
            ->paginated(false);
    }
}
