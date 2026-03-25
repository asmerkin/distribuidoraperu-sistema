<?php

namespace App\Models;

use App\Enums\SupplierInvoiceStatus;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SupplierInvoice extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_id',
        'purchase_order_id',
        'invoice_number',
        'date',
        'due_date',
        'total',
        'amount_paid',
        'status',
        'attachment',
        'notes',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'date' => 'date',
            'due_date' => 'date',
            'total' => 'decimal:2',
            'amount_paid' => 'decimal:2',
            'status' => SupplierInvoiceStatus::class,
        ];
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

    public function payments(): HasMany
    {
        return $this->hasMany(SupplierPayment::class);
    }

    public function getBalanceAttribute(): float
    {
        return round((float) $this->total - (float) $this->amount_paid, 2);
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date
            && $this->due_date->isPast()
            && $this->status !== SupplierInvoiceStatus::Pagada;
    }

    public function getDisplayStatusAttribute(): string
    {
        if ($this->is_overdue) {
            return 'Vencida';
        }

        return $this->status->label();
    }

    public function getDisplayStatusColorAttribute(): string
    {
        if ($this->is_overdue) {
            return 'danger';
        }

        return match ($this->status) {
            SupplierInvoiceStatus::Impaga => 'gray',
            SupplierInvoiceStatus::PagoParcial => 'warning',
            SupplierInvoiceStatus::Pagada => 'success',
        };
    }

    public function recordPayment(float $amount): void
    {
        $this->increment('amount_paid', $amount);
        $this->refresh();

        $this->update([
            'status' => (float) $this->amount_paid >= (float) $this->total
                ? SupplierInvoiceStatus::Pagada
                : SupplierInvoiceStatus::PagoParcial,
        ]);
    }
}
