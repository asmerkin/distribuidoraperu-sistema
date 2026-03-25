<?php

namespace App\Enums;

enum UnitOfMeasure: string
{
    case Unidad = 'unidad';
    case Caja = 'caja';
    case Resma = 'resma';
    case Pack = 'pack';
    case Rollo = 'rollo';
    case Metro = 'metro';
    case Kg = 'kg';

    public function label(): string
    {
        return match ($this) {
            self::Unidad => 'Unidad',
            self::Caja => 'Caja',
            self::Resma => 'Resma',
            self::Pack => 'Pack',
            self::Rollo => 'Rollo',
            self::Metro => 'Metro',
            self::Kg => 'Kilogramo',
        };
    }
}
