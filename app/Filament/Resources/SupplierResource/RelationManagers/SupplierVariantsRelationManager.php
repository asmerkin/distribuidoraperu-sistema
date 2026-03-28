<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Models\Variant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SupplierVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierVariants';

    protected static ?string $title = 'Productos';

    protected static ?string $modelLabel = 'producto';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('variant_id')
                ->label('Variante')
                ->options(
                    Variant::query()
                        ->with('product')
                        ->where('is_active', true)
                        ->get()
                        ->mapWithKeys(fn (Variant $v) => [
                            $v->id => "[{$v->sku}] {$v->product->name}" . ($v->name !== 'Default' ? " — {$v->name}" : ''),
                        ])
                )
                ->searchable()
                ->preload()
                ->required(),

            TextInput::make('supplier_code')
                ->label('Código del proveedor')
                ->required()
                ->maxLength(255),

            TextInput::make('cost_price')
                ->label('Precio por unidad de compra')
                ->numeric()
                ->prefix('$')
                ->required(),

            TextInput::make('purchase_unit')
                ->label('Unidad de compra')
                ->placeholder('Ej: Caja x12, Pack x6')
                ->maxLength(255),

            TextInput::make('purchase_unit_qty')
                ->label('Uds. base por unidad de compra')
                ->integer()
                ->default(1)
                ->minValue(1)
                ->required()
                ->helperText('Cuántas unidades base contiene cada unidad de compra'),

            Toggle::make('is_default')
                ->label('Proveedor predeterminado')
                ->columnSpan(2),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('variant.product.name')
                    ->label('Producto')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('variant.name')
                    ->label('Variante')
                    ->sortable(),

                TextColumn::make('supplier_code')
                    ->label('Cód. Proveedor')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('purchase_unit')
                    ->label('Unidad compra')
                    ->placeholder('—'),

                TextColumn::make('cost_price')
                    ->label('Precio')
                    ->money('ARS')
                    ->sortable(),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean()
                    ->alignCenter(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Agregar producto'),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('variant.sku');
    }
}
