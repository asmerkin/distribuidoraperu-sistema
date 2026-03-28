<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePurchaseOrder extends CreateRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterCreate(): void
    {
        $record = $this->getRecord();
        $record->load('items');

        foreach ($record->items as $item) {
            $item->updateQuietly([
                'base_quantity_ordered' => $item->quantity_ordered * max($item->purchase_unit_qty, 1),
            ]);
        }

        $record->update([
            'total' => $record->items()->sum('subtotal'),
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
