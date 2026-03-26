<?php

use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\ScannerDevice;
use App\Models\StockMovement;
use App\Models\Variant;

beforeEach(function () {
    $this->location = Location::create(['name' => 'Deposito Principal']);
    $this->product = Product::create(['name' => 'Resma A4', 'unit_of_measure' => 'unit']);
    $this->variant = Variant::create([
        'product_id' => $this->product->id,
        'sku' => 'RESMA-001',
        'barcode' => '7790000000123',
        'name' => 'Default',
        'cost_price' => 150,
    ]);
});

function createDevice(array $overrides = []): ScannerDevice
{
    return ScannerDevice::create(array_merge([
        'name' => 'Tablet Test',
        'location_id' => test()->location->id,
        'is_active' => true,
    ], $overrides));
}

function createAuthenticatedDevice(): array
{
    $device = createDevice();
    $rawToken = 'test-token-' . str_repeat('x', 50);
    $device->update(['token' => hash('sha256', $rawToken)]);

    return [$device, $rawToken];
}

it('exchanges valid OTP for a permanent token', function () {
    $rawOtp = 'test-otp-123456';
    $device = createDevice([
        'otp' => hash('sha256', $rawOtp),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/scanner/auth', ['otp' => $rawOtp]);

    $response->assertOk()
        ->assertJsonStructure(['token', 'device' => ['id', 'name', 'location' => ['id', 'name']]]);

    $device->refresh();
    expect($device->otp)->toBeNull();
    expect($device->otp_expires_at)->toBeNull();
    expect($device->token)->not->toBeNull();
});

it('rejects expired OTP', function () {
    $rawOtp = 'expired-otp';
    createDevice([
        'otp' => hash('sha256', $rawOtp),
        'otp_expires_at' => now()->subMinutes(1),
    ]);

    $response = $this->postJson('/api/scanner/auth', ['otp' => $rawOtp]);

    $response->assertUnauthorized();
});

it('rejects invalid OTP', function () {
    createDevice([
        'otp' => hash('sha256', 'real-otp'),
        'otp_expires_at' => now()->addMinutes(15),
    ]);

    $response = $this->postJson('/api/scanner/auth', ['otp' => 'wrong-otp']);

    $response->assertUnauthorized();
});

it('rejects unauthenticated requests', function () {
    $this->getJson('/api/scanner/device')
        ->assertUnauthorized();
});

it('rejects inactive device', function () {
    $device = createDevice(['is_active' => false]);
    $rawToken = 'inactive-token-' . str_repeat('x', 50);
    $device->update(['token' => hash('sha256', $rawToken)]);

    $this->getJson('/api/scanner/device', [
        'Authorization' => "Bearer {$rawToken}",
    ])->assertUnauthorized();
});

it('returns device info', function () {
    [$device, $token] = createAuthenticatedDevice();

    $response = $this->getJson('/api/scanner/device', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJson([
            'id' => $device->id,
            'name' => 'Tablet Test',
            'location' => [
                'id' => $this->location->id,
                'name' => 'Deposito Principal',
            ],
        ]);
});

it('looks up variant by barcode', function () {
    [$device, $token] = createAuthenticatedDevice();

    $response = $this->getJson('/api/scanner/lookup?code=7790000000123', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJsonPath('variant.sku', 'RESMA-001')
        ->assertJsonPath('variant.barcode', '7790000000123')
        ->assertJsonPath('variant.product_name', 'Resma A4');
});

it('looks up variant by SKU', function () {
    [$device, $token] = createAuthenticatedDevice();

    $response = $this->getJson('/api/scanner/lookup?code=RESMA-001', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJsonPath('variant.sku', 'RESMA-001');
});

it('returns 404 for unknown barcode', function () {
    [$device, $token] = createAuthenticatedDevice();

    $response = $this->getJson('/api/scanner/lookup?code=NOTEXIST', [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertNotFound();
});

it('submits stock adjustment', function () {
    [$device, $token] = createAuthenticatedDevice();

    // Seed initial stock
    InventoryLevel::create([
        'variant_id' => $this->variant->id,
        'location_id' => $this->location->id,
        'quantity' => 10,
    ]);

    $response = $this->postJson('/api/scanner/adjust', [
        'variant_id' => $this->variant->id,
        'counted_quantity' => 7,
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Stock ajustado',
            'previous_stock' => 10,
            'counted' => 7,
            'diff' => -3,
            'new_stock' => 7,
        ]);

    // Verify movement was created
    expect(StockMovement::count())->toBe(1);
    $movement = StockMovement::first();
    expect($movement->quantity)->toBe(-3);
    expect($movement->type->value)->toBe('adjustment');
    expect($movement->reason->value)->toBe('stock_count');

    // Verify inventory level updated
    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();
    expect($level->quantity)->toBe(7);
});

it('handles zero difference adjustment', function () {
    [$device, $token] = createAuthenticatedDevice();

    InventoryLevel::create([
        'variant_id' => $this->variant->id,
        'location_id' => $this->location->id,
        'quantity' => 10,
    ]);

    $response = $this->postJson('/api/scanner/adjust', [
        'variant_id' => $this->variant->id,
        'counted_quantity' => 10,
    ], [
        'Authorization' => "Bearer {$token}",
    ]);

    $response->assertOk()
        ->assertJson([
            'message' => 'Sin diferencia',
            'diff' => 0,
        ]);

    expect(StockMovement::count())->toBe(0);
});
