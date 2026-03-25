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
            ->recordUrl(fn ($record) => \App\Filament\Resources\SupplierInvoiceResource::getUrl('view', ['record' => $record]));
    }

    private function getInvoiceFormSchema(): array
    {
        return [
            TextInput::make('invoice_number')
                ->label('N° Factura')
                ->required()
                ->maxLength(100)
                ->unique(
                    table: 'supplier_invoices',
                    column: 'invoice_number',
                    modifyRuleUsing: fn ($rule) => $rule->where('supplier_id', $this->getOwnerRecord()->id),
                ),

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
