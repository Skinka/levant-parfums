<?php

namespace App\Models\Content;

use App\Models\Catalogue\Product;
use Database\Factories\Content\ArticleFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Article extends Model implements HasMedia
{
    /** @use HasFactory<ArticleFactory> */
    use HasFactory;

    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'slug', 'title', 'intro', 'content',
        'seo_title', 'seo_description',
        'is_published', 'published_at',
    ];

    public array $translatable = [
        'slug', 'title', 'intro', 'content', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'published_at' => 'datetime',
        ];
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'article_product')
            ->withPivot('sort_order')
            ->orderByPivot('sort_order');
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true)
            ->where(function (Builder $inner) {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('primary')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 400, 400)->format('webp')->nonQueued();
        $this->addMediaConversion('card')->fit(Fit::Crop, 1200, 630)->format('webp')->nonQueued();
        $this->addMediaConversion('detail')->fit(Fit::Crop, 1920, 1080)->format('webp')->nonQueued();
    }
}
