<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\PerfumeFamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PerfumeFamily extends Model
{
    /** @use HasFactory<PerfumeFamilyFactory> */
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
