<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use RuntimeException;

class Category extends Model
{
    use HasUlids;

    protected $fillable = ['name', 'parent_id'];

    protected static function booted(): void
    {
        static::saving(function (Category $category): void {
            $duplicate = static::where('name', $category->name)
                ->where('parent_id', $category->parent_id)
                ->when($category->exists, fn ($q) => $q->where('id', '!=', $category->id))
                ->exists();
            if ($duplicate) {
                throw new RuntimeException('Ya existe una categoría con ese nombre en el mismo nivel.');
            }

            if ($category->parent_id === null) {
                return;
            }

            if ($category->parent_id === $category->id) {
                throw new RuntimeException('Una categoría no puede ser su propia padre.');
            }

            $parent = static::find($category->parent_id);
            if ($parent && $parent->parent_id !== null) {
                throw new RuntimeException('Solo se permiten 2 niveles de categorías. El padre elegido ya es una subcategoría.');
            }

            if ($category->exists && $category->children()->exists()) {
                throw new RuntimeException('Esta categoría tiene subcategorías, por eso no puede tener un padre.');
            }
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
