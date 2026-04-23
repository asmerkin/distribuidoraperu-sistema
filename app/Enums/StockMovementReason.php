<?php

namespace App\Enums;

enum StockMovementReason: string
{
    case Purchase = 'purchase';
    case StockCount = 'stock_count';
    case Shrinkage = 'shrinkage';
    case Return = 'return';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';

    public function label(): string
    {
        return __('enums.stock_movement_reason.'.$this->value);
    }
}
