<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditScannerDevice extends EditRecord
{
    protected static string $resource = ScannerDeviceResource::class;

    protected function getRedirectUrl(): string
    {
        return ScannerDeviceResource::getUrl('view', ['record' => $this->getRecord()]);
    }

    protected function getFormActions(): array
    {
        return [
            ...parent::getFormActions(),
            DeleteAction::make()
                ->label('Eliminar dispositivo')
                ->link(),
        ];
    }
}
