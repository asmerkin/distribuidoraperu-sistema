<?php

namespace App\Filament\Resources\VariantResource\RelationManagers;

use App\Filament\Concerns\HasStockMovementsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    use HasStockMovementsTable;

    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Movimientos de stock';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with(['location', 'user'])->latest())
            ->columns($this->stockMovementColumns())
            ->filters($this->stockMovementFilters())
            ->defaultSort('created_at', 'desc');
    }
}
