<?php

namespace App\Enums;

enum StockCountStatus: string
{
    case EnProgreso = 'en_progreso';
    case Completado = 'completado';

    public function label(): string
    {
        return match ($this) {
            self::EnProgreso => 'En progreso',
            self::Completado => 'Completado',
        };
    }
}
