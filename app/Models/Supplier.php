<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'tax_id',
        'contact_name',
        'email',
        'phone',
        'address',
        'payment_terms',
        'notes',
    ];

    public function supplierVariants(): HasMany
    {
        return $this->hasMany(SupplierVariant::class);
    }

    public function purchaseOrders(): HasMany
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(SupplierInvoice::class);
    }

    public function creditNotes(): HasMany
    {
        return $this->hasMany(SupplierCreditNote::class);
    }

    public function payments(): \Illuminate\Database\Eloquent\Relations\HasManyThrough
    {
        return $this->hasManyThrough(SupplierPayment::class, SupplierInvoice::class);
    }

    public function getTotalOwedAttribute(): float
    {
        return round(
            (float) $this->invoices()
                ->where('status', '!=', \App\Enums\SupplierInvoiceStatus::Paid->value)
                ->selectRaw('COALESCE(SUM(total - amount_paid), 0) as owed')
                ->value('owed'),
            2,
        );
    }
}
