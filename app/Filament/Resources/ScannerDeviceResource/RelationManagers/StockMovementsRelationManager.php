<?php

namespace App\Filament\Resources\ScannerDeviceResource\RelationManagers;

use App\Filament\Concerns\HasStockMovementsTable;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class StockMovementsRelationManager extends RelationManager
{
    use HasStockMovementsTable;

    protected static string $relationship = 'stockMovements';

    protected static ?string $title = 'Escaneos';

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns($this->stockMovementColumns(showSku: true, showProduct: true))
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('Sin escaneos')
            ->emptyStateDescription('Los movimientos realizados desde este dispositivo apareceran aca.');
    }
}
