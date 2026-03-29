<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('price_list_imports', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('supplier_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('file_type');
            $table->unsignedInteger('items_extracted')->default(0);
            $table->unsignedInteger('items_matched')->default(0);
            $table->unsignedInteger('items_updated')->default(0);
            $table->unsignedInteger('items_unchanged')->default(0);
            $table->unsignedInteger('items_unmatched')->default(0);
            $table->unsignedInteger('items_linked')->default(0);
            $table->string('status')->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_list_imports');
    }
};
