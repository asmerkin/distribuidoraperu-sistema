<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScannerDeviceResource\Pages;
use App\Models\ScannerDevice;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;

class ScannerDeviceResource extends Resource
{
    protected static ?string $model = ScannerDevice::class;

    protected static ?string $modelLabel = 'Dispositivo Scanner';

    protected static ?string $pluralModelLabel = 'Dispositivos Scanner';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-device-phone-mobile';

    protected static string|\UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->label('Nombre')
                ->placeholder('Tablet Depósito 1')
                ->required()
                ->maxLength(255)
                ->columnSpanFull(),

            Select::make('location_id')
                ->label('Ubicación')
                ->relationship('location', 'name', fn ($query) => $query->where('is_active', true))
                ->required()
                ->searchable()
                ->preload()
                ->columnSpanFull(),

            Toggle::make('is_active')
                ->label('Activo')
                ->default(true)
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('location.name')
                    ->label('Ubicación')
                    ->sortable(),

                TextColumn::make('user_agent')
                    ->label('Dispositivo')
                    ->formatStateUsing(fn (ScannerDevice $record) => $record->deviceLabel())
                    ->placeholder('Sin vincular')
                    ->icon('heroicon-o-device-phone-mobile')
                    ->toggleable(),

                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),

                TextColumn::make('last_used_at')
                    ->label('Último uso')
                    ->dateTime('d/m/Y H:i')
                    ->placeholder('Nunca')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Creado')
                    ->dateTime('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('name')
            ->recordAction('view')
            ->actions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScannerDevices::route('/'),
            'create' => Pages\CreateScannerDevice::route('/create'),
            'view' => Pages\ViewScannerDevice::route('/{record}'),
            'edit' => Pages\EditScannerDevice::route('/{record}/edit'),
            'qr' => Pages\ShowScannerDeviceQr::route('/{record}/qr'),
        ];
    }
}
