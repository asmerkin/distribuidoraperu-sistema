<?php

namespace App\Enums;

enum StockCountStatus: string
{
    case InProgress = 'in_progress';
    case Completed = 'completed';

    public function label(): string
    {
        return __('enums.stock_count_status.' . $this->value);
    }
}
