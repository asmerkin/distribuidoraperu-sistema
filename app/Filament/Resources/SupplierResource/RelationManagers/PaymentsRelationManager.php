<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    protected static ?string $title = 'Pagos realizados';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('invoice.invoice_number')
                    ->label('Factura')
                    ->searchable(),

                TextColumn::make('amount')
                    ->label('Monto')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('method')
                    ->label('Medio')
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'efectivo' => 'Efectivo',
                        'transferencia' => 'Transferencia',
                        'cheque' => 'Cheque',
                        default => $state,
                    })
                    ->badge()
                    ->color(fn ($state) => match ($state) {
                        'efectivo' => 'success',
                        'transferencia' => 'info',
                        'cheque' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('reference')
                    ->label('Referencia')
                    ->placeholder('—')
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Registrado por')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('date', 'desc')
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->paginated([10, 25, 50]);
    }
}
