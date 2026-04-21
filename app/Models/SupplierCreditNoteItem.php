<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierCreditNoteItem extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_credit_note_id',
        'variant_id',
        'location_id',
        'purchase_unit',
        'purchase_unit_qty',
        'quantity',
        'base_quantity',
        'unit_cost',
        'subtotal',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'purchase_unit_qty' => 'integer',
            'quantity' => 'integer',
            'base_quantity' => 'integer',
            'unit_cost' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(SupplierCreditNote::class, 'supplier_credit_note_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }
}
