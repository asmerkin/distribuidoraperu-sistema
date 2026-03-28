<?php

namespace App\Filament\Resources;

use App\Enums\PurchaseOrderStatus;
use App\Filament\Resources\PurchaseOrderResource\Pages;
use App\Models\Location;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PurchaseOrderResource extends Resource
{
    protected static ?string $model = PurchaseOrder::class;

    protected static ?string $modelLabel = 'Orden de Compra';

    protected static ?string $pluralModelLabel = 'Órdenes de Compra';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-shopping-cart';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'po_number';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Encabezado')
                ->schema([
                    TextInput::make('po_number')
                        ->label('N° de Orden')
                        ->placeholder('Auto-generado')
                        ->disabled()
                        ->dehydrated(false),

                    Select::make('supplier_id')
                        ->label('Proveedor')
                        ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required()
                        ->reactive(),

                    Select::make('location_id')
                        ->label('Destino de mercadería')
                        ->options(Location::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id'))
                        ->searchable()
                        ->preload()
                        ->required(),

                    DatePicker::make('order_date')
                        ->label('Fecha de orden')
                        ->default(today())
                        ->required()
                        ->displayFormat('d/m/Y'),

                    DatePicker::make('expected_date')
                        ->label('Fecha estimada de entrega')
                        ->nullable()
                        ->displayFormat('d/m/Y'),

                    Select::make('status')
                        ->label('Estado')
                        ->options(collect(PurchaseOrderStatus::cases())->mapWithKeys(
                            fn (PurchaseOrderStatus $s) => [$s->value => $s->label()]
                        ))
                        ->default(PurchaseOrderStatus::Draft->value)
                        ->disabled()
                        ->required(),

                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(2),

                    Textarea::make('notes_for_supplier')
                        ->label('Notas para el proveedor')
                        ->helperText('Aparecerán en el PDF y email al proveedor.')
                        ->rows(2),
                ])
                ->columns(3),

            Section::make('Productos')
                ->schema([
                    Repeater::make('items')
                        ->label('')
                        ->relationship('items')
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
                                    if ($state) {
                                        $sv = SupplierVariant::with('variant')->find($state);
                                        if ($sv) {
                                            $set('variant_id', $sv->variant_id);
                                            $set('unit_cost', $sv->cost_price);
                                            $set('purchase_unit', $sv->purchase_unit);
                                            $set('purchase_unit_qty', $sv->purchase_unit_qty ?? 1);
                                        }
                                    }
                                }),

                            Hidden::make('variant_id'),
                            Hidden::make('purchase_unit'),
                            Hidden::make('purchase_unit_qty')->default(1),
                            Hidden::make('base_quantity_ordered'),

                            TextInput::make('quantity_ordered')
                                ->label('Cantidad')
                                ->integer()
                                ->minValue(1)
                                ->required()
                                ->reactive()
                                ->suffix(fn (callable $get) => $get('purchase_unit') ?: null)
                                ->helperText(function (callable $get) {
                                    $qty = intval($get('quantity_ordered'));
                                    $puQty = intval($get('purchase_unit_qty'));

                                    return ($puQty > 1 && $qty > 0)
                                        ? "= " . ($qty * $puQty) . " unidades base"
                                        : null;
                                })
                                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                                    $qty = intval($state);
                                    $puQty = intval($get('purchase_unit_qty')) ?: 1;
                                    $set('subtotal', round(floatval($state) * floatval($get('unit_cost')), 2));
                                    $set('base_quantity_ordered', $qty * $puQty);
                                }),

                            TextInput::make('unit_cost')
                                ->label('Costo unit.')
                                ->numeric()
                                ->prefix('$')
                                ->minValue(0)
                                ->required()
                                ->reactive()
                                ->helperText(function (callable $get) {
                                    $puQty = intval($get('purchase_unit_qty'));
                                    $cost = floatval($get('unit_cost'));

                                    return ($puQty > 1 && $cost > 0)
                                        ? '$ ' . number_format($cost / $puQty, 2, ',', '.') . ' /ud. base'
                                        : null;
                                })
                                ->afterStateUpdated(fn ($state, callable $get, callable $set) =>
                                    $set('subtotal', round(floatval($get('quantity_ordered')) * floatval($state), 2))
                                ),

                            TextInput::make('subtotal')
                                ->label('Subtotal')
                                ->numeric()
                                ->prefix('$')
                                ->disabled()
                                ->dehydrated(true),
                        ])
                        ->columns(6)
                        ->addActionLabel('Agregar producto')
                        ->reorderableWithButtons(),
                ]),
        ])->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('po_number')
                    ->label('N° Orden')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location.name')
                    ->label('Destino')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (PurchaseOrderStatus $state) => $state->label())
                    ->color(fn (PurchaseOrderStatus $state) => match ($state) {
                        PurchaseOrderStatus::Draft             => 'gray',
                        PurchaseOrderStatus::Sent              => 'info',
                        PurchaseOrderStatus::Confirmed         => 'success',
                        PurchaseOrderStatus::Rejected          => 'danger',
                        PurchaseOrderStatus::PartiallyReceived => 'warning',
                        PurchaseOrderStatus::Received          => 'success',
                        PurchaseOrderStatus::Cancelled         => 'danger',
                    }),

                TextColumn::make('order_date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('expected_date')
                    ->label('Entrega est.')
                    ->date('d/m/Y')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable()
                    ->alignRight(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn ($record) => static::getUrl('view', ['record' => $record]))
            ->filters([
                SelectFilter::make('status')
                    ->label('Estado')
                    ->options(collect(PurchaseOrderStatus::cases())->mapWithKeys(
                        fn (PurchaseOrderStatus $s) => [$s->value => $s->label()]
                    )),

                SelectFilter::make('supplier_id')
                    ->label('Proveedor')
                    ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable(),
            ])
;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['po_number', 'supplier.name'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'Proveedor' => $record->supplier?->name,
            'Estado' => $record->status->label(),
            'Total' => '$ ' . number_format((float) $record->total, 2, ',', '.'),
        ]);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'location']);
    }

    public static function getRelations(): array
    {
        return [
            \App\Filament\Resources\PurchaseOrderResource\RelationManagers\ReceiptsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListPurchaseOrders::route('/'),
            'create' => Pages\CreatePurchaseOrder::route('/create'),
            'view'   => Pages\ViewPurchaseOrder::route('/{record}'),
            'edit'   => Pages\EditPurchaseOrder::route('/{record}/edit'),
        ];
    }
}
