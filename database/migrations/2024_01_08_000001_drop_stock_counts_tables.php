<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('stock_count_items');
        Schema::dropIfExists('stock_counts');
    }

    public function down(): void
    {
        Schema::create('stock_counts', function ($table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('location_id')->constrained()->restrictOnDelete();
            $table->string('status')->default('in_progress');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('stock_count_items', function ($table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('stock_count_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('variant_id')->constrained()->cascadeOnDelete();
            $table->integer('system_quantity');
            $table->integer('counted_quantity');
            $table->integer('difference');
            $table->timestamps();
        });
    }
};
