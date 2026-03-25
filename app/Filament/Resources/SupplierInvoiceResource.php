<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Filament\Resources\SupplierInvoiceResource\RelationManagers\PaymentsRelationManager;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierInvoiceResource extends Resource
{
    protected static ?string $model = SupplierInvoice::class;

    protected static ?string $modelLabel = 'Factura';

    protected static ?string $pluralModelLabel = 'Facturas';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'invoice_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['invoice_number', 'supplier.name'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Proveedor' => $record->supplier?->name,
            'Total' => '$ ' . number_format((float) $record->total, 2, ',', '.'),
            'Estado' => $record->display_status,
        ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('invoice_number')
                ->label('N° Factura')
                ->required()
                ->maxLength(100),

            Select::make('supplier_id')
                ->label('Proveedor')
                ->options(Supplier::orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required()
                ->disabled(),

            Grid::make(2)->schema([
                DatePicker::make('date')
                    ->label('Fecha')
                    ->required()
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
                ->options(fn ($record) => $record
                    ? PurchaseOrder::where('supplier_id', $record->supplier_id)
                        ->orderByDesc('order_date')
                        ->pluck('po_number', 'id')
                    : []
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
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

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

                TextColumn::make('purchaseOrder.po_number')
                    ->label('OC')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                SelectFilter::make('status')
                    ->label('Estado')
                    ->options([
                        'impaga' => 'Impaga',
                        'pago_parcial' => 'Pago parcial',
                        'pagada' => 'Pagada',
                    ]),

                Filter::make('vencidas')
                    ->label('Solo vencidas')
                    ->toggle()
                    ->query(fn ($query) => $query
                        ->where('status', '!=', 'pagada')
                        ->whereNotNull('due_date')
                        ->where('due_date', '<', today())
                    ),

                Filter::make('date_range')
                    ->label('Rango de fechas')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from')
                            ->label('Desde')
                            ->displayFormat('d/m/Y'),
                        \Filament\Forms\Components\DatePicker::make('until')
                            ->label('Hasta')
                            ->displayFormat('d/m/Y'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->where('date', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->where('date', '<=', $date));
                    })
                    ->indicateUsing(function (array $data): array {
                        $indicators = [];
                        if ($data['from'] ?? null) {
                            $indicators[] = 'Desde: ' . \Carbon\Carbon::parse($data['from'])->format('d/m/Y');
                        }
                        if ($data['until'] ?? null) {
                            $indicators[] = 'Hasta: ' . \Carbon\Carbon::parse($data['until'])->format('d/m/Y');
                        }
                        return $indicators;
                    }),
            ])
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]));
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'purchaseOrder']);
    }

    public static function getRelations(): array
    {
        return [
            PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierInvoices::route('/'),
            'view' => Pages\ViewSupplierInvoice::route('/{record}'),
            'edit' => Pages\EditSupplierInvoice::route('/{record}/edit'),
        ];
    }
}
