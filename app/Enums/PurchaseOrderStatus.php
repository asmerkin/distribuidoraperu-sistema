<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case Confirmed = 'confirmed';
    case Rejected = 'rejected';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return __('enums.purchase_order_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Sent => 'info',
            self::Confirmed, self::Received => 'success',
            self::Rejected, self::Cancelled => 'danger',
            self::PartiallyReceived => 'warning',
        };
    }
}
