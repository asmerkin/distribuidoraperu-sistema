<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_variants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('variant_id')->constrained()->cascadeOnDelete();
            $table->string('supplier_code')->nullable();
            $table->decimal('cost_price', 10, 2);
            $table->string('upc')->nullable();
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            $table->unique(['supplier_id', 'variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_variants');
    }
};
