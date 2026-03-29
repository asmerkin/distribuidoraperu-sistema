<?php

namespace App\Enums;

enum PriceListImportStatus: string
{
    case Uploading = 'uploading';
    case Parsing = 'parsing';
    case Draft = 'draft';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    public function label(): string
    {
        return __('enums.price_list_import_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::Uploading, self::Parsing, self::Processing => 'info',
            self::Draft => 'warning',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Uploading => 'heroicon-o-arrow-up-tray',
            self::Parsing => 'heroicon-o-cpu-chip',
            self::Draft => 'heroicon-o-pencil-square',
            self::Processing => 'heroicon-o-arrow-path',
            self::Completed => 'heroicon-o-check-circle',
            self::Failed => 'heroicon-o-x-circle',
        };
    }
}
