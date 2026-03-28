<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierVariantPriceLog extends Model
{
    use HasUlids;

    public $timestamps = false;

    protected $fillable = [
        'supplier_variant_id',
        'old_price',
        'new_price',
        'purchase_unit_qty',
        'changed_at',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'old_price' => 'decimal:2',
            'new_price' => 'decimal:2',
            'purchase_unit_qty' => 'integer',
            'changed_at' => 'datetime',
        ];
    }

    public function supplierVariant(): BelongsTo
    {
        return $this->belongsTo(SupplierVariant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
