<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\OccasionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Occasion extends Model
{
    /** @use HasFactory<OccasionFactory> */
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
}
