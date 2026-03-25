<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierPayment extends Model
{
    use HasUlids;

    protected $fillable = [
        'supplier_invoice_id',
        'amount',
        'date',
        'method',
        'reference',
        'notes',
        'attachment',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'date' => 'date',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(SupplierInvoice::class, 'supplier_invoice_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
