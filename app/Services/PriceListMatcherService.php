<?php

namespace App\Services;

use App\Models\SupplierVariant;

class PriceListMatcherService
{
    /**
     * Match extracted price list items against existing supplier variants.
     *
     * @param  string  $supplierId
     * @param  array<int, array{code: string, description: string, price: float}>  $extractedItems
     * @return array{changed: array, unchanged: array, unmatched: array}
     */
    public function match(string $supplierId, array $extractedItems): array
    {
        $supplierVariants = SupplierVariant::where('supplier_id', $supplierId)
            ->with('variant.product')
            ->get()
            ->keyBy(fn (SupplierVariant $sv) => $this->normalize($sv->supplier_code));

        $changed = [];
        $unchanged = [];
        $unmatched = [];

        foreach ($extractedItems as $item) {
            $normalizedCode = $this->normalize($item['code']);

            if ($normalizedCode === '' || ! $supplierVariants->has($normalizedCode)) {
                $unmatched[] = [
                    'code' => $item['code'],
                    'barcode' => $item['barcode'] ?? '',
                    'description' => $item['description'],
                    'new_price' => $item['price'],
                ];
                continue;
            }

            $sv = $supplierVariants->get($normalizedCode);
            $currentPrice = (float) $sv->cost_price;
            $newPrice = $item['price'];

            $base = [
                'supplier_variant_id' => $sv->id,
                'code' => $item['code'],
                'barcode' => $item['barcode'] ?? '',
                'description' => $item['description'],
                'sku' => $sv->variant->sku,
                'product_name' => $sv->variant->product->name,
                'variant_name' => $sv->variant->name !== 'Default' ? $sv->variant->name : null,
                'purchase_unit' => $sv->purchase_unit,
                'purchase_unit_qty' => $sv->purchase_unit_qty,
                'current_price' => $currentPrice,
                'new_price' => $newPrice,
            ];

            if (abs($currentPrice - $newPrice) < 0.01) {
                $unchanged[] = $base;
            } else {
                $pctChange = $currentPrice > 0
                    ? round(($newPrice - $currentPrice) / $currentPrice * 100, 1)
                    : null;
                $base['pct_change'] = $pctChange;
                $changed[] = $base;
            }
        }

        return [
            'changed' => $changed,
            'unchanged' => $unchanged,
            'unmatched' => $unmatched,
        ];
    }

    /**
     * Normalize a supplier code for comparison.
     */
    private function normalize(?string $code): string
    {
        if ($code === null || $code === '') {
            return '';
        }

        // Trim, uppercase, strip dashes/dots/spaces
        return preg_replace('/[\s\-\.]+/', '', strtoupper(trim($code)));
    }
}
