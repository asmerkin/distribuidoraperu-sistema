<?php

namespace App\Filament\Resources\SupplierInvoiceResource\RelationManagers;

use App\Models\SupplierPayment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        $invoice = $this->getOwnerRecord();

        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Medio')
                    ->badge()
                    ->placeholder('—'),

                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('—'),

                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->placeholder('—'),

                TextColumn::make('notes')
                    ->label('Notas')
                    ->limit(30)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->actions([
                ViewAction::make()
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('date')->label('Fecha')->date('d/m/Y'),
                        \Filament\Infolists\Components\TextEntry::make('amount')->label('Monto')->money('ARS'),
                        \Filament\Infolists\Components\TextEntry::make('method')->label('Medio de pago')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('reference')->label('Referencia')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('user.name')->label('Usuario')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('notes')->label('Notas')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('attachment')
                            ->label('Comprobante')
                            ->visible(fn ($record) => filled($record->attachment))
                            ->url(fn ($record) => $record->attachment ? \Illuminate\Support\Facades\Storage::url($record->attachment) : null)
                            ->openUrlInNewTab()
                            ->state('Ver comprobante'),
                    ]),
                EditAction::make()
                    ->form([
                        DatePicker::make('date')
                            ->label('Fecha')
                            ->required()
                            ->displayFormat('d/m/Y'),

                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->minValue(0.01),

                        Select::make('method')
                            ->label('Medio de pago')
                            ->options([
                                'efectivo' => 'Efectivo',
                                'transferencia' => 'Transferencia',
                                'cheque' => 'Cheque',
                            ]),

                        TextInput::make('reference')
                            ->label('Referencia')
                            ->maxLength(255),

                        FileUpload::make('attachment')
                            ->label('Comprobante')
                            ->disk('public')
                            ->directory('supplier-payments')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->using(function (SupplierPayment $record, array $data) use ($invoice): SupplierPayment {
                        return DB::transaction(function () use ($record, $data, $invoice): SupplierPayment {
                            $record->update($data);
                            $invoice->recalculateFromPayments();

                            return $record;
                        });
                    }),

                DeleteAction::make()
                    ->using(function (SupplierPayment $record) use ($invoice): void {
                        DB::transaction(function () use ($record, $invoice): void {
                            $record->delete();
                            $invoice->recalculateFromPayments();
                        });
                    }),
            ])
            ->headerActions([
                Action::make('registrar_pago')
                    ->label('Registrar pago')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->visible(fn () => $invoice->balance > 0)
                    ->modalHeading("Registrar pago — Factura {$invoice->invoice_number}")
                    ->modalSubmitActionLabel('Registrar pago')
                    ->form([
                        TextInput::make('amount')
                            ->label('Monto')
                            ->numeric()
                            ->prefix('$')
                            ->required()
                            ->default($invoice->balance)
                            ->minValue(0.01)
                            ->maxValue($invoice->balance),

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

                        FileUpload::make('attachment')
                            ->label('Comprobante (PDF, imagen)')
                            ->disk('public')
                            ->directory('supplier-payments')
                            ->acceptedFileTypes(['application/pdf', 'image/*'])
                            ->maxSize(10240),

                        Textarea::make('notes')
                            ->label('Notas')
                            ->rows(2),
                    ])
                    ->action(function (array $data) use ($invoice) {
                        DB::transaction(function () use ($data, $invoice): void {
                            SupplierPayment::create([
                                'supplier_invoice_id' => $invoice->id,
                                'amount' => $data['amount'],
                                'date' => $data['date'],
                                'method' => $data['method'],
                                'reference' => $data['reference'] ?? null,
                                'attachment' => $data['attachment'] ?? null,
                                'notes' => $data['notes'] ?? null,
                                'user_id' => auth()->id(),
                            ]);

                            $invoice->recalculateFromPayments();
                        });

                        Notification::make()
                            ->title('Pago registrado')
                            ->body('$ ' . number_format($data['amount'], 2, ',', '.') . " registrado.")
                            ->success()
                            ->send();
                    }),
            ])
            ->defaultSort('date', 'desc')
            ->emptyStateHeading('Sin pagos registrados');
    }
}
