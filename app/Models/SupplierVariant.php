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
    ];

    protected function casts(): array
    {
        return [
            'cost_price' => 'decimal:2',
            'is_default' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (SupplierVariant $model) {
            if ($model->isDirty('cost_price')) {
                SupplierVariantPriceLog::create([
                    'supplier_variant_id' => $model->id,
                    'old_price' => $model->getOriginal('cost_price'),
                    'new_price' => $model->cost_price,
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
