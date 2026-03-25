<?php

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Variant;
use App\Services\InventoryService;

beforeEach(function () {
    $this->service = new InventoryService;
    $this->product = Product::create([
        'name' => 'Resma A4',
        'unit_of_measure' => 'unit',
    ]);
    $this->variant = Variant::create([
        'product_id' => $this->product->id,
        'sku' => 'RESMA-001',
        'name' => 'Default',
        'cost_price' => 0,
    ]);
    $this->location = Location::create(['name' => 'Depósito Principal']);
});

it('creates a stock movement and inventory level on entrada', function () {
    $movement = $this->service->recordMovement(
        $this->variant,
        $this->location,
        StockMovementType::In,
        StockMovementReason::Purchase,
        10,
    );

    expect($movement)->toBeInstanceOf(StockMovement::class);
    expect($movement->quantity)->toBe(10);
    expect($movement->type)->toBe(StockMovementType::In);

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();

    expect($level)->not->toBeNull();
    expect($level->quantity)->toBe(10);
});

it('subtracts stock on salida', function () {
    // First add stock
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::In, StockMovementReason::Purchase, 20,
    );

    // Then remove some
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::Out, StockMovementReason::Shrinkage, 5,
    );

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();

    expect($level->quantity)->toBe(15);
});

it('handles positive and negative adjustments', function () {
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::In, StockMovementReason::Purchase, 10,
    );

    // Positive adjustment (counted more than system)
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::Adjustment, StockMovementReason::StockCount, 3,
    );

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();

    expect($level->quantity)->toBe(13);

    // Negative adjustment (counted less than system)
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::Adjustment, StockMovementReason::StockCount, -2,
    );

    $level->refresh();
    expect($level->quantity)->toBe(11);
});

it('creates inventory level automatically if it does not exist', function () {
    $newLocation = Location::create(['name' => 'Local Mostrador']);

    expect(InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $newLocation->id)
        ->exists())->toBeFalse();

    $this->service->recordMovement(
        $this->variant, $newLocation,
        StockMovementType::In, StockMovementReason::Purchase, 5,
    );

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $newLocation->id)
        ->first();

    expect($level)->not->toBeNull();
    expect($level->quantity)->toBe(5);
});

it('transfers stock between locations', function () {
    $fromLocation = $this->location;
    $toLocation = Location::create(['name' => 'Local Mostrador']);

    // Add initial stock
    $this->service->recordMovement(
        $this->variant, $fromLocation,
        StockMovementType::In, StockMovementReason::Purchase, 20,
    );

    // Transfer
    [$outMovement, $inMovement] = $this->service->transfer(
        $this->variant, $fromLocation, $toLocation, 8,
    );

    expect($outMovement->type)->toBe(StockMovementType::Out);
    expect($outMovement->reason)->toBe(StockMovementReason::TransferOut);
    expect($inMovement->type)->toBe(StockMovementType::In);
    expect($inMovement->reason)->toBe(StockMovementReason::TransferIn);

    $fromLevel = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $fromLocation->id)->first();
    $toLevel = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $toLocation->id)->first();

    expect($fromLevel->quantity)->toBe(12);
    expect($toLevel->quantity)->toBe(8);
});

it('records all movements immutably', function () {
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::In, StockMovementReason::Purchase, 10,
    );
    $this->service->recordMovement(
        $this->variant, $this->location,
        StockMovementType::Out, StockMovementReason::Shrinkage, 3,
    );

    expect(StockMovement::count())->toBe(2);
    expect(StockMovement::where('type', 'in')->first()->quantity)->toBe(10);
    expect(StockMovement::where('type', 'out')->first()->quantity)->toBe(3);
});
