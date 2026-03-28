<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use App\Models\SupplierVariant;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class PurchaseOrderPdfService
{
    public function generate(PurchaseOrder $purchaseOrder): DomPDF
    {
        $purchaseOrder->loadMissing('supplier', 'items.variant.product', 'items.supplierVariant', 'location', 'user');

        $company = [
            'name' => Setting::get('company_name'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'tax_id' => Setting::get('company_tax_id'),
            'email' => Setting::get('company_email'),
        ];

        // Build supplier code lookup: variant_id => supplier_code (fallback for legacy items without supplier_variant_id)
        $variantIds = $purchaseOrder->items->pluck('variant_id');
        $supplierCodes = SupplierVariant::where('supplier_id', $purchaseOrder->supplier_id)
            ->whereIn('variant_id', $variantIds)
            ->pluck('supplier_code', 'variant_id')
            ->toArray();

        return Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'company' => $company,
            'supplierCodes' => $supplierCodes,
        ])->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true);
    }

    public function generateContent(PurchaseOrder $purchaseOrder): string
    {
        return $this->generate($purchaseOrder)->output();
    }
}
