<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\SupplierResource;
use App\Models\PurchaseOrderReceipt;
use App\Models\PurchaseOrderReceiptItem;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewPurchaseOrder extends ViewRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        return "Orden de Compra: {$this->getRecord()->po_number}";
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            EditAction::make()
                ->visible(fn () => $record->status === PurchaseOrderStatus::Borrador),

            Action::make('send')
                ->label('Enviar al proveedor')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $record->status === PurchaseOrderStatus::Borrador)
                ->requiresConfirmation()
                ->modalHeading('Enviar orden de compra')
                ->modalDescription("Se enviará la orden {$record->po_number} al proveedor {$record->supplier->name}.")
                ->modalSubmitActionLabel('Enviar')
                ->action(function () use ($record) {
                    $record->update([
                        'status' => PurchaseOrderStatus::Enviada,
                        'sent_at' => now(),
                    ]);

                    Notification::make()
                        ->title('Orden enviada')
                        ->body("La orden {$record->po_number} fue marcada como enviada.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'sent_at']);
                }),

            Action::make('receive')
                ->label('Recibir mercadería')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn () => in_array($record->status, [
                    PurchaseOrderStatus::Enviada,
                    PurchaseOrderStatus::RecibidaParcial,
                ]))
                ->modalHeading('Recibir mercadería')
                ->modalDescription("Orden {$record->po_number} — Ingresá las cantidades recibidas.")
                ->modalSubmitActionLabel('Confirmar recepción')
                ->modalWidth('lg')
                ->form(fn () => $this->buildReceiveFormFields())
                ->action(fn (array $data) => $this->processReception($data)),

            Action::make('ver_proveedor')
                ->label('Ver proveedor')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->url(fn () => SupplierResource::getUrl('view', ['record' => $record->supplier_id])),

            Action::make('eliminar')
                ->label('Eliminar')
                ->color('danger')
                ->link()
                ->visible(fn () => $record->status === PurchaseOrderStatus::Borrador)
                ->requiresConfirmation()
                ->modalHeading('Eliminar orden de compra')
                ->modalDescription("¿Eliminar la orden {$record->po_number}?")
                ->action(function () use ($record) {
                    $record->items()->delete();
                    $record->delete();

                    Notification::make()
                        ->title('Orden eliminada')
                        ->success()
                        ->send();

                    $this->redirect(PurchaseOrderResource::getUrl());
                }),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema->components([
            Section::make('Detalle de la orden')
                ->schema([
                    TextEntry::make('po_number')->label('N° Orden')->weight(FontWeight::Bold),
                    TextEntry::make('supplier.name')->label('Proveedor'),
                    TextEntry::make('status')
                        ->label('Estado')
                        ->badge()
                        ->formatStateUsing(fn (PurchaseOrderStatus $state) => $state->label())
                        ->color(fn (PurchaseOrderStatus $state) => match ($state) {
                            PurchaseOrderStatus::Borrador => 'gray',
                            PurchaseOrderStatus::Enviada => 'info',
                            PurchaseOrderStatus::RecibidaParcial => 'warning',
                            PurchaseOrderStatus::Recibida => 'success',
                            PurchaseOrderStatus::Cancelada => 'danger',
                        }),
                    TextEntry::make('location.name')->label('Destino'),
                    TextEntry::make('order_date')->label('Fecha de orden')->date('d/m/Y'),
                    TextEntry::make('expected_date')->label('Entrega estimada')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('total')->label('Total')->money('ARS')->weight(FontWeight::Bold),
                    TextEntry::make('sent_at')->label('Enviada')->dateTime('d/m/Y H:i')->placeholder('No enviada'),
                    TextEntry::make('user.name')->label('Creada por')->placeholder('—'),
                    TextEntry::make('notes')->label('Notas internas')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('notes_for_supplier')->label('Notas para el proveedor')->placeholder('—')->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Productos')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('variant.sku')->label('SKU'),
                            TextEntry::make('variant.product.name')->label('Producto'),
                            TextEntry::make('quantity_ordered')->label('Pedido'),
                            TextEntry::make('quantity_received')->label('Recibido'),
                            TextEntry::make('unit_cost')->label('Costo unit.')->money('ARS'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('ARS'),
                        ])
                        ->columns(6),
                ]),
            Section::make('Recepciones')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('receipts')
                        ->label('')
                        ->schema([
                            TextEntry::make('received_at')->label('Fecha')->dateTime('d/m/Y H:i'),
                            TextEntry::make('user.name')->label('Recibió')->placeholder('—'),
                            \Filament\Infolists\Components\RepeatableEntry::make('items')
                                ->label('Detalle')
                                ->schema([
                                    TextEntry::make('variant.sku')->label('SKU'),
                                    TextEntry::make('quantity_received')->label('Cantidad'),
                                    TextEntry::make('unit_cost')->label('Precio')->money('ARS'),
                                ])
                                ->columns(3),
                        ]),
                ])
                ->visible(fn () => $record->receipts()->exists()),
        ])->columns(1);
    }

    private function buildReceiveFormFields(): array
    {
        $record = $this->getRecord();
        $record->load('items.variant.product');

        $fields = [];

        foreach ($record->items as $item) {
            $pending = $item->quantity_ordered - $item->quantity_received;
            if ($pending <= 0) {
                continue;
            }

            $label = "[{$item->variant->sku}] {$item->variant->product->name}";
            if ($item->variant->name !== 'Default') {
                $label .= " — {$item->variant->name}";
            }

            $fields[] = \Filament\Schemas\Components\Section::make($label)
                ->description("Pedido: {$item->quantity_ordered} | Recibido: {$item->quantity_received} | Pendiente: {$pending}")
                ->schema([
                    \Filament\Schemas\Components\Grid::make(2)->schema([
                        TextInput::make("qty_{$item->id}")
                            ->label('Cantidad a recibir')
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue($pending)
                            ->suffix("/ {$pending}"),

                        TextInput::make("price_{$item->id}")
                            ->label('Precio unitario')
                            ->numeric()
                            ->prefix('$')
                            ->default($item->unit_cost)
                            ->minValue(0),
                    ]),
                ])
                ->compact();
        }

        if (empty($fields)) {
            $fields[] = \Filament\Schemas\Components\Section::make('Todo recibido')
                ->description('No hay ítems pendientes de recepción.');
        }

        return $fields;
    }

    private function processReception(array $data): void
    {
        $record = $this->getRecord();
        $record->load('items.variant', 'location');
        $inventory = app(InventoryService::class);

        $anyReceived = false;
        $receiptItems = [];

        foreach ($record->items as $item) {
            $qty = (int) ($data["qty_{$item->id}"] ?? 0);
            if ($qty <= 0) {
                continue;
            }

            $pending = $item->quantity_ordered - $item->quantity_received;
            $qty = min($qty, $pending);
            if ($qty <= 0) {
                continue;
            }

            $confirmedPrice = (float) ($data["price_{$item->id}"] ?? $item->unit_cost);
            $anyReceived = true;

            // Update PO item
            $item->increment('quantity_received', $qty);

            // Update price if different
            if ($confirmedPrice != (float) $item->unit_cost) {
                $item->update([
                    'unit_cost' => $confirmedPrice,
                    'subtotal' => $item->quantity_ordered * $confirmedPrice,
                ]);
            }

            // Update variant cost price
            $item->variant->update(['cost_price' => $confirmedPrice]);

            // Create stock movement
            $inventory->recordMovement(
                variant: $item->variant,
                location: $record->location,
                type: StockMovementType::Entrada,
                reason: StockMovementReason::Compra,
                quantity: $qty,
                reference: $record,
                notes: "Recepción OC {$record->po_number}",
                userId: auth()->id(),
            );

            // Collect for receipt record
            $receiptItems[] = [
                'purchase_order_item_id' => $item->id,
                'variant_id' => $item->variant->id,
                'quantity_received' => $qty,
                'unit_cost' => $confirmedPrice,
            ];
        }

        if (! $anyReceived) {
            Notification::make()
                ->title('Sin cambios')
                ->body('No se ingresó ninguna cantidad a recibir.')
                ->warning()
                ->send();

            return;
        }

        // Create receipt record
        $receipt = PurchaseOrderReceipt::create([
            'purchase_order_id' => $record->id,
            'received_at' => now(),
            'user_id' => auth()->id(),
        ]);

        foreach ($receiptItems as $receiptItem) {
            PurchaseOrderReceiptItem::create([
                'purchase_order_receipt_id' => $receipt->id,
                ...$receiptItem,
            ]);
        }

        // Update PO status and total
        $record->refresh()->load('items');
        $allReceived = $record->items->every(
            fn ($item) => $item->quantity_received >= $item->quantity_ordered,
        );

        $record->update([
            'status' => $allReceived
                ? PurchaseOrderStatus::Recibida
                : PurchaseOrderStatus::RecibidaParcial,
            'total' => $record->items->sum('subtotal'),
        ]);

        $statusLabel = $allReceived ? 'completa' : 'parcial';

        Notification::make()
            ->title("Recepción {$statusLabel}")
            ->body("Mercadería registrada para OC {$record->po_number}.")
            ->success()
            ->send();

        $this->refreshFormData(['status']);
    }
}
