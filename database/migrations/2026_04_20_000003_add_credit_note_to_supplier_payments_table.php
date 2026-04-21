<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->foreignUlid('supplier_credit_note_id')
                ->nullable()
                ->after('supplier_invoice_id')
                ->constrained()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_credit_note_id');
        });
    }
};
