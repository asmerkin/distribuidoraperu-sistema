<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CategoryMergeService
{
    /**
     * Merge the source category into the target. Both must be at the same level
     * (both roots or both subcategories).
     *
     * - Subcategory → Subcategory: reassigns products, deletes source.
     * - Root → Root: for each child of source, if target has a child with the same
     *   name it gets recursively merged (subcat merge); otherwise the child is
     *   reparented under target. Any products directly on source are moved too.
     *
     * @return array{products_moved: int, children_merged: int, children_moved: int, source_id: string, target_id: string, target_name: string}
     */
    public function merge(Category $source, Category $target): array
    {
        if ($source->id === $target->id) {
            throw new RuntimeException('No se puede fusionar una categoría consigo misma.');
        }

        $sourceIsRoot = $source->parent_id === null;
        $targetIsRoot = $target->parent_id === null;

        if ($sourceIsRoot !== $targetIsRoot) {
            throw new RuntimeException('Solo se pueden fusionar categorías del mismo nivel (raíz con raíz, subcategoría con subcategoría).');
        }

        return $sourceIsRoot
            ? $this->mergeRoots($source, $target)
            : $this->mergeSubcategories($source, $target);
    }

    /**
     * @return array{products_moved: int, children_merged: int, children_moved: int, source_id: string, target_id: string, target_name: string}
     */
    private function mergeSubcategories(Category $source, Category $target): array
    {
        return DB::transaction(function () use ($source, $target): array {
            $moved = Product::where('category_id', $source->id)
                ->update(['category_id' => $target->id]);

            $source->delete();

            return [
                'products_moved' => $moved,
                'children_merged' => 0,
                'children_moved' => 0,
                'source_id' => $source->id,
                'target_id' => $target->id,
                'target_name' => $target->name,
            ];
        });
    }

    /**
     * @return array{products_moved: int, children_merged: int, children_moved: int, source_id: string, target_id: string, target_name: string}
     */
    private function mergeRoots(Category $source, Category $target): array
    {
        return DB::transaction(function () use ($source, $target): array {
            $productsMoved = Product::where('category_id', $source->id)
                ->update(['category_id' => $target->id]);

            $targetChildrenByName = $target->children()->pluck('id', 'name');

            $childrenMerged = 0;
            $childrenMoved = 0;

            foreach ($source->children()->get() as $sourceChild) {
                if ($targetChildrenByName->has($sourceChild->name)) {
                    $matching = Category::findOrFail($targetChildrenByName->get($sourceChild->name));
                    $nested = $this->mergeSubcategories($sourceChild, $matching);
                    $productsMoved += $nested['products_moved'];
                    $childrenMerged++;
                } else {
                    $sourceChild->update(['parent_id' => $target->id]);
                    $targetChildrenByName->put($sourceChild->name, $sourceChild->id);
                    $childrenMoved++;
                }
            }

            $source->delete();

            return [
                'products_moved' => $productsMoved,
                'children_merged' => $childrenMerged,
                'children_moved' => $childrenMoved,
                'source_id' => $source->id,
                'target_id' => $target->id,
                'target_name' => $target->name,
            ];
        });
    }
}
