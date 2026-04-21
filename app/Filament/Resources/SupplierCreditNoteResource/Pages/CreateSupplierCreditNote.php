<?php

namespace App\Filament\Resources\SupplierCreditNoteResource\Pages;

use App\Filament\Resources\SupplierCreditNoteResource;
use App\Services\SupplierCreditNoteService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSupplierCreditNote extends CreateRecord
{
    protected static string $resource = SupplierCreditNoteResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $supplierId = request()->query('supplier_id');
        $purchaseOrderId = request()->query('purchase_order_id');

        if ($supplierId) {
            $data['supplier_id'] = $supplierId;
        }
        if ($purchaseOrderId) {
            $data['purchase_order_id'] = $purchaseOrderId;
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        $items = $data['items'] ?? [];
        unset($data['items']);

        return app(SupplierCreditNoteService::class)->create(
            data: $data,
            items: $items,
            userId: auth()->id(),
        );
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->getRecord()]);
    }
}
