<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Compra = 'compra';
    case AjusteConteo = 'ajuste_conteo';
    case Merma = 'merma';
    case Devolucion = 'devolucion';
    case TransferenciaEntrada = 'transferencia_entrada';
    case TransferenciaSalida = 'transferencia_salida';

    public function label(): string
    {
        return match ($this) {
            self::Compra => 'Compra',
            self::AjusteConteo => 'Ajuste por conteo',
            self::Merma => 'Merma',
            self::Devolucion => 'Devolución',
            self::TransferenciaEntrada => 'Transferencia (entrada)',
            self::TransferenciaSalida => 'Transferencia (salida)',
        };
    }
}
