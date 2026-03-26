<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use App\Models\ScannerDevice;
use chillerlan\QRCode\Output\QROutputInterface;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Str;

class ShowScannerDeviceQr extends Page
{
    protected static string $resource = ScannerDeviceResource::class;

    protected string $view = 'filament.resources.scanner-device-resource.pages.show-scanner-device-qr';

    protected static ?string $title = 'Vincular Dispositivo';

    public ?ScannerDevice $record = null;

    public string $rawOtp = '';

    public string $qrSvg = '';

    public string $expiresAt = '';

    public bool $linked = false;

    public function mount(ScannerDevice $record): void
    {
        $this->record = $record;

        $this->rawOtp = Str::random(32);

        $this->record->update([
            'otp' => hash('sha256', $this->rawOtp),
            'otp_expires_at' => now()->addMinutes(15),
            'token' => null,
        ]);

        $qrData = json_encode([
            'otp' => $this->rawOtp,
            'url' => url('/api/scanner/auth'),
        ]);

        $options = new QROptions([
            'outputType' => QROutputInterface::MARKUP_SVG,
            'scale' => 10,
            'quietzoneSize' => 2,
        ]);

        $this->qrSvg = (new QRCode($options))->render($qrData);

        $this->expiresAt = now()->addMinutes(15)->format('H:i');
    }

    public function checkLinked(): void
    {
        if ($this->linked) {
            return;
        }

        $this->record->refresh();

        if ($this->record->token && $this->record->otp === null) {
            $this->linked = true;

            Notification::make()
                ->success()
                ->title('Dispositivo vinculado')
                ->body("{$this->record->name} se vinculo correctamente.")
                ->send();
        }
    }

    public function getFinishUrl(): string
    {
        return ScannerDeviceResource::getUrl('view', ['record' => $this->record]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Volver')
                ->url(ScannerDeviceResource::getUrl('view', ['record' => $this->record]))
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
