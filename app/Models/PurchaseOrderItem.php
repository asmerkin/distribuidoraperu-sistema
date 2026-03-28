<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'purchase_order_id',
        'variant_id',
        'supplier_variant_id',
        'purchase_unit',
        'purchase_unit_qty',
        'quantity_ordered',
        'quantity_received',
        'base_quantity_ordered',
        'base_quantity_received',
        'unit_cost',
        'subtotal',
    ];

    protected function casts(): array
    {
        return [
            'quantity_ordered' => 'integer',
            'quantity_received' => 'integer',
            'base_quantity_ordered' => 'integer',
            'base_quantity_received' => 'integer',
            'purchase_unit_qty' => 'integer',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function supplierVariant(): BelongsTo
    {
        return $this->belongsTo(SupplierVariant::class);
    }
}
