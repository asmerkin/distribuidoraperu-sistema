<?php

namespace App\Services;

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderReceipt;
use App\Models\PurchaseOrderReceiptItem;
use App\Models\SupplierVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    /**
     * Confirm a PO with adjusted quantities, prices, and expected date.
     */
    public function confirm(PurchaseOrder $po, array $data): void
    {
        $po->load('items');

        DB::transaction(function () use ($po, $data) {
            if (! empty($data['expected_date'])) {
                $po->update(['expected_date' => $data['expected_date']]);
            }

            foreach ($po->items as $item) {
                $qty = (int) ($data["qty_{$item->id}"] ?? $item->quantity_ordered);
                $price = (float) ($data["price_{$item->id}"] ?? $item->unit_cost);

                if ($qty != $item->quantity_ordered || $price != (float) $item->unit_cost) {
                    $puQty = max($item->purchase_unit_qty, 1);
                    $item->update([
                        'quantity_ordered' => $qty,
                        'unit_cost' => $price,
                        'subtotal' => $qty * $price,
                        'base_quantity_ordered' => $qty * $puQty,
                    ]);
                }
            }

            $po->refresh()->load('items');
            $po->update([
                'status' => PurchaseOrderStatus::Confirmed,
                'confirmed_at' => now(),
                'total' => $po->items->sum('subtotal'),
            ]);
        });
    }

    /**
     * Receive items for a PO. Returns whether all items are now fully received.
     *
     * @param  array  $receiptItems  Array of ['purchase_order_item_id', 'variant_id', 'quantity_received', 'base_quantity_received', 'unit_cost']
     */
    public function receive(PurchaseOrder $po, array $receiptItems, ?int $userId = null): bool
    {
        $po->load('items.variant', 'location');

        // Pre-load supplier variants to avoid N+1
        $supplierVariantsByItemId = $this->loadSupplierVariants($po);

        return DB::transaction(function () use ($po, $receiptItems, $userId, $supplierVariantsByItemId) {
            foreach ($po->items as $item) {
                $receiptItem = collect($receiptItems)->firstWhere('purchase_order_item_id', $item->id);
                if (! $receiptItem) {
                    continue;
                }

                $qty = $receiptItem['quantity_received'];
                $baseQty = $receiptItem['base_quantity_received'];
                $confirmedPrice = $receiptItem['unit_cost'];

                $item->increment('quantity_received', $qty);
                $item->increment('base_quantity_received', $baseQty);

                if ($confirmedPrice != (float) $item->unit_cost) {
                    $item->update([
                        'unit_cost' => $confirmedPrice,
                        'subtotal' => $item->quantity_ordered * $confirmedPrice,
                    ]);
                }

                $supplierVariant = $supplierVariantsByItemId->get($item->id);
                if ($supplierVariant) {
                    $supplierVariant->update(['cost_price' => $confirmedPrice]);
                }

                $this->inventory->recordMovement(
                    variant: $item->variant,
                    location: $po->location,
                    type: StockMovementType::In,
                    reason: StockMovementReason::Purchase,
                    quantity: $baseQty,
                    reference: $po,
                    notes: "Recepción OC {$po->po_number}",
                    userId: $userId,
                );

                if (! $item->variant->product->is_active) {
                    $item->variant->product->update(['is_active' => true]);
                }
            }

            // Create receipt record
            $receipt = PurchaseOrderReceipt::create([
                'purchase_order_id' => $po->id,
                'received_at' => now(),
                'user_id' => $userId,
            ]);

            foreach ($receiptItems as $receiptItem) {
                PurchaseOrderReceiptItem::create([
                    'purchase_order_receipt_id' => $receipt->id,
                    ...$receiptItem,
                ]);
            }

            // Update PO status and total
            $po->refresh()->load('items');
            $allReceived = $po->items->every(
                fn ($item) => $item->quantity_received >= $item->quantity_ordered,
            );

            $po->update([
                'status' => $allReceived
                    ? PurchaseOrderStatus::Received
                    : PurchaseOrderStatus::PartiallyReceived,
                'total' => $po->items->sum('subtotal'),
            ]);

            return $allReceived;
        });
    }

    /**
     * Pre-load supplier variants for all PO items in a single query batch.
     */
    private function loadSupplierVariants(PurchaseOrder $po): Collection
    {
        $withId = $po->items->filter(fn ($item) => $item->supplier_variant_id);
        $withoutId = $po->items->filter(fn ($item) => ! $item->supplier_variant_id);

        $byId = $withId->isNotEmpty()
            ? SupplierVariant::whereIn('id', $withId->pluck('supplier_variant_id'))->get()->keyBy('id')
            : collect();

        $byLookup = collect();
        if ($withoutId->isNotEmpty()) {
            $byLookup = SupplierVariant::where('supplier_id', $po->supplier_id)
                ->whereIn('variant_id', $withoutId->pluck('variant_id'))
                ->get()
                ->keyBy('variant_id');
        }

        // Map to item_id => SupplierVariant
        return $po->items->mapWithKeys(function ($item) use ($byId, $byLookup) {
            if ($item->supplier_variant_id) {
                return [$item->id => $byId->get($item->supplier_variant_id)];
            }

            return [$item->id => $byLookup->get($item->variant_id)];
        });
    }
}
