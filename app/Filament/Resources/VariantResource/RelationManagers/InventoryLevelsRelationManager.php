<?php

namespace App\Filament\Resources\VariantResource\RelationManagers;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Services\InventoryService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class InventoryLevelsRelationManager extends RelationManager
{
    protected static string $relationship = 'inventoryLevels';

    protected static ?string $title = 'Inventario';

    protected static ?string $modelLabel = 'ubicación';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Stock')
                    ->alignCenter()
                    ->sortable()
                    ->color(fn (InventoryLevel $record) => $record->isLowStock() ? 'danger' : null),

                TextColumn::make('min_stock')
                    ->label('Stock mínimo')
                    ->alignCenter()
                    ->placeholder('0'),
            ])
            ->headerActions([
                Action::make('add_location')
                    ->label('Agregar a ubicación')
                    ->icon('heroicon-o-map-pin')
                    ->color('success')
                    ->modalHeading('Agregar a ubicación')
                    ->modalSubmitActionLabel('Agregar')
                    ->form(function (): array {
                        $variant = $this->getOwnerRecord();
                        $existingLocationIds = $variant->inventoryLevels()->pluck('location_id');

                        return [
                            Select::make('location_id')
                                ->label('Ubicación')
                                ->options(
                                    Location::query()
                                        ->where('is_active', true)
                                        ->whereNotIn('id', $existingLocationIds)
                                        ->orderBy('name')
                                        ->pluck('name', 'id')
                                )
                                ->searchable()
                                ->preload()
                                ->required(),

                            TextInput::make('quantity')
                                ->label('Stock inicial')
                                ->integer()
                                ->default(0)
                                ->minValue(0)
                                ->required(),

                            TextInput::make('min_stock')
                                ->label('Stock mínimo')
                                ->integer()
                                ->default(0)
                                ->minValue(0),
                        ];
                    })
                    ->action(function (array $data) {
                        $variant = $this->getOwnerRecord();
                        $location = Location::find($data['location_id']);
                        $qty = (int) $data['quantity'];

                        InventoryLevel::create([
                            'variant_id' => $variant->id,
                            'location_id' => $data['location_id'],
                            'quantity' => 0,
                            'min_stock' => (int) ($data['min_stock'] ?? 0),
                        ]);

                        if ($qty > 0) {
                            app(InventoryService::class)->recordMovement(
                                variant: $variant,
                                location: $location,
                                type: StockMovementType::In,
                                reason: StockMovementReason::StockCount,
                                quantity: $qty,
                                notes: 'Stock inicial al agregar ubicación',
                                userId: auth()->id(),
                            );
                        }

                        Notification::make()
                            ->title('Ubicación agregada')
                            ->body("{$location->name} agregada con stock {$qty}.")
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Action::make('adjust')
                    ->label('Ajustar')
                    ->icon('heroicon-o-adjustments-horizontal')
                    ->color('warning')
                    ->modalHeading(fn (InventoryLevel $record) => "Ajustar stock — {$record->location->name}")
                    ->modalDescription(fn (InventoryLevel $record) => "Stock actual: {$record->quantity}")
                    ->modalSubmitActionLabel('Aplicar')
                    ->form(fn (InventoryLevel $record) => [
                        TextInput::make('new_quantity')
                            ->label('Nueva cantidad')
                            ->integer()
                            ->default($record->quantity)
                            ->minValue(0)
                            ->required(),

                        Textarea::make('notes')
                            ->label('Motivo del ajuste')
                            ->rows(2),
                    ])
                    ->action(function (InventoryLevel $record, array $data) {
                        $newQty = (int) $data['new_quantity'];
                        $diff = $newQty - $record->quantity;

                        if ($diff === 0) {
                            Notification::make()
                                ->title('Sin cambios')
                                ->info()
                                ->send();
                            return;
                        }

                        app(InventoryService::class)->recordMovement(
                            variant: $record->variant,
                            location: $record->location,
                            type: StockMovementType::Adjustment,
                            reason: StockMovementReason::StockCount,
                            quantity: $diff,
                            notes: $data['notes'] ?? null,
                            userId: auth()->id(),
                        );

                        Notification::make()
                            ->title('Stock ajustado')
                            ->body("{$record->location->name}: {$record->quantity} → {$newQty}")
                            ->success()
                            ->send();
                    }),

                Action::make('min_stock')
                    ->label('Mínimo')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('gray')
                    ->modalHeading(fn (InventoryLevel $record) => "Stock mínimo — {$record->location->name}")
                    ->modalSubmitActionLabel('Guardar')
                    ->form(fn (InventoryLevel $record) => [
                        TextInput::make('min_stock')
                            ->label('Stock mínimo')
                            ->integer()
                            ->default($record->min_stock)
                            ->minValue(0)
                            ->required(),
                    ])
                    ->action(function (InventoryLevel $record, array $data) {
                        $record->update(['min_stock' => (int) $data['min_stock']]);

                        Notification::make()
                            ->title('Stock mínimo actualizado')
                            ->success()
                            ->send();
                    }),

                DeleteAction::make()
                    ->label('Quitar')
                    ->modalHeading(fn (InventoryLevel $record) => "Quitar de {$record->location->name}")
                    ->modalDescription(fn (InventoryLevel $record) => $record->quantity > 0
                        ? "Esta ubicación tiene {$record->quantity} unidades. Se registrará una salida de stock."
                        : 'Se quitará esta ubicación del inventario.')
                    ->before(function (InventoryLevel $record) {
                        if ($record->quantity > 0) {
                            app(InventoryService::class)->recordMovement(
                                variant: $record->variant,
                                location: $record->location,
                                type: StockMovementType::Out,
                                reason: StockMovementReason::Shrinkage,
                                quantity: $record->quantity,
                                notes: 'Ubicación removida del inventario',
                                userId: auth()->id(),
                            );
                        }
                    }),
            ])
            ->defaultSort('location.name')
            ->emptyStateHeading('Sin ubicaciones')
            ->emptyStateDescription('Usá "Agregar a ubicación" para habilitar el inventario en una ubicación.');
    }
}
