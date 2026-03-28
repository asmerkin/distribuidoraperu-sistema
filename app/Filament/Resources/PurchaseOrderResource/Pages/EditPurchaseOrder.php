<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterSave(): void
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
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
