<?php

namespace App\Models;

use App\Enums\PurchaseOrderStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseOrder extends Model
{
    use HasUlids;

    protected static function booted(): void
    {
        static::creating(function (PurchaseOrder $po) {
            if (empty($po->po_number)) {
                $last = static::query()->lockForUpdate()->max('po_number');
                $next = $last ? ((int) substr($last, 3)) + 1 : 1;
                $po->po_number = 'PO-' . str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            if (empty($po->status)) {
                $po->status = PurchaseOrderStatus::Borrador;
            }

            if (empty($po->user_id) && auth()->check()) {
                $po->user_id = auth()->id();
            }
        });
    }

    protected $fillable = [
        'po_number',
        'supplier_id',
        'location_id',
        'status',
        'order_date',
        'expected_date',
        'total',
        'notes',
        'notes_for_supplier',
        'user_id',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => PurchaseOrderStatus::class,
            'order_date' => 'date',
            'expected_date' => 'date',
            'total' => 'decimal:2',
            'sent_at' => 'datetime',
        ];
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }
}
