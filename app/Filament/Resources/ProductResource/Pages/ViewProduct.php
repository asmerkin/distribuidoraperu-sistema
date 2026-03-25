<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Enums\UnitOfMeasure;
use App\Filament\Resources\ProductResource;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Enums\FontWeight;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;

    public ?string $activeRelationManager = null;

    public function getHeading(): string
    {
        return "Producto: {$this->getRecord()->name}";
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')
                ->schema([
                    Grid::make(3)->schema([
                        Group::make([
                            Grid::make(2)->schema([
                                TextEntry::make('name')
                                    ->label('Nombre')
                                    ->weight(FontWeight::Bold)
                                    ->columnSpanFull(),

                                TextEntry::make('description')
                                    ->label('Descripción')
                                    ->placeholder('—')
                                    ->columnSpanFull(),

                                TextEntry::make('category.name')
                                    ->label('Categoría')
                                    ->placeholder('—'),

                                TextEntry::make('unit_of_measure')
                                    ->label('Unidad de Medida')
                                    ->badge()
                                    ->formatStateUsing(fn (UnitOfMeasure $state): string => $state->label())
                                    ->color('gray'),

                                TextEntry::make('suppliers.name')
                                    ->label('Proveedores')
                                    ->placeholder('—')
                                    ->columnSpanFull(),

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
