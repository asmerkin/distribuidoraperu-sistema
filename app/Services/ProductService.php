<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SupplierVariant;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ProductService
{
    public function createProduct(
        string $name,
        string $unitOfMeasure = 'unit',
        ?string $categoryId = null,
        ?string $description = null,
        bool $isActive = true,
    ): Product {
        return Product::create([
            'name' => $name,
            'unit_of_measure' => $unitOfMeasure,
            'category_id' => $categoryId,
            'description' => $description,
            'is_active' => $isActive,
        ]);
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

    /**
     * Create a Product with a single default Variant (quick-add from PO).
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
        return DB::transaction(function () use ($name, $sku, $unitOfMeasure, $categoryId, $barcode, $isActive) {
            $product = $this->createProduct(
                name: $name,
                unitOfMeasure: $unitOfMeasure,
                categoryId: $categoryId,
                isActive: $isActive,
            );

            $variant = $this->createVariant(
                productId: $product->id,
                sku: $sku,
                barcode: $barcode,
            );

            return ['product' => $product, 'variant' => $variant];
        });
    }

    /**
     * Create a Product with multiple Variants (from ProductResource).
     *
     * @param  array<array{sku: string, name?: string, barcode?: string}>  $variants
     * @return array{product: Product, variants: array<Variant>}
     */
    public function createWithVariants(
        string $name,
        string $unitOfMeasure = 'unit',
        ?string $categoryId = null,
        ?string $description = null,
        bool $isActive = true,
        array $variants = [],
    ): array {
        return DB::transaction(function () use ($name, $unitOfMeasure, $categoryId, $description, $isActive, $variants) {
            $product = $this->createProduct(
                name: $name,
                unitOfMeasure: $unitOfMeasure,
                categoryId: $categoryId,
                description: $description,
                isActive: $isActive,
            );

            $createdVariants = [];
            foreach ($variants as $variant) {
                $createdVariants[] = $this->createVariant(
                    productId: $product->id,
                    sku: $variant['sku'],
                    name: $variant['name'] ?? 'Default',
                    barcode: $variant['barcode'] ?? null,
                );
            }

            return ['product' => $product, 'variants' => $createdVariants];
        });
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
