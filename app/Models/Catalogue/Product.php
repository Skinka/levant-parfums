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
        'inspired_perfume_name', 'inspired_brand_id',
        'volume_ml', 'gender',
        'price_uah', 'price_eur',
        'in_stock', 'is_published', 'published_at',
        'seo_title', 'seo_description',
        'perfume_family_id', 'concentration_id', 'series_id',
    ];

    public array $translatable = ['name', 'tagline', 'description', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return [
            'in_stock' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'volume_ml' => 'integer',
            'price_uah' => 'decimal:2',
            'price_eur' => 'decimal:2',
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
        return $this->notes()->wherePivot('level', $level->value);
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
}
