<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('invoice_number');
            $table->date('date');
            $table->date('due_date')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->string('status')->default('impaga');
            $table->string('attachment')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('supplier_payments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_invoice_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->date('date');
            $table->string('method')->nullable();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_payments');
        Schema::dropIfExists('supplier_invoices');
    }
};
