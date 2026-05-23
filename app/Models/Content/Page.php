<?php

namespace App\Models\Content;

use Database\Factories\Content\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'slug', 'title', 'intro', 'content',
        'seo_title', 'seo_description', 'is_published',
    ];

    public array $translatable = [
        'slug', 'title', 'intro', 'content', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $reserved = config('content.reserved_slugs', []);
            $slugs = $page->getTranslations('slug');
            foreach ($slugs as $locale => $slug) {
                if (in_array($slug, $reserved, true)) {
                    throw new \DomainException("Slug '{$slug}' is reserved (locale: {$locale}).");
                }
            }
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
