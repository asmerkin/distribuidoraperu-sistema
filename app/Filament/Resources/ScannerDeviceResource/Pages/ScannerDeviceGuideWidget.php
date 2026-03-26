<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use Filament\Widgets\Widget;

class ScannerDeviceGuideWidget extends Widget
{
    protected string $view = 'filament.resources.scanner-device-resource.widgets.scanner-device-guide';

    protected int|string|array $columnSpan = 'full';
}
