<?php

namespace App\Filament\Resources\VariantResource\RelationManagers;

use App\Models\Supplier;
use App\Models\SupplierVariant;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
class SupplierVariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'supplierVariants';

    protected static ?string $title = 'Proveedores';

    protected static ?string $modelLabel = 'proveedor';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Select::make('supplier_id')
                ->label('Proveedor')
                ->options(Supplier::query()->orderBy('name')->pluck('name', 'id'))
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
                TextColumn::make('supplier.name')
                    ->label('Proveedor')
                    ->sortable()
                    ->searchable(),

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
                    ->label('Agregar proveedor'),
            ])
            ->actions([
                EditAction::make(),

                Action::make('price_history')
                    ->label('Historial')
                    ->icon('heroicon-o-clock')
                    ->color('gray')
                    ->modalHeading('Historial de precios')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->infolist(function (SupplierVariant $record): array {
                        $logs = $record->priceLogs()->with('user')->limit(20)->get();

                        if ($logs->isEmpty()) {
                            return [
                                TextEntry::make('empty')
                                    ->label('')
                                    ->default('Sin cambios de precio registrados.')
                                    ->columnSpanFull(),
                            ];
                        }

                        return [
                            RepeatableEntry::make('priceLogs')
                                ->label('')
                                ->schema([
                                    TextEntry::make('changed_at')
                                        ->label('Fecha')
                                        ->dateTime('d/m/Y H:i'),
                                    TextEntry::make('old_price')
                                        ->label('Anterior')
                                        ->money('ARS'),
                                    TextEntry::make('new_price')
                                        ->label('Nuevo')
                                        ->money('ARS'),
                                    TextEntry::make('purchase_unit_qty')
                                        ->label('Uds./compra')
                                        ->formatStateUsing(fn ($state) => $state > 1 ? "×{$state}" : '—'),
                                    TextEntry::make('user.name')
                                        ->label('Usuario')
                                        ->placeholder('Sistema'),
                                ])
                                ->columns(5),
                        ];
                    }),

                DeleteAction::make(),
            ])
            ->defaultSort('supplier.name');
    }
}
