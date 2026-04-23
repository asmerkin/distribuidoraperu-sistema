<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ProductMergeService
{
    /**
     * Merge the source product into the target product.
     * All variants of $source are reassigned to $target, then $source is deleted.
     *
     * @return array{variants_moved: int, source_id: string, target_id: string, target_name: string}
     */
    public function merge(Product $source, Product $target): array
    {
        if ($source->id === $target->id) {
            throw new RuntimeException('No se puede fusionar un producto consigo mismo.');
        }

        return DB::transaction(function () use ($source, $target): array {
            $renamedSource = $source->variants()
                ->where('name', 'Default')
                ->update([
                    'product_id' => $target->id,
                    'name' => $source->name,
                ]);

            $kept = $source->variants()->update(['product_id' => $target->id]);

            $renamedTarget = $target->variants()
                ->where('name', 'Default')
                ->update(['name' => $target->name]);

            $source->refresh();

            $source->options()->delete();

            $source->delete();

            return [
                'variants_moved' => $renamedSource + $kept,
                'variants_renamed' => $renamedSource + $renamedTarget,
                'source_id' => $source->id,
                'target_id' => $target->id,
                'target_name' => $target->name,
            ];
        });
    }
}
