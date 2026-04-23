<?php

namespace App\Filament\Resources\LocationResource\Pages;

use App\Filament\Resources\LocationResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditLocation extends EditRecord
{
    protected static string $resource = LocationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();
                    $relations = [];

                    if ($record->inventoryLevels()->exists()) {
                        $relations[] = 'inventario';
                    }
                    if ($record->stockMovements()->exists()) {
                        $relations[] = 'movimientos de stock';
                    }

                    if (! empty($relations)) {
                        Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Esta ubicación tiene '.implode(' y ', $relations).' asociados. Eliminá esos registros primero.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }
}
