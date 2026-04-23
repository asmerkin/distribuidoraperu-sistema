<?php

namespace App\Filament\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Resources\SupplierInvoiceResource;
use App\Filament\Resources\SupplierResource;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewSupplierInvoice extends ViewRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        return "Factura: {$this->getRecord()->invoice_number}";
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            EditAction::make(),

            Action::make('ver_proveedor')
                ->label('Ver proveedor')
                ->icon('heroicon-o-truck')
                ->color('gray')
                ->url(fn () => SupplierResource::getUrl('view', ['record' => $record->supplier_id])),

        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema->components([
            Section::make('Detalle de la factura')
                ->schema([
                    Grid::make(3)->schema([
                        TextEntry::make('invoice_number')->label('N° Factura')->weight(FontWeight::Bold),
                        TextEntry::make('supplier.name')->label('Proveedor'),
                        TextEntry::make('purchaseOrder.po_number')->label('Orden de Compra')->placeholder('—'),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('date')->label('Fecha')->date('d/m/Y'),
                        TextEntry::make('due_date')->label('Vencimiento')->date('d/m/Y')->placeholder('—'),
                        TextEntry::make('display_status')
                            ->label('Estado')
                            ->badge()
                            ->state(fn () => $record->display_status)
                            ->color(fn () => $record->display_status_color),
                    ]),
                    Grid::make(3)->schema([
                        TextEntry::make('total')->label('Total')->money('ARS'),
                        TextEntry::make('amount_paid')->label('Pagado')->money('ARS'),
                        TextEntry::make('balance')
                            ->label('Saldo')
                            ->money('ARS')
                            ->state(fn () => $record->balance)
                            ->weight(FontWeight::Bold)
                            ->color($record->balance > 0 ? 'danger' : 'success'),
                    ]),
                ]),

            Section::make('Notas')
                ->schema([
                    TextEntry::make('notes')->label('')->placeholder('Sin notas'),
                ])
                ->collapsible()
                ->visible(fn () => filled($record->notes)),
        ]);
    }
}
