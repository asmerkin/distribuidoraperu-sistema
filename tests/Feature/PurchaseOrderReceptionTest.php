<?php

use App\Enums\PurchaseOrderStatus;
use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Variant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->supplier = Supplier::create(['name' => 'Proveedor Test']);
    $this->location = Location::create(['name' => 'Depósito']);
    $this->product = Product::create(['name' => 'Resma A4', 'unit_of_measure' => 'unidad']);
    $this->variant = Variant::create([
        'product_id' => $this->product->id,
        'sku' => 'RESMA-001',
        'name' => 'Default',
        'cost_price' => 100,
    ]);
});

it('receives full PO and creates stock movements', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Enviada,
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
        type: StockMovementType::Entrada,
        reason: StockMovementReason::Compra,
        quantity: 10,
        reference: $po,
    );

    $item->increment('quantity_received', 10);
    $this->variant->update(['cost_price' => $item->unit_cost]);

    $item->refresh();
    expect($item->quantity_received)->toBe(10);

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();
    expect($level->quantity)->toBe(10);

    $movement = StockMovement::first();
    expect($movement->type)->toBe(StockMovementType::Entrada);
    expect($movement->reason)->toBe(StockMovementReason::Compra);
    expect($movement->reference_type)->toBe(PurchaseOrder::class);
    expect($movement->reference_id)->toBe($po->id);
});

it('handles partial reception', function () {
    $po = PurchaseOrder::create([
        'supplier_id' => $this->supplier->id,
        'location_id' => $this->location->id,
        'status' => PurchaseOrderStatus::Enviada,
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
        type: StockMovementType::Entrada,
        reason: StockMovementReason::Compra,
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
        type: StockMovementType::Entrada,
        reason: StockMovementReason::Compra,
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
