<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function form(Schema $schema): Schema
    {
        $base = parent::form($schema);

        return $base->components([
            ...$base->getComponents(),

            Section::make('Variantes')
                ->columnSpanFull()
                ->schema([
                    Repeater::make('initial_variants')
                        ->label('')
                        ->schema([
                            TextInput::make('sku')
                                ->label('SKU')
                                ->required()
                                ->maxLength(255),

                            TextInput::make('name')
                                ->label('Nombre')
                                ->placeholder('Default')
                                ->maxLength(255),

                            TextInput::make('barcode')
                                ->label('Código de barras')
                                ->maxLength(255),
                        ])
                        ->columns(3)
                        ->addActionLabel('Agregar variante')
                        ->defaultItems(1)
                        ->reorderableWithButtons(),
                ]),
        ]);
    }

    protected function afterCreate(): void
    {
        $data = $this->form->getState();

        foreach ($data['initial_variants'] ?? [] as $variant) {
            $this->record->variants()->create([
                'sku' => $variant['sku'],
                'name' => $variant['name'] ?: 'Default',
                'barcode' => $variant['barcode'] ?? null,
                'is_active' => true,
            ]);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
