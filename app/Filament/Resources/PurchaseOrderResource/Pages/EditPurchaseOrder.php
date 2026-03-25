<?php

namespace App\Filament\Resources\PurchaseOrderResource\Pages;

use App\Filament\Resources\PurchaseOrderResource;
use Filament\Resources\Pages\EditRecord;

class EditPurchaseOrder extends EditRecord
{
    protected static string $resource = PurchaseOrderResource::class;

    protected function afterSave(): void
    {
        $this->getRecord()->update([
            'total' => $this->getRecord()->items()->sum('subtotal'),
        ]);
    }

    public function getRelationManagers(): array
    {
        return [];
    }
}
