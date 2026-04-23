<?php

namespace App\Filament\Resources\SupplierCreditNoteResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use App\Filament\Resources\SupplierCreditNoteResource;
use App\Filament\Resources\SupplierInvoiceResource;
use App\Filament\Resources\SupplierResource;
use App\Models\SupplierInvoice;
use App\Services\SupplierCreditNoteService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;
use Illuminate\Support\Facades\Storage;

class ViewSupplierCreditNote extends ViewRecord
{
    protected static string $resource = SupplierCreditNoteResource::class;

    public function getHeading(): string
    {
        return "Nota de Crédito: {$this->getRecord()->credit_note_number}";
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('apply_to_invoice')
                ->label('Aplicar a factura')
                ->icon('heroicon-o-arrow-right-circle')
                ->color('success')
                ->visible(fn () => $record->balance > 0)
                ->modalHeading("Aplicar NC {$record->credit_note_number} a una factura")
                ->modalSubmitActionLabel('Aplicar')
                ->form(function () use ($record) {
                    $invoices = SupplierInvoice::where('supplier_id', $record->supplier_id)
                        ->where('status', '!=', 'paid')
                        ->orderByDesc('date')
                        ->get()
                        ->filter(fn ($i) => $i->balance > 0);

                    return [
                        Select::make('supplier_invoice_id')
                            ->label('Factura')
                            ->options($invoices->mapWithKeys(fn (SupplierInvoice $i) => [
                                $i->id => "{$i->invoice_number} — Saldo $ ".number_format($i->balance, 2, ',', '.'),
                            ]))
                            ->searchable()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) use ($record) {
                                if (! $state) {
                                    return;
                                }
                                $invoice = SupplierInvoice::find($state);
                                if ($invoice) {
                                    $set('amount', round(min($record->balance, $invoice->balance), 2));
                                }
                            }),

                        TextInput::make('amount')
                            ->label('Monto a aplicar')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01)
                            ->maxValue($record->balance)
                            ->helperText('Saldo disponible en NC: $ '.number_format($record->balance, 2, ',', '.')),
                    ];
                })
                ->action(function (array $data) use ($record) {
                    $invoice = SupplierInvoice::findOrFail($data['supplier_invoice_id']);

                    app(SupplierCreditNoteService::class)->applyToInvoice(
                        creditNote: $record,
                        invoice: $invoice,
                        amount: (float) $data['amount'],
                        userId: auth()->id(),
                    );

                    Notification::make()
                        ->title('NC aplicada')
                        ->body('Se aplicaron $ '.number_format($data['amount'], 2, ',', '.')." a la factura {$invoice->invoice_number}.")
                        ->success()
                        ->send();

                    $this->refreshFormData(['total']);
                }),

            ActionGroup::make([
                Action::make('ver_proveedor')
                    ->label('Ver proveedor')
                    ->icon('heroicon-o-building-office')
                    ->url(fn () => SupplierResource::getUrl('view', ['record' => $record->supplier_id])),

                Action::make('ver_po')
                    ->label('Ver orden de compra')
                    ->icon('heroicon-o-shopping-cart')
                    ->visible(fn () => $record->purchase_order_id !== null)
                    ->url(fn () => PurchaseOrderResource::getUrl('view', ['record' => $record->purchase_order_id])),
            ]),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema->components([
            Section::make('Detalle')
                ->schema([
                    TextEntry::make('credit_note_number')->label('N° interno')->weight(FontWeight::Bold),
                    TextEntry::make('supplier_document_number')->label('N° NC del proveedor')->placeholder('—'),
                    TextEntry::make('supplier.name')->label('Proveedor'),
                    TextEntry::make('date')->label('Fecha')->date('d/m/Y'),
                    TextEntry::make('purchaseOrder.po_number')->label('OC relacionada')->placeholder('—'),
                    TextEntry::make('total')->label('Total')->money('ARS')->weight(FontWeight::Bold),
                    TextEntry::make('balance')
                        ->label('Saldo a favor')
                        ->state(fn () => $record->balance)
                        ->money('ARS')
                        ->badge()
                        ->color(fn () => $record->balance > 0 ? 'warning' : 'success'),
                    TextEntry::make('user.name')->label('Creada por')->placeholder('—'),
                    TextEntry::make('reason')->label('Motivo')->columnSpanFull(),
                    TextEntry::make('notes')->label('Notas internas')->placeholder('—')->columnSpanFull(),
                    TextEntry::make('attachment')
                        ->label('Adjunto')
                        ->visible(fn () => filled($record->attachment))
                        ->url(fn () => $record->attachment ? Storage::url($record->attachment) : null)
                        ->openUrlInNewTab()
                        ->state('Ver adjunto')
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Productos devueltos')
                ->schema([
                    RepeatableEntry::make('items')
                        ->label('')
                        ->schema([
                            TextEntry::make('variant.sku')->label('SKU'),
                            TextEntry::make('variant.product.name')->label('Producto'),
                            TextEntry::make('location.name')->label('Ubicación'),
                            TextEntry::make('quantity')
                                ->label('Cantidad')
                                ->formatStateUsing(function ($state, $record) {
                                    $puQty = $record->purchase_unit_qty ?? 1;
                                    if ($puQty > 1) {
                                        return "{$state} ({$record->base_quantity} uds. base)";
                                    }

                                    return $state;
                                }),
                            TextEntry::make('unit_cost')->label('Costo unit.')->money('ARS'),
                            TextEntry::make('subtotal')->label('Subtotal')->money('ARS'),
                        ])
                        ->columns(6),
                ]),

            Section::make('Aplicaciones a facturas')
                ->schema([
                    RepeatableEntry::make('appliedPayments')
                        ->label('')
                        ->schema([
                            TextEntry::make('date')->label('Fecha')->date('d/m/Y'),
                            TextEntry::make('invoice.invoice_number')
                                ->label('Factura')
                                ->url(fn ($record) => $record->supplier_invoice_id
                                    ? SupplierInvoiceResource::getUrl('view', ['record' => $record->supplier_invoice_id])
                                    : null),
                            TextEntry::make('amount')->label('Monto')->money('ARS'),
                            TextEntry::make('user.name')->label('Usuario')->placeholder('—'),
                        ])
                        ->columns(4),
                ])
                ->visible(fn () => $record->appliedPayments()->exists()),
        ])->columns(1);
    }
}
