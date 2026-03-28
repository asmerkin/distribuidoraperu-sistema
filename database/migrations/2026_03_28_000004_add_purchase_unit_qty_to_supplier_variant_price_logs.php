<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('supplier_variant_price_logs', function (Blueprint $table) {
            $table->unsignedInteger('purchase_unit_qty')->default(1)->after('new_price');
        });
    }

    public function down(): void
    {
        Schema::table('supplier_variant_price_logs', function (Blueprint $table) {
            $table->dropColumn('purchase_unit_qty');
        });
    }
};
