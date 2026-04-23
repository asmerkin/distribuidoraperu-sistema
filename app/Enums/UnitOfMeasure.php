<?php

namespace App\Enums;

enum UnitOfMeasure: string
{
    case Unit = 'unit';
    case Box = 'box';
    case Ream = 'ream';
    case Pack = 'pack';
    case Roll = 'roll';
    case Meter = 'meter';
    case Kg = 'kg';

    public function label(): string
    {
        return __('enums.unit_of_measure.'.$this->value);
    }
}
