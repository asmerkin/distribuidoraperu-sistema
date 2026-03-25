<?php

use App\Enums\SupplierInvoiceStatus;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\SupplierPayment;

beforeEach(function () {
    $this->supplier = Supplier::create(['name' => 'Proveedor Test']);
});

it('creates an invoice with impaga status', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-001',
        'date' => today(),
        'due_date' => today()->addDays(30),
        'total' => 1000,
    ]);

    $invoice->refresh();
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Unpaid);
    expect($invoice->amount_paid)->toBe('0.00');
    expect($invoice->balance)->toBe(1000.0);
});

it('changes to pago_parcial after partial payment', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-002',
        'date' => today(),
        'total' => 1000,
    ]);

    $invoice->recordPayment(400);

    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);
    expect($invoice->amount_paid)->toBe('400.00');
    expect($invoice->balance)->toBe(600.0);
});

it('changes to pagada after full payment', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-003',
        'date' => today(),
        'total' => 500,
    ]);

    $invoice->recordPayment(500);

    expect($invoice->status)->toBe(SupplierInvoiceStatus::Paid);
    expect($invoice->balance)->toBe(0.0);
});

it('changes to pagada after multiple partial payments', function () {
    $invoice = SupplierInvoice::create([
        'supplier_id' => $this->supplier->id,
        'invoice_number' => 'FC-004',
        'date' => today(),
        'total' => 1000,
    ]);

    $invoice->recordPayment(300);
    expect($invoice->status)->toBe(SupplierInvoiceStatus::PartiallyPaid);

    $invoice->recordPayment(700);
    expect($invoice->status)->toBe(SupplierInvoiceStatus::Paid);
    expect($invoice->balance)->toBe(0.0);
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

    $invoice->recordPayment(500);

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
