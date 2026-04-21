<?php

namespace App\Filament\Resources\SupplierCreditNoteResource\Pages;

use App\Filament\Resources\SupplierCreditNoteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSupplierCreditNotes extends ListRecords
{
    protected static string $resource = SupplierCreditNoteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
