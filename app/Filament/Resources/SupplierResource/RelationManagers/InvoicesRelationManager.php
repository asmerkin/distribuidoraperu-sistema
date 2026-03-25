<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\SupplierPayment;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Facturas';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('N° Factura')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('due_date')
                    ->label('Vencimiento')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('amount_paid')
                    ->label('Pagado')
                    ->money('ARS'),

                TextColumn::make('balance')
                    ->label('Saldo')
                    ->money('ARS')
                    ->state(fn ($record) => $record->balance),

                TextColumn::make('display_status')
                    ->label('Estado')
                    ->badge()
                    ->state(fn ($record) => $record->display_status)
                    ->color(fn ($record) => $record->display_status_color),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([
                CreateAction::make()
                    ->label('Nueva factura')
                    ->form($this->getInvoiceFormSchema())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();

                        return $data;
                    }),
            ])
            ->actions([
                EditAction::make()
                    ->form($this->getInvoiceFormSchema()),

                Action::make('registrar_pago')
                    ->label('Registrar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn ($record) => $record->balance > 0)
                    ->modalHeading(fn ($record) => "Registrar pago — Factura {$record->invoice_number}")
                    ->modalSubmitActionLabel('Registrar pago')
                    ->form(fn ($record) => [
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default(fn () => $record->balance)
                            ->minValue(0.01)
                            ->maxValue(fn () => $record->balance),

                        DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->default(today())
                            ->displayFormat('d/m/Y'),

                        Select::make('method')
                            ->label('Medio de pago')
                            ->required()
                            ->options([
                                'efectivo' => 'Efectivo',
                                'transferencia' => 'Transferencia',
                                'cheque' => 'Cheque',
                            ]),

                        TextInput::make('reference')
                            ->label('Referencia')
                            ->placeholder('N° de cheque, transferencia, etc.')
                            ->maxLength(255),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),

                        FileUpload::make('attachment')
                            ->label('Comprobante (PDF, imagen)')
                            ->disk('public')
                            ->directory('supplier-payments')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240),
                    ])
                    ->action(function (array $data, $record): void {
                        SupplierPayment::create([
                            'supplier_invoice_id' => $record->getKey(),
                            'amount' => $data['amount'],
                            'date' => $data['date'],
                            'method' => $data['method'],
                            'reference' => $data['reference'] ?? null,
                            'notes' => $data['notes'] ?? null,
                            'attachment' => $data['attachment'] ?? null,
                            'user_id' => auth()->id(),
                        ]);

                        $record->recordPayment((float) $data['amount']);

                        Notification::make()
                            ->title('Pago registrado')
                            ->body("Se registró un pago de $ " . number_format($data['amount'], 2, ',', '.') . " para la factura {$record->invoice_number}.")
                            ->success()
                            ->send();
                    }),

                Action::make('ver_adjunto')
                    ->label('Ver adjunto')
                    ->icon('heroicon-o-paper-clip')
                    ->color('gray')
                    ->visible(fn ($record) => filled($record->attachment))
                    ->url(fn ($record) => $record->attachment
                        ? \Illuminate\Support\Facades\Storage::url($record->attachment)
                        : null
                    )
                    ->openUrlInNewTab(),
            ]);
    }

    private function getInvoiceFormSchema(): array
    {
        return [
            TextInput::make('invoice_number')
                ->label('N° Factura')
                ->required()
                ->maxLength(100),

            \Filament\Schemas\Components\Grid::make(2)
                ->schema([
                    DatePicker::make('date')
                        ->label('Fecha')
                        ->required()
                        ->default(today())
                        ->displayFormat('d/m/Y'),

                    DatePicker::make('due_date')
                        ->label('Vencimiento')
                        ->displayFormat('d/m/Y'),
                ]),

            TextInput::make('total')
                ->label('Total')
                ->numeric()
                ->prefix('$')
                ->required()
                ->minValue(0),

            Select::make('purchase_order_id')
                ->label('Orden de compra relacionada')
                ->placeholder('Ninguna')
                ->options(fn () => $this->getOwnerRecord()
                    ->purchaseOrders()
                    ->orderByDesc('order_date')
                    ->pluck('po_number', 'id')
                )
                ->searchable(),

            FileUpload::make('attachment')
                ->label('Adjunto (PDF, imagen)')
                ->disk('public')
                ->directory('supplier-invoices')
                ->acceptedFileTypes(['application/pdf', 'image/*'])
                ->maxSize(10240)
                ->downloadable()
                ->openable(),

            Textarea::make('notes')
                ->label('Notas')
                ->rows(3),
        ];
    }
}
