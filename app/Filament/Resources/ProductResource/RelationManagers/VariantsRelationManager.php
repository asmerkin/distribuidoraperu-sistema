<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Resources\VariantResource;
use App\Filament\Support\BarcodeInput;
use App\Models\Variant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $title = 'Variantes';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('sku')
                ->label('SKU')
                ->required()
                ->maxLength(255),

            TextInput::make('name')
                ->label('Nombre')
                ->required()
                ->maxLength(255),

            BarcodeInput::make('barcode'),

            FileUpload::make('images')
                ->label('Imágenes')
                ->disk('public')
                ->directory('variant-images')
                ->image()
                ->multiple()
                ->reorderable()
                ->appendFiles()
                ->maxFiles(5)
                ->maxSize(5120)
                ->panelLayout('grid')
                ->columnSpan(2),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->columnSpan(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordUrl(fn (Variant $record) => VariantResource::getUrl('view', ['record' => $record]))
            ->columns([
                ImageColumn::make('images')
                    ->label('Foto')
                    ->disk('public')
                    ->limit(1)
                    ->square()
                    ->size(32),

                TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('barcode')
                    ->label('Código de Barras')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('cost_price')
                    ->label('Precio de Costo')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('total_stock')
                    ->label('Stock Total')
                    ->getStateUsing(fn (Variant $record): int => $record->inventoryLevels()->sum('quantity'))
                    ->alignCenter(),

                TextColumn::make('pending_po')
                    ->label('Pendiente PO')
                    ->getStateUsing(fn (Variant $record): int => $record->pendingFromPurchaseOrders())
                    ->formatStateUsing(fn (int $state): string => $state > 0 ? "+{$state}" : '—')
                    ->color(fn (Variant $record): string => $record->pendingFromPurchaseOrders() > 0 ? 'info' : 'gray')
                    ->alignCenter(),

                IconColumn::make('is_active')
                    ->label('Activo')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('sku');
    }
}
