<?php

namespace App\Filament\Resources\SupplierResource\RelationManagers;

use App\Filament\Resources\SupplierCreditNoteResource;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CreditNotesRelationManager extends RelationManager
{
    protected static string $relationship = 'creditNotes';

    protected static ?string $title = 'Notas de Crédito';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('credit_note_number')
                    ->label('N° interno')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                TextColumn::make('supplier_document_number')
                    ->label('N° proveedor')
                    ->searchable()
                    ->placeholder('—'),

                TextColumn::make('date')
                    ->label('Fecha')
                    ->date('d/m/Y')
                    ->sortable(),

                TextColumn::make('purchaseOrder.po_number')
                    ->label('OC')
                    ->placeholder('—'),

                TextColumn::make('total')
                    ->label('Total')
                    ->money('ARS')
                    ->sortable(),

                TextColumn::make('balance')
                    ->label('Saldo a favor')
                    ->state(fn ($record) => $record->balance)
                    ->money('ARS')
                    ->badge()
                    ->color(fn ($record) => $record->balance > 0 ? 'warning' : 'success'),

                TextColumn::make('reason')
                    ->label('Motivo')
                    ->limit(40)
                    ->placeholder('—')
                    ->toggleable(),
            ])
            ->defaultSort('date', 'desc')
            ->recordUrl(fn ($record) => SupplierCreditNoteResource::getUrl('view', ['record' => $record]));
    }
}
