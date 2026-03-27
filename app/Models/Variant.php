<?php

namespace App\Models;

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

    public function defaultSupplierVariant(): ?SupplierVariant
    {
        return $this->supplierVariants()->where('is_default', true)->first();
    }

    public function getCostPriceAttribute(): ?float
    {
        return (float) ($this->defaultSupplierVariant()?->cost_price ?? 0);
    }

    public function totalStock(): int
    {
        return $this->inventoryLevels()->sum('quantity');
    }
}
