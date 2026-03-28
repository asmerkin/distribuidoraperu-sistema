<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Backfill null supplier_codes with the variant SKU before changing constraints
        DB::statement("UPDATE supplier_variants SET supplier_code = (SELECT sku FROM variants WHERE variants.id = supplier_variants.variant_id) WHERE supplier_code IS NULL OR supplier_code = ''");

        // Add new columns (if not already added from partial run)
        if (! Schema::hasColumn('supplier_variants', 'purchase_unit')) {
            Schema::table('supplier_variants', function (Blueprint $table) {
                $table->string('purchase_unit')->nullable()->after('upc');
                $table->unsignedInteger('purchase_unit_qty')->default(1)->after('purchase_unit');
            });
        }

        // Create new unique index FIRST (MySQL needs it to cover the FK before we drop the old one)
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->unique(['supplier_id', 'variant_id', 'supplier_code']);
        });

        // Now safe to drop old unique index
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->dropUnique(['supplier_id', 'variant_id']);
        });

        // Make supplier_code not nullable
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->string('supplier_code')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->string('supplier_code')->nullable()->change();
        });

        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->unique(['supplier_id', 'variant_id']);
        });

        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->dropUnique(['supplier_id', 'variant_id', 'supplier_code']);
        });

        Schema::table('supplier_variants', function (Blueprint $table) {
            $table->dropColumn(['purchase_unit', 'purchase_unit_qty']);
        });
    }
};
