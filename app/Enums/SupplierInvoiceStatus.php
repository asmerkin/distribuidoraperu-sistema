<?php

namespace App\Enums;

enum SupplierInvoiceStatus: string
{
    case Unpaid = 'unpaid';
    case PartiallyPaid = 'partially_paid';
    case Paid = 'paid';

    public function label(): string
    {
        return __('enums.supplier_invoice_status.'.$this->value);
    }
}
