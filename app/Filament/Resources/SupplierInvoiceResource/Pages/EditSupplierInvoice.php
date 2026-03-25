<?php

namespace App\Filament\Resources\SupplierInvoiceResource\Pages;

use App\Filament\Resources\SupplierInvoiceResource;
use App\Filament\Resources\SupplierResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditSupplierInvoice extends EditRecord
{
    protected static string $resource = SupplierInvoiceResource::class;

    protected function getHeaderActions(): array
    {
        $record = $this->getRecord();

        return [
            Action::make('eliminar')
                ->label('Eliminar factura')
                ->color('danger')
                ->link()
                ->requiresConfirmation()
                ->modalHeading('Eliminar factura')
                ->modalDescription(fn () => $record->payments()->count() > 0
                    ? "Esta factura tiene {$record->payments()->count()} pago(s) registrado(s). Se eliminarán todos los pagos asociados."
                    : '¿Eliminar esta factura?')
                ->action(function () use ($record) {
                    $supplierId = $record->supplier_id;
                    $disk = \Illuminate\Support\Facades\Storage::disk('public');

                    foreach ($record->payments as $payment) {
                        if (filled($payment->attachment)) {
                            $disk->delete($payment->attachment);
                        }
                    }
                    $record->payments()->delete();

                    if (filled($record->attachment)) {
                        $disk->delete($record->attachment);
                    }
                    $record->delete();

                    Notification::make()
                        ->title('Factura eliminada')
                        ->success()
                        ->send();

                    $this->redirect(SupplierResource::getUrl('view', ['record' => $supplierId]));
                }),
        ];
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
