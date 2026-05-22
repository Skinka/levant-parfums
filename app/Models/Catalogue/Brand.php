<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'country', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\App\Models\Catalogue\Product::class, 'inspired_brand_id');
    }
}
