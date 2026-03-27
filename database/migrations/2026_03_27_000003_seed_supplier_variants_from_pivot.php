<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        $pivots = DB::table('product_supplier')->get();

        foreach ($pivots as $pivot) {
            $variants = DB::table('variants')
                ->where('product_id', $pivot->product_id)
                ->get();

            $isFirst = true;

            foreach ($variants as $variant) {
                $exists = DB::table('supplier_variants')
                    ->where('supplier_id', $pivot->supplier_id)
                    ->where('variant_id', $variant->id)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('supplier_variants')->insert([
                    'id' => Str::ulid()->toBase32(),
                    'supplier_id' => $pivot->supplier_id,
                    'variant_id' => $variant->id,
                    'supplier_code' => null,
                    'cost_price' => $variant->cost_price ?? 0,
                    'upc' => null,
                    'is_default' => $isFirst,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $isFirst = false;
            }
        }
    }

    public function down(): void
    {
        DB::table('supplier_variants')->truncate();
    }
};
