<?php

namespace App\Filament\Resources\ScannerDeviceResource\Pages;

use App\Filament\Resources\ScannerDeviceResource;
use App\Filament\Resources\ScannerDeviceResource\RelationManagers\StockMovementsRelationManager;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\IconEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ViewScannerDevice extends ViewRecord
{
    protected static string $resource = ScannerDeviceResource::class;

    public function getHeading(): string
    {
        return $this->getRecord()->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateQr')
                ->label('Generar QR')
                ->icon('heroicon-o-qr-code')
                ->color('info')
                ->url(fn () => ScannerDeviceResource::getUrl('qr', ['record' => $this->getRecord()])),

            EditAction::make(),
        ];
    }

    public function getRelationManagers(): array
    {
        return [
            StockMovementsRelationManager::class,
        ];
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Información general')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('name')
                                ->label('Nombre'),

                            TextEntry::make('location.name')
                                ->label('Ubicación'),

                            IconEntry::make('is_active')
                                ->label('Activo')
                                ->boolean(),

                            TextEntry::make('created_at')
                                ->label('Creado')
                                ->dateTime('d/m/Y H:i'),
                        ]),
                ]),

            Section::make('Vinculación')
                ->schema([
                    Grid::make(2)
                        ->schema([
                            TextEntry::make('user_agent')
                                ->label('Dispositivo')
                                ->formatStateUsing(fn ($record) => $record->deviceLabel())
                                ->placeholder('Sin vincular')
                                ->icon('heroicon-o-device-phone-mobile'),

                            TextEntry::make('last_used_at')
                                ->label('Último uso')
                                ->dateTime('d/m/Y H:i')
                                ->placeholder('Nunca'),
                        ]),
                ]),
        ]);
    }
}
