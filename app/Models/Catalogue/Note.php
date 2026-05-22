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

    public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(\App\Models\Catalogue\Product::class, 'product_note')
            ->withPivot(['level', 'sort_order'])
            ->withTimestamps();
    }
}
