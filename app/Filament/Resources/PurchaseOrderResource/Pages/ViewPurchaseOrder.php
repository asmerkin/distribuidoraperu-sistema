<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\SupplierCreditNoteResource;
use App\Filament\Resources\SupplierResource;
use App\Mail\PurchaseOrderMail;
use App\Services\PurchaseOrderPdfService;
use App\Services\PurchaseOrderService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Mail;

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
            // ── Acciones principales (botones visibles) ──

            EditAction::make()
                ->visible(fn () => $record->status === PurchaseOrderStatus::Draft),

            Action::make('send')
                ->label('Enviar al proveedor')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $record->status === PurchaseOrderStatus::Draft)
                ->modalHeading('Enviar orden de compra')
                ->modalSubmitActionLabel('Marcar como enviada')
                ->form([
                    Toggle::make('send_email')
                        ->label('Enviar email al proveedor')
                        ->default(fn () => (bool) $record->supplier->email)
                        ->disabled(fn () => ! $record->supplier->email)
                        ->helperText(fn () => ! $record->supplier->email
                            ? 'El proveedor no tiene email cargado.'
                            : "Se enviará a {$record->supplier->email}"),
                ])
                ->action(function (array $data) use ($record) {
                    if ($data['send_email'] && $record->supplier->email) {
                        Mail::to($record->supplier->email)
                            ->send(new PurchaseOrderMail($record));
                    }

                    $record->update([
                        'status' => PurchaseOrderStatus::Sent,
                        'sent_at' => now(),
                    ]);

                    $emailMsg = ($data['send_email'] && $record->supplier->email)
                        ? " y enviada por email a {$record->supplier->email}"
                        : '';

                    Notification::make()
                        ->title('Orden enviada')
                        ->body("La orden {$record->po_number} fue marcada como enviada{$emailMsg}.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['status', 'sent_at']);
                }),

            Action::make('confirm')
                ->label('Confirmar')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $record->status === PurchaseOrderStatus::Sent)
                ->modalHeading('Confirmar orden de compra')
                ->modalDescription("Registrar la confirmación del proveedor para la orden {$record->po_number}. Podés ajustar cantidades, precios y fecha de entrega según lo confirmado.")
                ->modalSubmitActionLabel('Confirmar orden')
                ->modalWidth('lg')
                ->form(fn () => $this->buildConfirmFormFields())
                ->action(fn (array $data) => $this->processConfirmation($data)),

            Action::make('reject')
                ->label('Rechazar')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->visible(fn () => $record->status === PurchaseOrderStatus::Sent)
                ->modalHeading('Rechazar orden de compra')
                ->modalDescription("Marcar la orden {$record->po_number} como rechazada por el proveedor.")
                ->modalSubmitActionLabel('Marcar como rechazada')
                ->form([
                    Textarea::make('rejection_reason')
                        ->label('Motivo del rechazo')
                        ->rows(3)
                        ->required(),
                ])
                ->action(function (array $data) use ($record) {
                    $record->update([
                        'status' => PurchaseOrderStatus::Rejected,
                        'rejected_at' => now(),
                        'rejection_reason' => $data['rejection_reason'],
                    ]);

                    Notification::make()
                        ->title('Orden rechazada')
                        ->body("La orden {$record->po_number} fue marcada como rechazada.")
                        ->warning()
                        ->send();

                    $this->refreshFormData(['status', 'rejected_at', 'rejection_reason']);
                }),

            Action::make('receive')
                ->label('Recibir mercadería')
                ->icon('heroicon-o-truck')
                ->color('success')
                ->visible(fn () => in_array($record->status, [
                    PurchaseOrderStatus::Confirmed,
                    PurchaseOrderStatus::PartiallyReceived,
                ]))
                ->modalHeading('Recibir mercadería')
                ->modalDescription("Orden {$record->po_number} — Ingresá las cantidades recibidas.")
                ->modalSubmitActionLabel('Confirmar recepción')
                ->modalWidth('lg')
                ->form(fn () => $this->buildReceiveFormFields())
                ->action(fn (array $data) => $this->processReception($data)),

            Action::make('create_credit_note')
                ->label('Crear nota de crédito')
                ->icon('heroicon-o-receipt-refund')
                ->color('warning')
                ->visible(fn () => in_array($record->status, [
                    PurchaseOrderStatus::PartiallyReceived,
                    PurchaseOrderStatus::Received,
                ]))
                ->url(fn () => SupplierCreditNoteResource::getUrl('create', [
                    'supplier_id' => $record->supplier_id,
                    'purchase_order_id' => $record->id,
                ])),

            Action::make('download_pdf')
                ->label('PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->action(function () use ($record) {
                    $pdf = app(PurchaseOrderPdfService::class)->generate($record);

                    return response()->streamDownload(
                        fn () => print($pdf->output()),
                        "OC-{$record->po_number}.pdf",
                    );
                }),

            // ── Acciones secundarias (menú de 3 puntitos) ──

            ActionGroup::make([
                Action::make('resend_email')
                    ->label('Reenviar email')
                    ->icon('heroicon-o-envelope')
                    ->visible(fn () => in_array($record->status, [
                        PurchaseOrderStatus::Sent,
                        PurchaseOrderStatus::Confirmed,
                    ]) && $record->supplier->email)
                    ->requiresConfirmation()
                    ->modalHeading('Reenviar email')
                    ->modalDescription("Se reenviará la orden {$record->po_number} por email a {$record->supplier->email}.")
                    ->modalSubmitActionLabel('Reenviar')
                    ->action(function () use ($record) {
                        Mail::to($record->supplier->email)
                            ->send(new PurchaseOrderMail($record));

                        Notification::make()
                            ->title('Email reenviado')
                            ->body("La orden {$record->po_number} fue reenviada a {$record->supplier->email}.")
                            ->success()
                            ->send();
                    }),

                Action::make('reopen')
                    ->label('Reabrir como borrador')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->visible(fn () => $record->status === PurchaseOrderStatus::Rejected)
                    ->requiresConfirmation()
                    ->modalHeading('Reabrir orden de compra')
                    ->modalDescription("La orden {$record->po_number} volverá a estado Borrador para modificar y reenviar.")
                    ->modalSubmitActionLabel('Reabrir')
                    ->action(function () use ($record) {
                        $record->update([
                            'status' => PurchaseOrderStatus::Draft,
                            'sent_at' => null,
                        ]);

                        Notification::make()
                            ->title('Orden reabierta')
                            ->body("La orden {$record->po_number} volvió a estado borrador.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status', 'sent_at']);
                    }),

                Action::make('ver_proveedor')
                    ->label('Ver proveedor')
                    ->icon('heroicon-o-building-office')
                    ->url(fn () => SupplierResource::getUrl('view', ['record' => $record->supplier_id])),

                Action::make('cancel')
                    ->label('Cancelar orden')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn () => in_array($record->status, [
                        PurchaseOrderStatus::Sent,
                        PurchaseOrderStatus::Confirmed,
                        PurchaseOrderStatus::Rejected,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading(fn () => in_array($record->status, [
                        PurchaseOrderStatus::Sent,
                        PurchaseOrderStatus::Confirmed,
                        PurchaseOrderStatus::PartiallyReceived,
                    ]) ? 'Cancelar orden confirmada' : 'Cancelar orden')
                    ->modalDescription(function () use ($record) {
                        if ($record->status === PurchaseOrderStatus::PartiallyReceived) {
                            return "Esta orden ya tiene recepciones parciales registradas. Al cancelarla no se revertirá el stock ya ingresado. ¿Confirmar cancelación de {$record->po_number}?";
                        }

                        if (in_array($record->status, [PurchaseOrderStatus::Sent, PurchaseOrderStatus::Confirmed])) {
                            return "Esta orden ya fue enviada al proveedor. ¿Confirmar cancelación de {$record->po_number}?";
                        }

                        return "¿Cancelar la orden {$record->po_number}? Esta acción no se puede deshacer.";
                    })
                    ->modalSubmitActionLabel('Sí, cancelar')
                    ->action(function () use ($record) {
                        $record->update([
                            'status' => PurchaseOrderStatus::Cancelled,
                        ]);

                        Notification::make()
                            ->title('Orden cancelada')
                            ->body("La orden {$record->po_number} fue cancelada.")
                            ->success()
                            ->send();

                        $this->refreshFormData(['status']);
                    }),

                Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn () => $record->status === PurchaseOrderStatus::Draft)
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
            ]),
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
                        ->color(fn (PurchaseOrderStatus $state) => $state->color()),
                    TextEntry::make('location.name')->label('Destino'),
                    TextEntry::make('order_date')->label('Fecha de orden')->date('d/m/Y'),
                    TextEntry::make('expected_date')->label('Entrega estimada')->date('d/m/Y')->placeholder('—'),
                    TextEntry::make('total')->label('Total')->money('ARS')->weight(FontWeight::Bold),
                    TextEntry::make('sent_at')->label('Enviada')->dateTime('d/m/Y H:i')->placeholder('No enviada'),
                    TextEntry::make('confirmed_at')->label('Confirmada')->dateTime('d/m/Y H:i')->placeholder('—'),
                    TextEntry::make('user.name')->label('Creada por')->placeholder('—'),
                    TextEntry::make('notes')->label('Notas internas')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('notes_for_supplier')->label('Notas para el proveedor')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('rejection_reason')
                        ->label('Motivo de rechazo')
                        ->placeholder('—')
                        ->columnSpanFull()
                        ->visible(fn () => $record->rejected_at !== null),
                ])
                ->columns(2),

            Section::make('Productos')
                ->schema([
                    \Filament\Infolists\Components\RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('variant.sku')->label('SKU'),
                            TextEntry::make('variant.product.name')->label('Producto'),
                            TextEntry::make('purchase_unit')->label('Unidad compra')->placeholder('—'),
                            TextEntry::make('quantity_ordered')
                                ->label('Pedido')
                                ->formatStateUsing(function ($state, $record) {
                                    $puQty = $record->purchase_unit_qty ?? 1;
                                    if ($puQty > 1) {
                                        return "{$state} ({$record->base_quantity_ordered} uds. base)";
                                    }

                                    return $state;
                                }),
                            TextEntry::make('quantity_received')
                                ->label('Recibido')
                                ->formatStateUsing(function ($state, $record) {
                                    $puQty = $record->purchase_unit_qty ?? 1;
                                    if ($puQty > 1 && $state > 0) {
                                        return "{$state} ({$record->base_quantity_received} uds. base)";
                                    }

                                    return $state;
                                }),
                            TextEntry::make('unit_cost')->label('Costo unit.')->money('ARS'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('ARS'),
                        ])
                        ->columns(8),
                ]),
        ])->columns(1);
    }

    private function buildConfirmFormFields(): array
    {
        $record = $this->getRecord();
        $record->load('items.variant.product');

        $fields = [];

        $fields[] = DatePicker::make('expected_date')
            ->label('Fecha de entrega confirmada')
            ->default($record->expected_date?->format('Y-m-d'))
            ->native(false);

        foreach ($record->items as $item) {
            $description = $item->purchase_unit
                ? "Unidad de compra: {$item->purchase_unit} (×{$item->purchase_unit_qty} uds. base)"
                : null;

            $fields[] = \Filament\Schemas\Components\Section::make($item->variant->getLabel())
                ->description($description)
                ->schema([
                    Grid::make(2)->schema([
                        TextInput::make("qty_{$item->id}")
                            ->label('Cantidad confirmada')
                            ->integer()
                            ->default($item->quantity_ordered)
                            ->minValue(0)
                            ->required()
                            ->suffix($item->purchase_unit ?: null),

                        TextInput::make("price_{$item->id}")
                            ->label($item->purchase_unit ? "Precio por {$item->purchase_unit}" : 'Precio unitario')
                            ->numeric()
                            ->prefix('$')
                            ->default($item->unit_cost)
                            ->minValue(0)
                            ->required(),
                    ]),
                ])
                ->compact();
        }

        return $fields;
    }

    private function processConfirmation(array $data): void
    {
        $record = $this->getRecord();

        app(PurchaseOrderService::class)->confirm($record, $data);

        Notification::make()
            ->title('Orden confirmada')
            ->body("La orden {$record->po_number} fue confirmada por el proveedor.")
            ->success()
            ->send();

        $this->refreshFormData(['status', 'confirmed_at', 'expected_date', 'total']);
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

            $unitLabel = $item->purchase_unit ?: 'uds.';
            $description = "Pedido: {$item->quantity_ordered} {$unitLabel} | Recibido: {$item->quantity_received} | Pendiente: {$pending}";
            if ($item->purchase_unit_qty > 1) {
                $description .= " (1 {$item->purchase_unit} = {$item->purchase_unit_qty} uds. base)";
            }

            $fields[] = \Filament\Schemas\Components\Section::make($item->variant->getLabel())
                ->description($description)
                ->schema([
                    \Filament\Schemas\Components\Grid::make(2)->schema([
                        TextInput::make("qty_{$item->id}")
                            ->label('Cantidad a recibir')
                            ->integer()
                            ->default(0)
                            ->minValue(0)
                            ->maxValue($pending)
                            ->suffix($item->purchase_unit ? "{$item->purchase_unit} / {$pending}" : "/ {$pending}"),

                        TextInput::make("price_{$item->id}")
                            ->label($item->purchase_unit ? "Precio por {$item->purchase_unit}" : 'Precio unitario')
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
        $record->load('items.variant');

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
            $puQty = max($item->purchase_unit_qty, 1);

            $receiptItems[] = [
                'purchase_order_item_id' => $item->id,
                'variant_id' => $item->variant->id,
                'quantity_received' => $qty,
                'base_quantity_received' => $qty * $puQty,
                'unit_cost' => $confirmedPrice,
            ];
        }

        if (empty($receiptItems)) {
            Notification::make()
                ->title('Sin cambios')
                ->body('No se ingresó ninguna cantidad a recibir.')
                ->warning()
                ->send();

            return;
        }

        $allReceived = app(PurchaseOrderService::class)->receive(
            $record,
            $receiptItems,
            auth()->id(),
        );

        $statusLabel = $allReceived ? 'completa' : 'parcial';

        Notification::make()
            ->title("Recepción {$statusLabel}")
            ->body("Mercadería registrada para OC {$record->po_number}.")
            ->success()
            ->send();

        $this->refreshFormData(['status']);
    }
}
