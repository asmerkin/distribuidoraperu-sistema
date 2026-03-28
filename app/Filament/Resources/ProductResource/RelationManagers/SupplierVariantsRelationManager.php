<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Concerns\HasSupplierVariantsTable;
use App\Models\Supplier;
use App\Models\Variant;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SupplierVariantsRelationManager extends RelationManager
{
    use HasSupplierVariantsTable;

    protected static string $relationship = 'supplierVariants';

    protected static ?string $title = 'Proveedores';

    protected static ?string $modelLabel = 'proveedor';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        $product = $this->getOwnerRecord();

        return $schema->components([
            Select::make('supplier_id')
                ->label('Proveedor')
                ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
                ->searchable()
                ->preload()
                ->required(),

            Select::make('variant_id')
                ->label('Variante')
                ->options(
                    $product->variants()
                        ->get()
                        ->mapWithKeys(fn (Variant $v) => [
                            $v->id => "[{$v->sku}] {$v->name}",
                        ])
                )
                ->searchable()
                ->preload()
                ->required(),

            ...$this->supplierVariantFormFields(),
        ])->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ...$this->supplierVariantColumns(showSupplier: true, showVariant: true),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->actions([
                EditAction::make(),
                $this->priceHistoryAction(),
                DeleteAction::make(),
            ])
            ->defaultSort('supplier.name');
    }
}
