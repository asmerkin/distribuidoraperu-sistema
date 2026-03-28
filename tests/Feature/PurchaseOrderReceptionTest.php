<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseOrderReceipt;
use App\Models\PurchaseOrderReceiptItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierVariant;
use App\Models\Variant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->supplier = Supplier::create(['name' => 'Proveedor Test']);
    $this->location = Location::create(['name' => 'Depósito']);
    $this->product = Product::create(['name' => 'Resma A4', 'unit_of_measure' => 'unit']);
    $this->variant = Variant::create([
        'product_id' => $this->product->id,
        'sku' => 'RESMA-001',
        'name' => 'Default',
    ]);
    $this->supplierVariant = SupplierVariant::create([
        'supplier_id' => $this->supplier->id,
        'variant_id' => $this->variant->id,
        'supplier_code' => 'RESMA-001',
        'cost_price' => 100,
        'is_default' => true,
    ]);
});

it('receives full PO and creates stock movements', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Sent,
        'order_date' => today(),
        'total' => 1000,
    ]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'variant_id' => $this->variant->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'unit_cost' => 100,
        'subtotal' => 1000,
    ]);

    $inventory = app(InventoryService::class);

    // Simulate reception
    $inventory->recordMovement(
        variant: $this->variant,
        location: $this->location,
        type: StockMovementType::In,
        reason: StockMovementReason::Purchase,
        quantity: 10,
        reference: $po,
    );

    $item->increment('quantity_received', 10);
    $this->supplierVariant->update(['cost_price' => $item->unit_cost]);

    $item->refresh();
    expect($item->quantity_received)->toBe(10);

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();
    expect($level->quantity)->toBe(10);

    $movement = StockMovement::first();
    expect($movement->type)->toBe(StockMovementType::In);
    expect($movement->reason)->toBe(StockMovementReason::Purchase);
    expect($movement->reference_type)->toBe(PurchaseOrder::class);
    expect($movement->reference_id)->toBe($po->id);
});

it('handles partial reception', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Sent,
        'order_date' => today(),
        'total' => 1000,
    ]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'variant_id' => $this->variant->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'unit_cost' => 100,
        'subtotal' => 1000,
    ]);

    $inventory = app(InventoryService::class);

    // First partial reception: 6 units
    $inventory->recordMovement(
        variant: $this->variant,
        location: $this->location,
        type: StockMovementType::In,
        reason: StockMovementReason::Purchase,
        quantity: 6,
        reference: $po,
    );
    $item->increment('quantity_received', 6);

    $item->refresh();
    expect($item->quantity_received)->toBe(6);
    expect($item->quantity_ordered - $item->quantity_received)->toBe(4);

    // Second reception: remaining 4 units
    $inventory->recordMovement(
        variant: $this->variant,
        location: $this->location,
        type: StockMovementType::In,
        reason: StockMovementReason::Purchase,
        quantity: 4,
        reference: $po,
    );
    $item->increment('quantity_received', 4);

    $item->refresh();
    expect($item->quantity_received)->toBe(10);

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();
    expect($level->quantity)->toBe(10);

    expect(StockMovement::count())->toBe(2);
});

it('updates PO item and variant cost when price differs at reception', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Sent,
        'order_date' => today(),
        'total' => 1000,
    ]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'variant_id' => $this->variant->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'unit_cost' => 100,
        'subtotal' => 1000,
    ]);

    $inventory = app(InventoryService::class);
    $confirmedPrice = 120;

    // Simulate reception with different price
    $item->increment('quantity_received', 10);
    $item->update([
        'unit_cost' => $confirmedPrice,
        'subtotal' => $item->quantity_ordered * $confirmedPrice,
    ]);
    $this->supplierVariant->update(['cost_price' => $confirmedPrice]);

    $inventory->recordMovement(
        variant: $this->variant,
        location: $this->location,
        type: StockMovementType::In,
        reason: StockMovementReason::Purchase,
        quantity: 10,
        reference: $po,
    );

    $item->refresh();
    $this->supplierVariant->refresh();

    expect($item->unit_cost)->toBe('120.00');
    expect($item->subtotal)->toBe('1200.00');
    expect($this->supplierVariant->cost_price)->toBe('120.00');
});

it('creates receipt records with items', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Sent,
        'order_date' => today(),
        'total' => 1000,
    ]);

    $item = PurchaseOrderItem::create([
        'purchase_order_id' => $po->id,
        'variant_id' => $this->variant->id,
        'quantity_ordered' => 10,
        'quantity_received' => 0,
        'unit_cost' => 100,
        'subtotal' => 1000,
    ]);

    // Create receipt record
    $receipt = PurchaseOrderReceipt::create([
        'purchase_order_id' => $po->id,
        'received_at' => now(),
        'user_id' => null,
    ]);

    PurchaseOrderReceiptItem::create([
        'purchase_order_receipt_id' => $receipt->id,
        'purchase_order_item_id' => $item->id,
        'variant_id' => $this->variant->id,
        'quantity_received' => 6,
        'unit_cost' => 100,
    ]);

    expect($po->receipts()->count())->toBe(1);
    expect($receipt->items()->count())->toBe(1);

    $receiptItem = $receipt->items()->first();
    expect($receiptItem->quantity_received)->toBe(6);
    expect($receiptItem->unit_cost)->toBe('100.00');
    expect($receiptItem->variant_id)->toBe($this->variant->id);
});
