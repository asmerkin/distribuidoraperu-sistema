<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierVariant extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_id',
        'variant_id',
        'supplier_code',
        'cost_price',
        'upc',
        'is_default',
        'purchase_unit',
        'purchase_unit_qty',
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'is_default' => 'boolean',
            'purchase_unit_qty' => 'integer',
        ];
    }

    public function getBaseUnitCostAttribute(): float
    {
        return (float) $this->cost_price / max($this->purchase_unit_qty, 1);
    }

    protected static function booted(): void
    {
        static::updating(function (SupplierVariant $model) {
            if ($model->isDirty('cost_price')) {
                SupplierVariantPriceLog::create([
                    'supplier_variant_id' => $model->id,
                    'old_price' => $model->getOriginal('cost_price'),
                    'new_price' => $model->cost_price,
                    'purchase_unit_qty' => $model->purchase_unit_qty ?? 1,
                    'changed_at' => now(),
                    'user_id' => auth()->id(),
                ]);
            }
        });

        static::saving(function (SupplierVariant $model) {
            if ($model->is_default && $model->isDirty('is_default')) {
                static::where('variant_id', $model->variant_id)
                    ->where('id', '!=', $model->id)
                    ->update(['is_default' => false]);
            }
        });

    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function priceLogs(): HasMany
    {
        return $this->hasMany(SupplierVariantPriceLog::class)->orderByDesc('changed_at');
    }
}
