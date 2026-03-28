<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Variant extends Model
{
    use HasUlids;

    protected $fillable = [
        'product_id',
        'sku',
        'barcode',
        'name',
        'images',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Variant $variant) {
            if ($variant->images) {
                foreach ($variant->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }
        });
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function optionValues(): BelongsToMany
    {
        return $this->belongsToMany(ProductOptionValue::class, 'variant_option_values');
    }

    public function inventoryLevels(): HasMany
    {
        return $this->hasMany(InventoryLevel::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    public function supplierVariants(): HasMany
    {
        return $this->hasMany(SupplierVariant::class);
    }

    public function getLabel(): string
    {
        $label = "[{$this->sku}] {$this->product->name}";

        if ($this->name !== 'Default') {
            $label .= " — {$this->name}";
        }

        return $label;
    }

    public function defaultSupplierVariant(): ?SupplierVariant
    {
        if ($this->relationLoaded('supplierVariants')) {
            return $this->supplierVariants->firstWhere('is_default', true);
        }

        return $this->supplierVariants()->where('is_default', true)->first();
    }

    public function getCostPriceAttribute(): float
    {
        $sv = $this->defaultSupplierVariant();

        if (! $sv) {
            return 0;
        }

        return (float) $sv->cost_price / max($sv->purchase_unit_qty, 1);
    }

    public function totalStock(): int
    {
        return $this->inventoryLevels()->sum('quantity');
    }

    public function pendingFromPurchaseOrders(): int
    {
        return (int) PurchaseOrderItem::where('variant_id', $this->id)
            ->whereHas('purchaseOrder', fn ($q) => $q->whereIn('status', [
                PurchaseOrderStatus::Confirmed,
                PurchaseOrderStatus::PartiallyReceived,
            ]))
            ->selectRaw('COALESCE(SUM(base_quantity_ordered - base_quantity_received), 0) as pending')
            ->value('pending');
    }
}
