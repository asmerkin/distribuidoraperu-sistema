<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Pages\PriceListUploadPage;
use App\Filament\Resources\SupplierResource;
use App\Filament\Resources\SupplierResource\Widgets\SupplierStatsWidget;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewSupplier extends ViewRecord
{
    protected static string $resource = SupplierResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        return "Proveedor: {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('import_price_list')
                ->label('Importar Precios')
                ->icon('heroicon-o-document-arrow-up')
                ->url(fn () => PriceListUploadPage::getUrl(['supplier' => $this->getRecord()->id]))
                ->color('gray'),
            EditAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            SupplierStatsWidget::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Razón social')
                                ->weight(FontWeight::Bold),

                            TextEntry::make('tax_id')
                                ->label('CUIT')
                                ->placeholder('—'),
                        ]),
                ]),

            Section::make('Contacto')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('contact_name')
                                ->label('Persona de contacto')
                                ->placeholder('—'),

                            TextEntry::make('phone')
                                ->label('Teléfono')
                                ->placeholder('—'),

                            TextEntry::make('email')
                                ->label('Email')
                                ->placeholder('—')
                                ->copyable(),

                            TextEntry::make('address')
                                ->label('Dirección')
                                ->placeholder('—'),
                        ]),
                ]),

            Section::make('Condiciones y notas')
                ->schema([
                    TextEntry::make('payment_terms')
                        ->label('Condiciones de pago')
                        ->placeholder('—')
                        ->columnSpanFull(),

                    TextEntry::make('notes')
                        ->label('Notas internas')
                        ->placeholder('—')
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }
}
