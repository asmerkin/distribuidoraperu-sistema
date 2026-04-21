<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SupplierResource\Pages;
use App\Filament\Resources\SupplierResource\RelationManagers\CreditNotesRelationManager;
use App\Filament\Resources\SupplierResource\RelationManagers\InvoicesRelationManager;
use App\Filament\Resources\SupplierResource\RelationManagers\SupplierVariantsRelationManager;
use App\Filament\Resources\SupplierResource\RelationManagers\PaymentsRelationManager;
use App\Filament\Resources\SupplierResource\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Supplier;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class SupplierResource extends Resource
{
    protected static ?string $model = Supplier::class;

    protected static ?string $modelLabel = 'Proveedor';

    protected static ?string $pluralModelLabel = 'Proveedores';

    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-truck';

    protected static string | \UnitEnum | null $navigationGroup = 'Compras';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'tax_id', 'contact_name', 'email'];
    }

    public static function getGlobalSearchResultDetails(\Illuminate\Database\Eloquent\Model $record): array
    {
        return array_filter([
            'CUIT' => $record->tax_id,
            'Contacto' => $record->contact_name,
            'Email' => $record->email,
        ]);
    }

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')
                ->schema([
                    TextInput::make('name')
                        ->label('Razón social')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),

                    TextInput::make('tax_id')
                        ->label('CUIT')
                        ->maxLength(50)
                        ->placeholder('20-12345678-9'),
                ]),

            Section::make('Contacto')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextInput::make('contact_name')
                                ->label('Persona de contacto')
                                ->maxLength(255),

                            TextInput::make('phone')
                                ->label('Teléfono')
                                ->tel()
                                ->maxLength(50),

                            TextInput::make('email')
                                ->label('Email')
                                ->email()
                                ->maxLength(255),

                            TextInput::make('address')
                                ->label('Dirección')
                                ->maxLength(500),
                        ]),
                ]),

            Section::make('Notas')
                ->schema([
                    Textarea::make('payment_terms')
                        ->label('Condiciones de pago')
                        ->rows(3)
                        ->columnSpanFull(),

                    Textarea::make('notes')
                        ->label('Notas internas')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->collapsible(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Razón social')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tax_id')
                    ->label('CUIT')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('contact_name')
                    ->label('Contacto')
                    ->placeholder('—')
                    ->searchable(),

                TextColumn::make('email')
                    ->label('Email')
                    ->placeholder('—')
                    ->searchable()
                    ->copyable(),

                TextColumn::make('phone')
                    ->label('Teléfono')
                    ->placeholder('—'),

                TextColumn::make('purchase_orders_count')
                    ->label('OC')
                    ->counts('purchaseOrders')
                    ->badge()
                    ->alignCenter()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordUrl(fn ($record) => $record->trashed() ? null : static::getUrl('view', ['record' => $record]))
            ->actions([
                EditAction::make()
                    ->visible(fn ($record) => ! $record->trashed()),
                RestoreAction::make(),
                \Filament\Actions\DeleteAction::make()
                    ->label('Archivar')
                    ->modalHeading('Archivar proveedor')
                    ->modalDescription('El proveedor quedará archivado y no aparecerá en los filtros por defecto. Podés restaurarlo en cualquier momento.')
                    ->visible(fn ($record) => ! $record->trashed()),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Archivados'),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->label('Archivar seleccionados'),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getWidgets(): array
    {
        return [
            \App\Filament\Resources\SupplierResource\Widgets\SupplierStatsWidget::class,
        ];
    }

    public static function getRelations(): array
    {
        return [
            SupplierVariantsRelationManager::class,
            InvoicesRelationManager::class,
            PurchaseOrdersRelationManager::class,
            CreditNotesRelationManager::class,
            PaymentsRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSuppliers::route('/'),
            'create' => Pages\CreateSupplier::route('/create'),
            'view' => Pages\ViewSupplier::route('/{record}'),
            'edit' => Pages\EditSupplier::route('/{record}/edit'),
        ];
    }
}
