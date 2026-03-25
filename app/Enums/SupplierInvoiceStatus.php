<?php

namespace App\Enums;

enum SupplierInvoiceStatus: string
{
    case Impaga = 'impaga';
    case PagoParcial = 'pago_parcial';
    case Pagada = 'pagada';

    public function label(): string
    {
        return match ($this) {
            self::Impaga => 'Impaga',
            self::PagoParcial => 'Pago parcial',
            self::Pagada => 'Pagada',
        };
    }
}
