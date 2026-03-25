<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Filament\Resources\PurchaseOrderResource;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('send')
                ->label('Enviar al proveedor')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->getRecord()->status === PurchaseOrderStatus::Borrador)
                ->requiresConfirmation()
                ->modalHeading('Enviar orden de compra')
                ->modalDescription(fn () => "Se enviará la orden {$this->getRecord()->po_number} al proveedor {$this->getRecord()->supplier->name}.")
                ->modalSubmitActionLabel('Enviar')
                ->action(function () {
                    $record = $this->getRecord();

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
                ->visible(fn () => in_array($this->getRecord()->status, [
                    PurchaseOrderStatus::Enviada,
                    PurchaseOrderStatus::RecibidaParcial,
                ]))
                ->modalHeading('Recibir mercadería')
                ->modalDescription(fn () => "Orden {$this->getRecord()->po_number} — Ingresá las cantidades recibidas.")
                ->modalSubmitActionLabel('Confirmar recepción')
                ->modalWidth('lg')
                ->form(fn () => $this->buildReceiveFormFields())
                ->action(fn (array $data) => $this->processReception($data)),

            DeleteAction::make()
                ->disabled(fn () => $this->getRecord()->status !== PurchaseOrderStatus::Borrador),
        ];
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

            $fields[] = Section::make($label)
                ->description("Pedido: {$item->quantity_ordered} | Recibido: {$item->quantity_received} | Pendiente: {$pending}")
                ->schema([
                    TextInput::make("qty_{$item->id}")
                        ->label('Recibir ahora')
                        ->integer()
                        ->default(0)
                        ->minValue(0)
                        ->maxValue($pending)
                        ->suffix("/ {$pending} pendientes"),
                ])
                ->compact();
        }

        if (empty($fields)) {
            $fields[] = Section::make('Todo recibido')
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

            $anyReceived = true;

            $item->increment('quantity_received', $qty);

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

            $item->variant->update(['cost_price' => $item->unit_cost]);
        }

        if (! $anyReceived) {
            Notification::make()
                ->title('Sin cambios')
                ->body('No se ingresó ninguna cantidad a recibir.')
                ->warning()
                ->send();

            return;
        }

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
