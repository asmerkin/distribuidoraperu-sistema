<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use Filament\Resources\Pages\CreateRecord;

class CreateScannerDevice extends CreateRecord
{
    protected static string $resource = ScannerDeviceResource::class;

    protected function getRedirectUrl(): string
    {
        return ScannerDeviceResource::getUrl('qr', ['record' => $this->record]);
    }
}
