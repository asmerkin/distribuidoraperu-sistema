<?php

namespace App\Enums;

enum StockMovementType: string
{
    case Entrada = 'entrada';
    case Salida = 'salida';
    case Ajuste = 'ajuste';
    case Transferencia = 'transferencia';

    public function label(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Salida => 'Salida',
            self::Ajuste => 'Ajuste',
            self::Transferencia => 'Transferencia',
        };
    }
}
