<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierCreditNoteResource\Pages;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierCreditNote;
use App\Models\SupplierVariant;
use App\Models\Variant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SupplierCreditNoteResource extends Resource
{
    protected static ?string $model = SupplierCreditNote::class;

    protected static ?string $modelLabel = 'Nota de Crédito';

    protected static ?string $pluralModelLabel = 'Notas de Crédito';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-receipt-refund';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'credit_note_number';

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Encabezado')
                ->schema([
                    TextInput::make('credit_note_number')
                        ->label('N° interno')
                        ->placeholder('Auto-generado')
                        ->disabled()
                        ->dehydrated(false),

                    TextInput::make('supplier_document_number')
                        ->label('N° NC del proveedor')
                        ->placeholder('Ej: NC-A-0001-00000123')
                        ->helperText('Número de la nota de crédito emitida por el proveedor')
                        ->maxLength(100),

                    Select::make('supplier_id')
                        ->label('Proveedor')
                        ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Select::make('purchase_order_id')
                        ->label('Orden de compra (opcional)')
                        ->placeholder('Ninguna')
                        ->options(function (callable $get) {
                            $supplierId = $get('supplier_id');
                            if (! $supplierId) {
                                return [];
                            }
                            return PurchaseOrder::where('supplier_id', $supplierId)
                                ->orderByDesc('order_date')
                                ->pluck('po_number', 'id');
                        })
                        ->searchable(),

                    DatePicker::make('date')
                        ->label('Fecha')
                        ->default(today())
                        ->required()
                        ->displayFormat('d/m/Y'),

                    Textarea::make('reason')
                        ->label('Motivo')
                        ->required()
                        ->rows(2)
                        ->placeholder('Ej: Mercadería dañada, error de referencia, diferencia en recuento')
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(2)
                        ->columnSpanFull(),

                    FileUpload::make('attachment')
                        ->label('Adjunto (NC del proveedor, remito, foto)')
                        ->disk('public')
                        ->directory('supplier-credit-notes')
                        ->acceptedFileTypes(['application/pdf', 'image/*'])
                        ->maxSize(10240)
                        ->downloadable()
                        ->openable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make('Productos devueltos')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->schema([
                            Select::make('supplier_variant_id')
                                ->label('Producto / Variante')
                                ->options(function (callable $get) {
                                    $supplierId = $get('../../supplier_id');
                                    if (! $supplierId) {
                                        return [];
                                    }

                                    return SupplierVariant::where('supplier_id', $supplierId)
                                        ->with('variant.product')
                                        ->get()
                                        ->mapWithKeys(fn (SupplierVariant $sv) => [
                                            $sv->id => "[{$sv->supplier_code}] {$sv->variant->product->name}"
                                                . ($sv->variant->name !== 'Default' ? " — {$sv->variant->name}" : '')
                                                . ($sv->purchase_unit ? " ({$sv->purchase_unit})" : ''),
                                        ]);
                                })
                                ->searchable()
                                ->preload()
                                ->required()
                                ->distinct()
                                ->columnSpan(3)
                                ->reactive()
                                ->afterStateUpdated(function ($state, callable $set) {
                                    if (! $state) {
                                        return;
                                    }
                                    $sv = SupplierVariant::with('variant')->find($state);
                                    if ($sv) {
                                        $set('variant_id', $sv->variant_id);
                                        $set('unit_cost', $sv->cost_price);
                                        $set('purchase_unit', $sv->purchase_unit);
                                        $set('purchase_unit_qty', $sv->purchase_unit_qty ?? 1);
                                    }
                                }),

                            Hidden::make('variant_id'),

                            Select::make('location_id')
                                ->label('Ubicación')
                                ->options(Location::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                                ->searchable()
                                ->preload()
                                ->required()
                                ->columnSpan(2),

                            Hidden::make('purchase_unit'),
                            Hidden::make('purchase_unit_qty')->default(1),

                            TextInput::make('quantity')
                                ->label('Cantidad')
                                ->integer()
                                ->minValue(1)
                                ->required()
                                ->reactive()
                                ->suffix(fn (callable $get) => $get('purchase_unit') ?: null)
                                ->helperText(function (callable $get) {
                                    $qty = intval($get('quantity'));
                                    $puQty = intval($get('purchase_unit_qty'));
                                    return ($puQty > 1 && $qty > 0)
                                        ? '= ' . ($qty * $puQty) . ' uds. base'
                                        : null;
                                })
                                ->afterStateUpdated(fn ($state, callable $get, callable $set) => $set(
                                    'subtotal',
                                    round(floatval($state) * floatval($get('unit_cost')), 2),
                                )),

                            TextInput::make('unit_cost')
                                ->label('Costo unit.')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0)
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(fn ($state, callable $get, callable $set) => $set(
                                    'subtotal',
                                    round(floatval($get('quantity')) * floatval($state), 2),
                                )),

                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(true),
                        ])
                        ->columns(6)
                        ->addActionLabel('Agregar ítem')
                        ->reorderableWithButtons()
                        ->required()
                        ->minItems(1),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_note_number')
                    ->label('N° interno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier_document_number')
                    ->label('N° proveedor')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('purchaseOrder.po_number')
                    ->label('OC')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('balance')
                    ->label('Saldo a favor')
                    ->money('ARS')
                    ->state(fn ($record) => $record->balance)
                    ->badge()
                    ->color(fn ($record) => $record->balance > 0 ? 'warning' : 'success')
                    ->alignRight(),

                TextColumn::make('user.name')
                    ->label('Creada por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->relationship('supplier', 'name')
                    ->searchable()
                    ->preload(),

                Filter::make('con_saldo')
                    ->label('Con saldo a favor')
                    ->toggle()
                    ->query(fn (Builder $query) => $query->whereRaw(
                        'total > COALESCE((SELECT SUM(amount) FROM supplier_payments WHERE supplier_payments.supplier_credit_note_id = supplier_credit_notes.id), 0)'
                    )),

                Filter::make('date_range')
                    ->label('Rango de fechas')
                    ->form([
                        DatePicker::make('from')->label('Desde')->displayFormat('d/m/Y'),
                        DatePicker::make('until')->label('Hasta')->displayFormat('d/m/Y'),
                    ])
                    ->query(fn ($query, array $data) => $query
                        ->when($data['from'], fn ($q, $date) => $q->where('date', '>=', $date))
                        ->when($data['until'], fn ($q, $date) => $q->where('date', '<=', $date))),
            ]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['credit_note_number', 'supplier_document_number', 'supplier.name'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Proveedor' => $record->supplier?->name,
            'Total' => '$ ' . number_format((float) $record->total, 2, ',', '.'),
            'Fecha' => $record->date?->format('d/m/Y'),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'purchaseOrder']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierCreditNotes::route('/'),
            'create' => Pages\CreateSupplierCreditNote::route('/create'),
            'view' => Pages\ViewSupplierCreditNote::route('/{record}'),
        ];
    }
}
