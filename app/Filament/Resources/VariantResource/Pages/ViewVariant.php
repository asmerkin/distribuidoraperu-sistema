<?php

namespace App\Filament\Resources\VariantResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Resources\VariantResource;
use Filament\Actions\Action;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewVariant extends ViewRecord
{
    protected static string $resource = VariantResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        $record = $this->getRecord();
        $label = "[{$record->sku}] {$record->product->name}";
        if ($record->name !== 'Default') {
            $label .= " — {$record->name}";
        }

        return $label;
    }

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('ver_producto')
                ->label('Ver producto')
                ->icon('heroicon-o-cube')
                ->color('gray')
                ->url(ProductResource::getUrl('view', ['record' => $record->product_id])),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        $record = $this->getRecord();

        return $schema->components([
            Section::make('Información de la variante')
                ->schema([
                    Grid::make(3)->schema([
                        Group::make([
                            Grid::make(2)->schema([
                                TextEntry::make('sku')
                                    ->label('SKU')
                                    ->weight(FontWeight::Bold),

                                TextEntry::make('name')
                                    ->label('Variante')
                                    ->formatStateUsing(fn (string $state) => $state === 'Default' ? '—' : $state),

                                TextEntry::make('product.name')
                                    ->label('Producto'),

                                TextEntry::make('product.category.name')
                                    ->label('Categoría')
                                    ->placeholder('—'),

                                TextEntry::make('barcode')
                                    ->label('Código de barras')
                                    ->placeholder('—'),

                                TextEntry::make('cost_price')
                                    ->label('Precio de costo')
                                    ->money('ARS'),

                                TextEntry::make('total_stock')
                                    ->label('Stock total')
                                    ->getStateUsing(fn () => $record->totalStock())
                                    ->weight(FontWeight::Bold),

                                IconEntry::make('is_active')
                                    ->label('Activo')
                                    ->boolean(),
                            ]),
                        ])->columnSpan(2),

                        Group::make([
                            ImageEntry::make('images')
                                ->label('Imágenes')
                                ->disk('public')
                                ->placeholder('Sin imágenes')
                                ->columnSpanFull(),
                        ])->columnSpan(1),
                    ]),
                ]),
        ]);
    }
}
