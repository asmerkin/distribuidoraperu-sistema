<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_receipt_items', function (Blueprint $table) {
            $table->integer('base_quantity_received')->default(0)->after('quantity_received');
        });

        // Backfill: existing receipts had no conversion, so base = raw
        DB::table('purchase_order_receipt_items')->update([
            'base_quantity_received' => DB::raw('quantity_received'),
        ]);
    }

    public function down(): void
    {
        Schema::table('purchase_order_receipt_items', function (Blueprint $table) {
            $table->dropColumn('base_quantity_received');
        });
    }
};
