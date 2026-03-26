<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use App\Models\ScannerDevice;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class ShowScannerDeviceQr extends Page
{
    protected static string $resource = ScannerDeviceResource::class;

    protected string $view = 'filament.resources.scanner-device-resource.pages.show-scanner-device-qr';

    protected static ?string $title = 'Vincular Dispositivo';

    public ?ScannerDevice $record = null;

    public string $rawOtp = '';

    public string $qrData = '';

    public string $expiresAt = '';

    public function mount(ScannerDevice $record): void
    {
        $this->record = $record;

        $this->rawOtp = Str::random(32);

        $this->record->update([
            'otp' => hash('sha256', $this->rawOtp),
            'otp_expires_at' => now()->addMinutes(15),
            'token' => null,
        ]);

        $this->qrData = json_encode([
            'otp' => $this->rawOtp,
            'url' => url('/api/scanner/auth'),
        ]);

        $this->expiresAt = now()->addMinutes(15)->format('H:i');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver')
                ->url(ScannerDeviceResource::getUrl())
                ->color('gray'),

            Action::make('regenerate')
                ->label('Regenerar QR')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    return redirect(ScannerDeviceResource::getUrl('qr', ['record' => $this->record]));
                }),
        ];
    }
}
