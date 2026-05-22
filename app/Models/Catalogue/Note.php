<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
