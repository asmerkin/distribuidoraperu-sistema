<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierInvoiceResource\Pages;
use App\Models\SupplierInvoice;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
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
        return false; // se crean desde el perfil del proveedor
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
            ])
            ->recordUrl(fn ($record) => SupplierResource::getUrl('view', ['record' => $record->supplier_id]))
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->modalHeading(fn ($record) => "Factura {$record->invoice_number}")
                    ->infolist([
                        \Filament\Infolists\Components\TextEntry::make('supplier.name')->label('Proveedor'),
                        \Filament\Infolists\Components\TextEntry::make('invoice_number')->label('N° Factura'),
                        \Filament\Infolists\Components\TextEntry::make('date')->label('Fecha')->date('d/m/Y'),
                        \Filament\Infolists\Components\TextEntry::make('due_date')->label('Vencimiento')->date('d/m/Y')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('total')->label('Total')->money('ARS'),
                        \Filament\Infolists\Components\TextEntry::make('amount_paid')->label('Pagado')->money('ARS'),
                        \Filament\Infolists\Components\TextEntry::make('balance')->label('Saldo')->money('ARS')->state(fn ($record) => $record->balance),
                        \Filament\Infolists\Components\TextEntry::make('display_status')->label('Estado')->badge()->state(fn ($record) => $record->display_status)->color(fn ($record) => $record->display_status_color),
                        \Filament\Infolists\Components\TextEntry::make('purchaseOrder.po_number')->label('Orden de Compra')->placeholder('—'),
                        \Filament\Infolists\Components\TextEntry::make('notes')->label('Notas')->placeholder('—'),
                    ]),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['supplier', 'purchaseOrder']);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSupplierInvoices::route('/'),
        ];
    }
}
