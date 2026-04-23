<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Filament\Concerns\HasSupplierVariantsTable;
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
                            $v->id => "[{$v->sku}] {$v->product->name}".($v->name !== 'Default' ? " — {$v->name}" : ''),
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
                ...$this->supplierVariantColumns(showVariant: true),
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
