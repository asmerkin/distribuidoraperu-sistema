<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class SupplierCreditNote extends Model
{
    use HasUlids;

    protected $fillable = [
        'credit_note_number',
        'supplier_document_number',
        'supplier_id',
        'purchase_order_id',
        'date',
        'reason',
        'notes',
        'total',
        'attachment',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'total' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SupplierCreditNote $cn) {
            if (empty($cn->credit_note_number)) {
                $last = static::query()
                    ->lockForUpdate()
                    ->selectRaw('MAX(CAST(SUBSTRING(credit_note_number, 4) AS UNSIGNED)) as max_num')
                    ->value('max_num');
                $next = ($last ?? 0) + 1;
                $cn->credit_note_number = 'NC-'.str_pad($next, 5, '0', STR_PAD_LEFT);
            }

            if (empty($cn->user_id) && auth()->check()) {
                $cn->user_id = auth()->id();
            }
        });
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(SupplierCreditNoteItem::class);
    }

    public function appliedPayments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class, 'supplier_credit_note_id');
    }

    public function stockMovements(): MorphMany
    {
        return $this->morphMany(StockMovement::class, 'reference');
    }

    public function getAmountAppliedAttribute(): float
    {
        return round((float) $this->appliedPayments()->sum('amount'), 2);
    }

    public function getBalanceAttribute(): float
    {
        return round((float) $this->total - $this->amount_applied, 2);
    }
}
