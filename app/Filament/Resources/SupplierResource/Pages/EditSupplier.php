<?php

namespace App\Filament\Resources\SupplierResource\Pages;

use App\Filament\Resources\SupplierResource;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplier extends EditRecord
{
    protected static string $resource = SupplierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->before(function (DeleteAction $action) {
                    $record = $this->getRecord();
                    $relations = [];

                    if ($record->purchaseOrders()->exists()) {
                        $relations[] = 'órdenes de compra';
                    }
                    if ($record->invoices()->exists()) {
                        $relations[] = 'facturas';
                    }

                    if (! empty($relations)) {
                        Notification::make()
                            ->title('No se puede eliminar')
                            ->body('Este proveedor tiene ' . implode(' y ', $relations) . ' asociadas. Eliminá esos registros primero.')
                            ->danger()
                            ->send();

                        $action->cancel();
                    }
                }),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
