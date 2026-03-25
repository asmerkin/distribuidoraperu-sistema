<?php

namespace App\Filament\Resources\PurchaseOrderResource\RelationManagers;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\PurchaseOrderReceipt;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'receipts';

    protected static ?string $title = 'Recepciones';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('received_at')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Recibió')
                    ->placeholder('—'),

                TextColumn::make('items_summary')
                    ->label('Detalle')
                    ->getStateUsing(function (PurchaseOrderReceipt $record) {
                        return $record->items->map(
                            fn ($item) => "{$item->variant->sku}: {$item->quantity_received} × \${$item->unit_cost}"
                        )->join(' | ');
                    })
                    ->wrap(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => match ($state) {
                        'completed' => 'Completada',
                        'voided' => 'Anulada',
                        default => $state,
                    })
                    ->color(fn (string $state) => match ($state) {
                        'completed' => 'success',
                        'voided' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->actions([
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn (PurchaseOrderReceipt $record) => "Recepción del " . $record->received_at->format('d/m/Y H:i'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist([
                        TextEntry::make('received_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                        TextEntry::make('user.name')->label('Recibió')->placeholder('—'),
                        TextEntry::make('status')->label('Estado')->badge()
                            ->formatStateUsing(fn (string $state) => $state === 'voided' ? 'Anulada' : 'Completada')
                            ->color(fn (string $state) => $state === 'voided' ? 'danger' : 'success'),
                        RepeatableEntry::make('items')
                            ->label('Productos recibidos')
                            ->schema([
                                TextEntry::make('variant.sku')->label('SKU'),
                                TextEntry::make('variant.product.name')->label('Producto'),
                                TextEntry::make('quantity_received')->label('Cantidad'),
                                TextEntry::make('unit_cost')->label('Precio')->money('ARS'),
                            ])
                            ->columns(4),
                    ]),

                Action::make('anular')
                    ->label('Anular')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn (PurchaseOrderReceipt $record) => $record->status === 'completed')
                    ->requiresConfirmation()
                    ->modalHeading('Anular recepción')
                    ->modalDescription('Se revertirán los movimientos de stock y las cantidades recibidas de la PO. Podrás hacer una nueva recepción con los datos correctos.')
                    ->action(function (PurchaseOrderReceipt $record) {
                        $po = $this->getOwnerRecord();
                        $po->load('location');
                        $inventory = app(InventoryService::class);

                        foreach ($record->items()->with('variant', 'purchaseOrderItem')->get() as $receiptItem) {
                            // Revert stock: create salida movement
                            $inventory->recordMovement(
                                variant: $receiptItem->variant,
                                location: $po->location,
                                type: StockMovementType::Out,
                                reason: StockMovementReason::StockCount,
                                quantity: $receiptItem->quantity_received,
                                reference: $po,
                                notes: "Anulación recepción OC {$po->po_number}",
                                userId: auth()->id(),
                            );

                            // Revert PO item quantity_received
                            $poItem = $receiptItem->purchaseOrderItem;
                            $poItem->decrement('quantity_received', $receiptItem->quantity_received);
                        }

                        // Mark receipt as voided
                        $record->update(['status' => 'voided']);

                        // Recalculate PO status
                        $po->refresh()->load('items');
                        $totalReceived = $po->items->sum('quantity_received');
                        $totalOrdered = $po->items->sum('quantity_ordered');

                        $newStatus = match (true) {
                            $totalReceived <= 0 => PurchaseOrderStatus::Sent,
                            $totalReceived >= $totalOrdered => PurchaseOrderStatus::Received,
                            default => PurchaseOrderStatus::PartiallyReceived,
                        };

                        $po->update(['status' => $newStatus]);

                        Notification::make()
                            ->title('Recepción anulada')
                            ->body("Se revirtió el stock. Podés hacer una nueva recepción.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('received_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->with('items.variant.product', 'user'))
            ->emptyStateHeading('Sin recepciones');
    }
}
