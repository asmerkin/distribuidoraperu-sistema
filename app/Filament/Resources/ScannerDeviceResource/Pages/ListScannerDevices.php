<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Widgets\Widget;

class ListScannerDevices extends ListRecords
{
    protected static string $resource = ScannerDeviceResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ScannerDeviceGuideWidget::class,
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
