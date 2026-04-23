<?php

namespace App\Models;

use App\Enums\UnitOfMeasure;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Facades\Storage;

class Product extends Model
{
    use HasUlids;

    protected $fillable = [
        'name',
        'description',
        'images',
        'category_id',
        'brand_id',
        'unit_of_measure',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'images' => 'array',
            'unit_of_measure' => UnitOfMeasure::class,
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (Product $product) {
            if ($product->images) {
                foreach ($product->images as $image) {
                    Storage::disk('public')->delete($image);
                }
            }
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOption::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class);
    }

    public function supplierVariants(): HasManyThrough
    {
        return $this->hasManyThrough(SupplierVariant::class, Variant::class);
    }
}
