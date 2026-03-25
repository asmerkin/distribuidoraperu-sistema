<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Filament\Resources\ProductResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Productos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Producto')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('category.name')
                    ->label('Categoría')
                    ->placeholder('—')
                    ->sortable(),

                TextColumn::make('unit_of_measure')
                    ->label('Unidad')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state->label())
                    ->color('gray'),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->recordUrl(fn ($record) => ProductResource::getUrl('edit', ['record' => $record]))
            ->defaultSort('name')
            ->emptyStateHeading('Sin productos asociados');
    }
}
