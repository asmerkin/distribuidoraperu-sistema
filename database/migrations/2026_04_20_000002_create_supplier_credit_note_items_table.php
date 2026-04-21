<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credit_note_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('variant_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('location_id')->constrained()->restrictOnDelete();
            $table->string('purchase_unit')->nullable();
            $table->unsignedInteger('purchase_unit_qty')->default(1);
            $table->unsignedInteger('quantity');
            $table->unsignedInteger('base_quantity');
            $table->decimal('unit_cost', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_note_items');
    }
};
