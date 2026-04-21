<?php

use App\Enums\StockMovementReason;
use App\Enums\StockMovementType;
use App\Enums\SupplierInvoiceStatus;
use App\Models\InventoryLevel;
use App\Models\Location;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\SupplierCreditNote;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;
use App\Models\Variant;
use App\Services\InventoryService;
use App\Services\SupplierCreditNoteService;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    $this->supplier = Supplier::create(['name' => 'Proveedor Test']);
    $this->otherSupplier = Supplier::create(['name' => 'Otro Proveedor']);
    $this->location = Location::create(['name' => 'Depósito Principal']);
    $this->product = Product::create(['name' => 'Resma A4', 'unit_of_measure' => 'unit']);
    $this->variant = Variant::create([
        'product_id' => $this->product->id,
        'sku' => 'RESMA-001',
        'name' => 'Default',
    ]);

    $this->inventory = new InventoryService;
    $this->service = new SupplierCreditNoteService($this->inventory);

    // Seed 50 units of stock for the variant at the location
    $this->inventory->recordMovement(
        $this->variant, $this->location,
        StockMovementType::In, StockMovementReason::Purchase, 50,
    );
});

function makeInvoiceFor(Supplier $supplier, float $total, string $number = 'FC-001'): SupplierInvoice
{
    return SupplierInvoice::create([
        'supplier_id' => $supplier->id,
        'invoice_number' => $number,
        'date' => today(),
        'total' => $total,
    ]);
}

it('creates a credit note, generates stock Out movements, and decrements inventory', function () {
    $cn = $this->service->create(
        data: [
            'supplier_id' => $this->supplier->id,
            'date' => today(),
            'reason' => 'Mercadería dañada',
        ],
        items: [
            [
                'variant_id' => $this->variant->id,
                'location_id' => $this->location->id,
                'quantity' => 5,
                'unit_cost' => 100,
                'purchase_unit_qty' => 1,
            ],
        ],
    );

    expect($cn)->toBeInstanceOf(SupplierCreditNote::class);
    expect($cn->credit_note_number)->toStartWith('NC-');
    expect((float) $cn->total)->toBe(500.0);
    expect($cn->items)->toHaveCount(1);

    $level = InventoryLevel::where('variant_id', $this->variant->id)
        ->where('location_id', $this->location->id)
        ->first();
    expect($level->quantity)->toBe(45); // 50 - 5

    $movement = StockMovement::where('reference_type', $cn->getMorphClass())
        ->where('reference_id', $cn->id)
        ->first();
    expect($movement)->not->toBeNull();
    expect($movement->type)->toBe(StockMovementType::Out);
    expect($movement->reason)->toBe(StockMovementReason::Return);
    expect($movement->quantity)->toBe(5);
});

it('refuses to create a credit note when inventory is insufficient', function () {
    $this->service->create(
        data: [
            'supplier_id' => $this->supplier->id,
            'date' => today(),
            'reason' => 'Test',
        ],
        items: [[
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity' => 999,
            'unit_cost' => 100,
            'purchase_unit_qty' => 1,
        ]],
    );
})->throws(ValidationException::class);

it('applies a credit note to an invoice as a virtual payment and recalculates status', function () {
    $invoice = makeInvoiceFor($this->supplier, 1000);
    $cn = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'Daño'],
        items: [[
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity' => 3, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    $payment = $this->service->applyToInvoice($cn, $invoice, 300);

    expect($payment)->toBeInstanceOf(SupplierPayment::class);
    expect($payment->method)->toBe('credit_note');
    expect($payment->supplier_credit_note_id)->toBe($cn->id);

    $invoice->refresh();
    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);
    expect((float) $invoice->amount_paid)->toBe(300.0);
    expect($cn->fresh()->balance)->toBe(0.0);
});

it('refuses to apply more than the credit note balance', function () {
    $invoice = makeInvoiceFor($this->supplier, 1000);
    $cn = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'x'],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 2, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    $this->service->applyToInvoice($cn, $invoice, 500); // NC total is 200
})->throws(ValidationException::class);

it('refuses to apply a credit note to an invoice from a different supplier', function () {
    $invoice = makeInvoiceFor($this->otherSupplier, 1000);
    $cn = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'x'],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 2, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    $this->service->applyToInvoice($cn, $invoice, 100);
})->throws(ValidationException::class);

it('unapplies a credit note payment and restores invoice balance', function () {
    $invoice = makeInvoiceFor($this->supplier, 1000);
    $cn = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'x'],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 5, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    $payment = $this->service->applyToInvoice($cn, $invoice, 500);
    $invoice->refresh();
    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);

    $this->service->unapplyFromInvoice($payment);

    $invoice->refresh();
    $cn->refresh();
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Unpaid);
    expect((float) $invoice->amount_paid)->toBe(0.0);
    expect($cn->balance)->toBe(500.0);
    expect(SupplierPayment::find($payment->id))->toBeNull();
});

it('stores the supplier document number when provided', function () {
    $cn = $this->service->create(
        data: [
            'supplier_id' => $this->supplier->id,
            'supplier_document_number' => 'NC-A-0001-00000123',
            'date' => today(),
            'reason' => 'x',
        ],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 1, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    expect($cn->supplier_document_number)->toBe('NC-A-0001-00000123');
    expect($cn->credit_note_number)->toStartWith('NC-');
});

it('auto-increments credit_note_number with NC- prefix', function () {
    $cn1 = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'x'],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 1, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );
    $cn2 = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'y'],
        items: [[
            'variant_id' => $this->variant->id, 'location_id' => $this->location->id,
            'quantity' => 1, 'unit_cost' => 100, 'purchase_unit_qty' => 1,
        ]],
    );

    expect($cn1->credit_note_number)->toBe('NC-00001');
    expect($cn2->credit_note_number)->toBe('NC-00002');
});

it('handles purchase_unit_qty correctly for base quantity', function () {
    $cn = $this->service->create(
        data: ['supplier_id' => $this->supplier->id, 'date' => today(), 'reason' => 'x'],
        items: [[
            'variant_id' => $this->variant->id,
            'location_id' => $this->location->id,
            'quantity' => 2,
            'unit_cost' => 1200, // price per caja
            'purchase_unit' => 'Caja x12',
            'purchase_unit_qty' => 12,
        ]],
    );

    $item = $cn->items->first();
    expect($item->base_quantity)->toBe(24); // 2 cajas * 12 = 24 base units
    expect((float) $item->subtotal)->toBe(2400.0);

    $level = InventoryLevel::where('variant_id', $this->variant->id)->first();
    expect($level->quantity)->toBe(26); // 50 - 24
});
