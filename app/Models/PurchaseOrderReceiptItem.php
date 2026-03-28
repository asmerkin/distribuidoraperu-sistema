<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderReceiptItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'purchase_order_receipt_id',
        'purchase_order_item_id',
        'variant_id',
        'quantity_received',
        'base_quantity_received',
        'unit_cost',
    ];

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
            'base_quantity_received' => 'integer',
            'unit_cost' => 'decimal:2',
        ];
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderReceipt::class, 'purchase_order_receipt_id');
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }
}
