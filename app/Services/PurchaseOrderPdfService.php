<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\Setting;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDF;

class PurchaseOrderPdfService
{
    public function generate(PurchaseOrder $purchaseOrder): DomPDF
    {
        $purchaseOrder->loadMissing('supplier', 'items.variant.product', 'location');

        $company = [
            'name' => Setting::get('company_name'),
            'address' => Setting::get('company_address'),
            'phone' => Setting::get('company_phone'),
            'tax_id' => Setting::get('company_tax_id'),
            'email' => Setting::get('company_email'),
        ];

        return Pdf::loadView('pdf.purchase-order', [
            'purchaseOrder' => $purchaseOrder,
            'company' => $company,
        ])->setPaper('a4')
            ->setOption('isHtml5ParserEnabled', true);
    }

    public function generateContent(PurchaseOrder $purchaseOrder): string
    {
        return $this->generate($purchaseOrder)->output();
    }
}
