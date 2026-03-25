<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Borrador = 'borrador';
    case Enviada = 'enviada';
    case RecibidaParcial = 'recibida_parcial';
    case Recibida = 'recibida';
    case Cancelada = 'cancelada';

    public function label(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Enviada => 'Enviada',
            self::RecibidaParcial => 'Recibida parcial',
            self::Recibida => 'Recibida',
            self::Cancelada => 'Cancelada',
        };
    }
}
