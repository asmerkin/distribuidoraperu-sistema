<?php

namespace App\Filament\Resources\SupplierInvoiceResource\RelationManagers;

use App\Models\SupplierPayment;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
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
        return $schema->components([
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
        ]);
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
                Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->modalHeading(fn ($record) => "Pago del " . $record->date->format('d/m/Y'))
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
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
                            ->state('Descargar comprobante')
                            ->url(fn ($record) => \Illuminate\Support\Facades\Storage::url($record->attachment))
                            ->openUrlInNewTab()
                            ->color('primary'),
                    ]),

                Action::make('editar')
                    ->label('Editar')
                    ->icon('heroicon-o-pencil')
                    ->modalHeading(fn ($record) => "Editar pago del " . $record->date->format('d/m/Y'))
                    ->modalSubmitActionLabel('Guardar')
                    ->fillForm(fn ($record) => [
                        'date' => $record->date,
                        'amount' => $record->amount,
                        'method' => $record->method,
                        'reference' => $record->reference,
                        'attachment' => $record->attachment,
                        'notes' => $record->notes,
                    ])
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
                    ->action(function (array $data, $record) {
                        $record->update($data);

                        Notification::make()
                            ->title('Pago actualizado')
                            ->success()
                            ->send();
                    }),

                Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('Eliminar pago')
                    ->modalDescription('El monto se restará del total pagado de la factura. ¿Continuar?')
                    ->action(function ($record) {
                        $invoice = $record->invoice;
                        $amount = (float) $record->amount;

                        if (filled($record->attachment)) {
                            \Illuminate\Support\Facades\Storage::disk('public')->delete($record->attachment);
                        }

                        $record->delete();

                        // Recalculate invoice amount_paid
                        $newAmountPaid = max(0, (float) $invoice->amount_paid - $amount);
                        $invoice->update([
                            'amount_paid' => $newAmountPaid,
                            'status' => $newAmountPaid >= (float) $invoice->total
                                ? \App\Enums\SupplierInvoiceStatus::Pagada
                                : ($newAmountPaid > 0
                                    ? \App\Enums\SupplierInvoiceStatus::PagoParcial
                                    : \App\Enums\SupplierInvoiceStatus::Impaga),
                        ]);

                        Notification::make()
                            ->title('Pago eliminado')
                            ->body('El estado de la factura fue actualizado.')
                            ->success()
                            ->send();
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

                        $invoice->recordPayment((float) $data['amount']);

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
