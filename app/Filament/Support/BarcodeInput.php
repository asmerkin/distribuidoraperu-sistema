<?php

namespace App\Filament\Support;

use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;

class BarcodeInput
{
    /**
     * Create a TextInput for barcode with a camera scanner suffix action.
     */
    public static function make(string $name = 'barcode', string $label = 'Código de Barras'): TextInput
    {
        return TextInput::make($name)
            ->label($label)
            ->maxLength(255)
            ->suffixAction(
                Action::make("scan_{$name}")
                    ->icon('heroicon-o-camera')
                    ->color('gray')
                    ->tooltip('Escanear código de barras')
                    ->alpineClickHandler(fn ($component): string => "\$dispatch('open-barcode-scanner', { statePath: '{$component->getStatePath()}', wireId: \$wire.\$id })")
            );
    }
}
