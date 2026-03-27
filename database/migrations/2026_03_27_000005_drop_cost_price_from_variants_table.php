<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Sync cost_price from variants to their default supplier_variants
        $variants = DB::table('variants')
            ->whereNotNull('cost_price')
            ->where('cost_price', '>', 0)
            ->get(['id', 'cost_price']);

        foreach ($variants as $variant) {
            DB::table('supplier_variants')
                ->where('variant_id', $variant->id)
                ->where('is_default', true)
                ->where('cost_price', 0)
                ->update(['cost_price' => $variant->cost_price]);
        }

        Schema::table('variants', function (Blueprint $table) {
            $table->dropColumn('cost_price');
        });
    }

    public function down(): void
    {
        Schema::table('variants', function (Blueprint $table) {
            $table->decimal('cost_price', 10, 2)->default(0)->after('images');
        });
    }
};
