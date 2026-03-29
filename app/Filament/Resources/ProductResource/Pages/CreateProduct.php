<?php

namespace App\Filament\Resources\ProductResource\Pages;

use App\Filament\Resources\ProductResource;
use App\Filament\Support\BarcodeInput;
use App\Services\ProductService;
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
                                ->maxLength(255)
                                ->rules(['unique:variants,sku']),

                            TextInput::make('name')
                                ->label('Nombre')
                                ->placeholder('Default')
                                ->maxLength(255),

                            BarcodeInput::make('barcode'),
                        ])
                        ->columns(3)
                        ->addActionLabel('Agregar variante')
                        ->defaultItems(1)
                        ->reorderableWithButtons(),
                ]),
        ]);
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
    {
        $formState = $this->form->getState();
        $variants = $formState['initial_variants'] ?? [];

        $result = app(ProductService::class)->createWithVariants(
            name: $data['name'],
            unitOfMeasure: $data['unit_of_measure'] ?? 'unit',
            categoryId: $data['category_id'] ?? null,
            description: $data['description'] ?? null,
            isActive: $data['is_active'] ?? true,
            variants: $variants,
        );

        return $result['product'];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
