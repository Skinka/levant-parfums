<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\SeasonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Season extends Model
{
    /** @use HasFactory<SeasonFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Catalogue\Product::class, 'product_season');
    }
}
