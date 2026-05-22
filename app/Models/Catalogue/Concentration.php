<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\ConcentrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Concentration extends Model
{
    /** @use HasFactory<ConcentrationFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'abbreviation', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
