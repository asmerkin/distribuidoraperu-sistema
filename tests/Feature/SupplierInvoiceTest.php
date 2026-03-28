<?php

use App\Enums\SupplierInvoiceStatus;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;

beforeEach(function () {
    $this->supplier = Supplier::create(['name' => 'Proveedor Test']);
});

function makeInvoice(string $number, float $total, ?Supplier $supplier = null): SupplierInvoice
{
    return SupplierInvoice::create([
        'supplier_id' => $supplier?->id ?? Supplier::first()->id,
        'invoice_number' => $number,
        'date' => today(),
        'total' => $total,
    ]);
}

function addPayment(SupplierInvoice $invoice, float $amount): void
{
    SupplierPayment::create([
        'supplier_invoice_id' => $invoice->id,
        'amount' => $amount,
        'date' => today(),
    ]);
    $invoice->recalculateFromPayments();
    $invoice->refresh();
}

it('creates an invoice with impaga status', function () {
    $invoice = makeInvoice('FC-001', 1000, $this->supplier);

    $invoice->refresh();
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Unpaid);
    expect($invoice->amount_paid)->toBe('0.00');
    expect($invoice->balance)->toBe(1000.0);
});

it('changes to pago_parcial after partial payment', function () {
    $invoice = makeInvoice('FC-002', 1000, $this->supplier);

    addPayment($invoice, 400);

    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);
    expect($invoice->amount_paid)->toBe('400.00');
    expect($invoice->balance)->toBe(600.0);
});

it('changes to pagada after full payment', function () {
    $invoice = makeInvoice('FC-003', 500, $this->supplier);

    addPayment($invoice, 500);

    expect($invoice->status)->toBe(SupplierInvoiceStatus::Paid);
    expect($invoice->balance)->toBe(0.0);
});

it('changes to pagada after multiple partial payments', function () {
    $invoice = makeInvoice('FC-004', 1000, $this->supplier);

    addPayment($invoice, 300);
    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);

    addPayment($invoice, 700);
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Paid);
    expect($invoice->balance)->toBe(0.0);
});

it('reverts to impaga when a payment is deleted', function () {
    $invoice = makeInvoice('FC-010', 1000, $this->supplier);

    addPayment($invoice, 1000);
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Paid);

    $invoice->payments()->first()->delete();
    $invoice->recalculateFromPayments();
    $invoice->refresh();

    expect($invoice->status)->toBe(SupplierInvoiceStatus::Unpaid);
    expect($invoice->amount_paid)->toBe('0.00');
});

it('detects overdue invoices', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-005',
        'date' => today()->subDays(40),
        'due_date' => today()->subDays(10),
        'total' => 1000,
    ]);

    expect($invoice->is_overdue)->toBeTrue();
    expect($invoice->display_status)->toBe('Vencida');
    expect($invoice->display_status_color)->toBe('danger');
});

it('does not mark paid invoices as overdue', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-006',
        'date' => today()->subDays(40),
        'due_date' => today()->subDays(10),
        'total' => 500,
    ]);

    addPayment($invoice, 500);

    expect($invoice->is_overdue)->toBeFalse();
    expect($invoice->display_status)->toBe('Pagada');
});

it('calculates supplier total owed', function () {
    SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-007',
        'date' => today(),
        'total' => 1000,
        'amount_paid' => 300,
        'status' => SupplierInvoiceStatus::PartiallyPaid,
    ]);

    SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-008',
        'date' => today(),
        'total' => 500,
        'status' => SupplierInvoiceStatus::Unpaid,
    ]);

    SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-009',
        'date' => today(),
        'total' => 2000,
        'amount_paid' => 2000,
        'status' => SupplierInvoiceStatus::Paid,
    ]);

    expect($this->supplier->total_owed)->toBe(1200.0);
});
