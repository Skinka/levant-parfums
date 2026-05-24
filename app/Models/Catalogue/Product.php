<?php

namespace App\Models\Catalogue;

use App\Enums\Gender;
use App\Enums\NoteLevel;
use Database\Factories\Catalogue\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'sku', 'slug', 'name', 'tagline', 'description',
        'character', 'why',
        'inspired_perfume_name', 'inspired_brand_id',
        'volume_ml', 'gender',
        'price_uah', 'price_eur',
        'sillage_score', 'longevity_hours',
        'in_stock', 'is_published', 'published_at',
        'seo_title', 'seo_description',
        'perfume_family_id', 'concentration_id', 'series_id',
    ];

    public array $translatable = ['name', 'tagline', 'description', 'character', 'why', 'seo_title', 'seo_description'];

    public function setAttribute($key, $value)
    {
        if ($value === null && $this->isTranslatableAttribute($key)) {
            $this->attributes[$key] = null;

            return $this;
        }

        return parent::setAttribute($key, $value);
    }

    protected function casts(): array
    {
        return [
            'in_stock' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'volume_ml' => 'integer',
            'price_uah' => 'decimal:2',
            'price_eur' => 'decimal:2',
            'sillage_score' => 'integer',
            'longevity_hours' => 'integer',
            'gender' => Gender::class,
        ];
    }

    public function perfumeFamily(): BelongsTo
    {
        return $this->belongsTo(PerfumeFamily::class);
    }

    public function concentration(): BelongsTo
    {
        return $this->belongsTo(Concentration::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function inspiredBrand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'inspired_brand_id');
    }

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'product_note')
            ->withPivot(['level', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }

    public function notesByLevel(NoteLevel $level): BelongsToMany
    {
        return $this->notes()->wherePivot('level', $level->value)->select('notes.*');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function seasons(): BelongsToMany
    {
        return $this->belongsToMany(Season::class, 'product_season');
    }

    public function occasions(): BelongsToMany
    {
        return $this->belongsToMany(Occasion::class, 'product_occasion');
    }

    public function audiences(): BelongsToMany
    {
        return $this->belongsToMany(Audience::class, 'product_audience');
    }

    public function displayPrice(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $currency = config("catalogue.currency_by_locale.$locale", 'UAH');

        return match ($currency) {
            'EUR' => ['amount' => $this->price_eur, 'currency' => 'EUR'],
            default => ['amount' => $this->price_uah, 'currency' => 'UAH'],
        };
    }

    public function registerMediaCollections(): void
    {
        $this
            ->addMediaCollection('primary')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this
            ->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->fit(\Spatie\Image\Enums\Fit::Contain, 200, 200)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('card')
            ->fit(\Spatie\Image\Enums\Fit::Crop, 600, 800)
            ->format('webp')
            ->nonQueued();

        $this->addMediaConversion('detail')
            ->fit(\Spatie\Image\Enums\Fit::Contain, 1200, 1600)
            ->format('webp')
            ->nonQueued();
    }
}
