<?php

namespace App\Services;

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\SupplierCreditNote;
use App\Models\SupplierCreditNoteItem;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\Variant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierCreditNoteService
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    /**
     * Create a supplier credit note. Validates stock, persists the NC with items,
     * and generates Out stock movements for every item.
     *
     * @param  array  $data  keys: supplier_id, purchase_order_id (optional), date, reason, notes, attachment
     * @param  array  $items  each item: variant_id, location_id, quantity, unit_cost, purchase_unit, purchase_unit_qty, notes
     */
    public function create(array $data, array $items, ?int $userId = null): SupplierCreditNote
    {
        if (empty($items)) {
            throw ValidationException::withMessages([
                'items' => 'La nota de crédito debe tener al menos un ítem.',
            ]);
        }

        $this->validateStock($items);

        return DB::transaction(function () use ($data, $items, $userId) {
            $normalizedItems = collect($items)->map(function (array $item) {
                $puQty = max((int) ($item['purchase_unit_qty'] ?? 1), 1);
                $qty = (int) $item['quantity'];
                $unitCost = (float) $item['unit_cost'];

                return [
                    'variant_id' => $item['variant_id'],
                    'location_id' => $item['location_id'],
                    'purchase_unit' => $item['purchase_unit'] ?? null,
                    'purchase_unit_qty' => $puQty,
                    'quantity' => $qty,
                    'base_quantity' => $qty * $puQty,
                    'unit_cost' => $unitCost,
                    'subtotal' => round($qty * $unitCost, 2),
                    'notes' => $item['notes'] ?? null,
                ];
            });

            $total = round($normalizedItems->sum('subtotal'), 2);

            $creditNote = SupplierCreditNote::create([
                'supplier_id' => $data['supplier_id'],
                'supplier_document_number' => $data['supplier_document_number'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'date' => $data['date'],
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'attachment' => $data['attachment'] ?? null,
                'total' => $total,
                'user_id' => $userId ?? auth()->id(),
            ]);

            foreach ($normalizedItems as $itemData) {
                SupplierCreditNoteItem::create([
                    'supplier_credit_note_id' => $creditNote->id,
                    ...$itemData,
                ]);

                $variant = Variant::findOrFail($itemData['variant_id']);
                $location = Location::findOrFail($itemData['location_id']);

                $this->inventory->recordMovement(
                    variant: $variant,
                    location: $location,
                    type: StockMovementType::Out,
                    reason: StockMovementReason::Return,
                    quantity: $itemData['base_quantity'],
                    reference: $creditNote,
                    notes: "Devolución NC {$creditNote->credit_note_number}",
                    userId: $userId,
                );
            }

            return $creditNote->fresh(['items', 'supplier', 'purchaseOrder']);
        });
    }

    /**
     * Apply a credit note (or a portion of it) against a supplier invoice as a virtual payment.
     */
    public function applyToInvoice(
        SupplierCreditNote $creditNote,
        SupplierInvoice $invoice,
        float $amount,
        ?int $userId = null,
    ): SupplierPayment {
        if ($creditNote->supplier_id !== $invoice->supplier_id) {
            throw ValidationException::withMessages([
                'invoice' => 'La factura debe ser del mismo proveedor que la nota de crédito.',
            ]);
        }

        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'El monto a aplicar debe ser mayor a cero.',
            ]);
        }

        if ($amount > $creditNote->balance + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'El monto excede el saldo disponible de la nota de crédito.',
            ]);
        }

        if ($amount > $invoice->balance + 0.001) {
            throw ValidationException::withMessages([
                'amount' => 'El monto excede el saldo pendiente de la factura.',
            ]);
        }

        return DB::transaction(function () use ($creditNote, $invoice, $amount, $userId) {
            $payment = SupplierPayment::create([
                'supplier_invoice_id' => $invoice->id,
                'supplier_credit_note_id' => $creditNote->id,
                'amount' => $amount,
                'date' => today(),
                'method' => 'credit_note',
                'reference' => $creditNote->credit_note_number,
                'user_id' => $userId ?? auth()->id(),
            ]);

            $invoice->recalculateFromPayments();

            return $payment;
        });
    }

    /**
     * Remove a previously applied credit note payment and restore balances.
     */
    public function unapplyFromInvoice(SupplierPayment $payment): void
    {
        if (! $payment->supplier_credit_note_id) {
            throw ValidationException::withMessages([
                'payment' => 'Este pago no proviene de una nota de crédito.',
            ]);
        }

        DB::transaction(function () use ($payment) {
            $invoice = $payment->invoice;
            $payment->delete();
            $invoice?->recalculateFromPayments();
        });
    }

    /**
     * Check that every (variant, location) has enough on-hand stock before creating the NC.
     */
    private function validateStock(array $items): void
    {
        $needed = [];

        foreach ($items as $item) {
            $puQty = max((int) ($item['purchase_unit_qty'] ?? 1), 1);
            $baseQty = (int) $item['quantity'] * $puQty;
            $key = $item['variant_id'].':'.$item['location_id'];
            $needed[$key] = ($needed[$key] ?? 0) + $baseQty;
        }

        foreach ($needed as $key => $requiredBaseQty) {
            [$variantId, $locationId] = explode(':', $key);

            $onHand = (int) InventoryLevel::where('variant_id', $variantId)
                ->where('location_id', $locationId)
                ->value('quantity') ?? 0;

            if ($onHand < $requiredBaseQty) {
                $variant = Variant::with('product')->find($variantId);
                $location = Location::find($locationId);
                $label = $variant ? $variant->getLabel() : "variante {$variantId}";
                $locationName = $location?->name ?? "ubicación {$locationId}";

                throw ValidationException::withMessages([
                    'items' => "Stock insuficiente de {$label} en {$locationName}: hay {$onHand}, se intenta devolver {$requiredBaseQty}.",
                ]);
            }
        }
    }
}
