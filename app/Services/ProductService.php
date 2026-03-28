<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SupplierVariant;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    /**
     * Create a Product with a single default Variant.
     *
     * @return array{product: Product, variant: Variant}
     */
    public function createWithVariant(
        string $name,
        string $sku,
        string $unitOfMeasure = 'unit',
        ?string $categoryId = null,
        ?string $barcode = null,
        bool $isActive = true,
    ): array {
        if (Variant::where('sku', $sku)->exists()) {
            throw ValidationException::withMessages([
                'sku' => "El SKU '{$sku}' ya existe.",
            ]);
        }

        return DB::transaction(function () use ($name, $sku, $unitOfMeasure, $categoryId, $barcode, $isActive) {
            $product = Product::create([
                'name' => $name,
                'unit_of_measure' => $unitOfMeasure,
                'category_id' => $categoryId,
                'is_active' => $isActive,
            ]);

            $variant = Variant::create([
                'product_id' => $product->id,
                'sku' => $sku,
                'name' => 'Default',
                'barcode' => $barcode,
                'is_active' => true,
            ]);

            return ['product' => $product, 'variant' => $variant];
        });
    }

    public function createVariant(
        string $productId,
        string $sku,
        string $name = 'Default',
        ?string $barcode = null,
    ): Variant {
        if (Variant::where('sku', $sku)->exists()) {
            throw ValidationException::withMessages([
                'sku' => "El SKU '{$sku}' ya existe.",
            ]);
        }

        return Variant::create([
            'product_id' => $productId,
            'sku' => $sku,
            'name' => $name,
            'barcode' => $barcode,
            'is_active' => true,
        ]);
    }

    public function createSupplierVariant(
        string $variantId,
        string $supplierId,
        string $supplierCode,
        float $costPrice,
        ?string $purchaseUnit = null,
        int $purchaseUnitQty = 1,
        bool $isDefault = false,
    ): SupplierVariant {
        return SupplierVariant::create([
            'variant_id' => $variantId,
            'supplier_id' => $supplierId,
            'supplier_code' => $supplierCode,
            'cost_price' => $costPrice,
            'purchase_unit' => $purchaseUnit,
            'purchase_unit_qty' => $purchaseUnitQty,
            'is_default' => $isDefault,
        ]);
    }
}
