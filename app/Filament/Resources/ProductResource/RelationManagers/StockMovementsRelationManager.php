<?php

namespace App\Filament\Resources\ProductResource\RelationManagers;

use App\Filament\Concerns\HasStockMovementsTable;
use App\Models\StockMovement;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StockMovementsRelationManager extends RelationManager
{
    use HasStockMovementsTable;

    protected static string $relationship = 'variants';

    protected static ?string $title = 'Movimientos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => StockMovement::query()
                ->whereIn('variant_id', $this->getOwnerRecord()->variants()->pluck('id'))
                ->with(['variant', 'location', 'user'])
                ->latest()
            )
            ->columns($this->stockMovementColumns(showSku: true))
            ->filters($this->stockMovementFilters())
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin movimientos')
            ->emptyStateDescription('Los movimientos de stock se registran automáticamente.');
    }
}
