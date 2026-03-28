<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->foreignUlid('supplier_variant_id')->nullable()->after('variant_id')->constrained()->nullOnDelete();
            $table->string('purchase_unit')->nullable()->after('supplier_variant_id');
            $table->unsignedInteger('purchase_unit_qty')->default(1)->after('purchase_unit');
            $table->integer('base_quantity_ordered')->default(0)->after('quantity_received');
            $table->integer('base_quantity_received')->default(0)->after('base_quantity_ordered');
        });

        // Backfill existing items: no conversion (qty=1), base = raw quantities
        DB::table('purchase_order_items')->update([
            'base_quantity_ordered' => DB::raw('quantity_ordered'),
            'base_quantity_received' => DB::raw('quantity_received'),
        ]);

        // Backfill supplier_variant_id from the PO's supplier + item's variant
        DB::statement("
            UPDATE purchase_order_items
            SET supplier_variant_id = (
                SELECT sv.id FROM supplier_variants sv
                JOIN purchase_orders po ON po.id = purchase_order_items.purchase_order_id
                WHERE sv.supplier_id = po.supplier_id
                AND sv.variant_id = purchase_order_items.variant_id
                LIMIT 1
            )
        ");
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('supplier_variant_id');
            $table->dropColumn(['purchase_unit', 'purchase_unit_qty', 'base_quantity_ordered', 'base_quantity_received']);
        });
    }
};
