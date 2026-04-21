<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_credit_notes', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('credit_note_number')->unique();
            $table->foreignUlid('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->date('date');
            $table->text('reason');
            $table->text('notes')->nullable();
            $table->decimal('total', 10, 2)->default(0);
            $table->string('attachment')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_credit_notes');
    }
};
