<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_order_receipts', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_id')->constrained()->cascadeOnDelete();
            $table->datetime('received_at');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('purchase_order_receipt_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('purchase_order_receipt_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('purchase_order_item_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('variant_id')->constrained()->restrictOnDelete();
            $table->integer('quantity_received');
            $table->decimal('unit_cost', 10, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_order_receipt_items');
        Schema::dropIfExists('purchase_order_receipts');
    }
};
