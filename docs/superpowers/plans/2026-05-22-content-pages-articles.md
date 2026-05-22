# Content: Pages & Articles — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add two independent content types (`Page` for static pages, `Article` for blog) with bilingual translatable fields, media, scheduled publishing on Article, m2m link to products with sort order, plus a full Filament admin and tests for the risky paths.

**Architecture:** Two separate Eloquent models under `App\Models\Content` namespace, each with `HasFactory` + `HasTranslations` + `InteractsWithMedia`. MySQL functional unique indexes per locale enforce slug uniqueness in the DB; reserved slugs (e.g. `blog`, `admin`) are blocked on `Page` only. Filament resources mirror the existing `ProductResource` shape (Resource thin, `Schemas/`+`Tables/`+`Pages/` split). The Article↔Product pivot uses the `notesRepeater` pattern from `ProductForm` — Repeater UI plus manual `afterCreate`/`afterSave` attach in the Page classes, indexed by Repeater position.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL 8.0+, Filament 5, Spatie MediaLibrary 11, Spatie Translatable 6 (+ lara-zeus/spatie-translatable Filament glue), Pest 4 + `livewire/livewire` tests.

**Spec:** `docs/superpowers/specs/2026-05-22-content-pages-articles-design.md`

**Conventions used throughout:**
- All Bash commands assume `cwd = /Users/romanroman/Projects/LevantParfums`.
- All `php artisan` commands run against the project's MySQL DB (see `.env`).
- Migration timestamps in this plan are placeholders (`2026_05_22_HHMMSS_*`); use `php artisan make:migration` to get the actual timestamp.
- Commits use the same prefix style as recent history (`content: …`).

---

## File Structure

Files this plan will create or modify, grouped by task:

```
config/content.php                                                   [Task 1, create]

database/migrations/YYYY_MM_DD_HHMMSS_create_pages_table.php          [Task 2, create]
database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php      [Task 3, create]
database/migrations/YYYY_MM_DD_HHMMSS_create_article_product_table.php [Task 3, create]

app/Models/Content/Page.php                                          [Task 4, create]
database/factories/Content/PageFactory.php                           [Task 4, create]
tests/Feature/Content/PageTest.php                                   [Task 4, create]

app/Models/Content/Article.php                                       [Task 5, create]
database/factories/Content/ArticleFactory.php                        [Task 5, create]
tests/Feature/Content/ArticleTest.php                                [Task 5, create]

lang/uk/content.php                                                  [Task 6, create]
lang/en/content.php                                                  [Task 6, create]

app/Filament/Resources/Pages/PageResource.php                        [Task 7, create]
app/Filament/Resources/Pages/Schemas/PageForm.php                    [Task 7, create]
app/Filament/Resources/Pages/Tables/PagesTable.php                   [Task 7, create]
app/Filament/Resources/Pages/Pages/CreatePage.php                    [Task 7, create]
app/Filament/Resources/Pages/Pages/EditPage.php                      [Task 7, create]
app/Filament/Resources/Pages/Pages/ListPages.php                     [Task 7, create]
tests/Feature/Content/Filament/PageResourceTest.php                  [Task 7, create]

app/Filament/Resources/Articles/ArticleResource.php                  [Task 8, create]
app/Filament/Resources/Articles/Schemas/ArticleForm.php              [Task 8, create]
app/Filament/Resources/Articles/Tables/ArticlesTable.php             [Task 8, create]
app/Filament/Resources/Articles/Pages/CreateArticle.php              [Task 8, create]
app/Filament/Resources/Articles/Pages/EditArticle.php                [Task 8, create]
app/Filament/Resources/Articles/Pages/ListArticles.php               [Task 8, create]
tests/Feature/Content/Filament/ArticleResourceTest.php               [Task 8, create]

database/seeders/Content/PageSeeder.php                              [Task 9, create]
database/seeders/Content/ArticleSeeder.php                           [Task 9, create]
database/seeders/DatabaseSeeder.php                                  [Task 9, modify]
```

---

## Task 1: Config — reserved slugs

**Files:**
- Create: `config/content.php`

- [ ] **Step 1: Create `config/content.php`**

```php
<?php

return [
    'reserved_slugs' => [
        'admin', 'api', 'assets', 'storage', 'login', 'register', 'logout',
        'blog', 'articles', 'pages', 'sitemap', 'feed',
        'uk', 'en',
    ],
];
```

- [ ] **Step 2: Sanity check**

Run: `php artisan tinker --execute='echo count(config("content.reserved_slugs"));'`
Expected: `13`

- [ ] **Step 3: Commit**

```bash
git add config/content.php
git commit -m "content: add reserved_slugs config"
```

---

## Task 2: Migration — `pages` table

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_pages_table.php`

- [ ] **Step 1: Generate migration file**

Run: `php artisan make:migration create_pages_table`

This produces a file like `database/migrations/2026_05_22_HHMMSS_create_pages_table.php`.

- [ ] **Step 2: Replace contents with the schema**

Replace the entire migration body with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->json('slug');
            $table->json('title');
            $table->json('intro')->nullable();
            $table->json('content');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->index('is_published');
        });

        DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_uk_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.uk')) AS CHAR(191))))");
        DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_en_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')) AS CHAR(191))))");
    }

    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
```

- [ ] **Step 3: Run migration and verify**

Run: `php artisan migrate`
Expected: includes `INFO  Migrating: ..._create_pages_table` followed by `DONE`. No errors.

- [ ] **Step 4: Verify functional indexes exist**

Run: `php artisan db:show --counts | grep -i pages` followed by `php artisan tinker --execute='print_r(\DB::select("SHOW INDEX FROM pages"));'`
Expected: rows including `pages_slug_uk_uniq` and `pages_slug_en_uniq`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/*_create_pages_table.php
git commit -m "content: create pages table with per-locale slug unique indexes"
```

---

## Task 3: Migrations — `articles` and `article_product`

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_articles_table.php`
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_create_article_product_table.php`

- [ ] **Step 1: Generate the `articles` migration**

Run: `php artisan make:migration create_articles_table`

- [ ] **Step 2: Write its schema**

Replace the body with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->json('slug');
            $table->json('title');
            $table->json('intro')->nullable();
            $table->json('content');
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->index('is_published');
            $table->index('published_at');
        });

        DB::statement("ALTER TABLE articles ADD UNIQUE articles_slug_uk_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.uk')) AS CHAR(191))))");
        DB::statement("ALTER TABLE articles ADD UNIQUE articles_slug_en_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')) AS CHAR(191))))");
    }

    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
```

- [ ] **Step 3: Generate the `article_product` migration**

Run: `php artisan make:migration create_article_product_table`

Note: this migration MUST be created (and ordered) AFTER `create_articles_table` so the `article_id` FK resolves.

- [ ] **Step 4: Write the pivot schema**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('article_product', function (Blueprint $table) {
            $table->foreignId('article_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->primary(['article_id', 'product_id']);
            $table->index(['article_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('article_product');
    }
};
```

- [ ] **Step 5: Run migrations**

Run: `php artisan migrate`
Expected: both migrations applied, no errors.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/*_create_articles_table.php database/migrations/*_create_article_product_table.php
git commit -m "content: create articles + article_product tables"
```

---

## Task 4: `Page` model + factory + model tests

**Files:**
- Create: `app/Models/Content/Page.php`
- Create: `database/factories/Content/PageFactory.php`
- Create: `tests/Feature/Content/PageTest.php`

- [ ] **Step 1: Write failing test file**

Create `tests/Feature/Content/PageTest.php`:

```php
<?php

use App\Models\Content\Page;
use Illuminate\Database\QueryException;

it('stores translatable fields per locale', function () {
    $page = Page::factory()->create([
        'title' => ['uk' => 'Доставка', 'en' => 'Delivery'],
        'slug' => ['uk' => 'dostavka', 'en' => 'delivery'],
        'content' => ['uk' => 'Текст', 'en' => 'Text'],
    ]);

    expect($page->getTranslation('title', 'uk'))->toBe('Доставка');
    expect($page->getTranslation('title', 'en'))->toBe('Delivery');
});

it('published scope returns only is_published=true', function () {
    Page::factory()->create(['is_published' => true]);
    Page::factory()->create(['is_published' => false]);

    expect(Page::published()->count())->toBe(1);
});

it('DB rejects duplicate uk slug for two pages', function () {
    Page::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-1']]);

    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-2']]))
        ->toThrow(QueryException::class);
});

it('saving throws DomainException when uk slug is reserved', function () {
    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'blog', 'en' => 'blog-en']]))
        ->toThrow(DomainException::class);
});

it('saving throws DomainException when en slug is reserved', function () {
    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'ok-uk', 'en' => 'admin']]))
        ->toThrow(DomainException::class);
});
```

- [ ] **Step 2: Run test — expect FAIL**

Run: `php artisan test --filter=PageTest`
Expected: all five tests fail with "Class App\Models\Content\Page not found" (or similar).

- [ ] **Step 3: Create `app/Models/Content/Page.php`**

```php
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
```

- [ ] **Step 4: Create `database/factories/Content/PageFactory.php`**

```php
<?php

namespace Database\Factories\Content;

use App\Models\Content\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $titleUk = 'Сторінка '.fake()->unique()->numberBetween(1, 99999);
        $titleEn = 'Page '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => [
                'uk' => Str::slug($titleUk).'-'.Str::random(4),
                'en' => Str::slug($titleEn).'-'.Str::random(4),
            ],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'intro' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'content' => ['uk' => fake('uk_UA')->paragraphs(2, true), 'en' => fake()->paragraphs(2, true)],
            'seo_title' => ['uk' => $titleUk, 'en' => $titleEn],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'is_published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }
}
```

- [ ] **Step 5: Run tests — expect PASS**

Run: `php artisan test --filter=PageTest`
Expected: all 5 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Content/Page.php database/factories/Content/PageFactory.php tests/Feature/Content/PageTest.php
git commit -m "content: Page model, factory and tests"
```

---

## Task 5: `Article` model + factory + model tests

**Files:**
- Create: `app/Models/Content/Article.php`
- Create: `database/factories/Content/ArticleFactory.php`
- Create: `tests/Feature/Content/ArticleTest.php`

- [ ] **Step 1: Write failing test file**

Create `tests/Feature/Content/ArticleTest.php`:

```php
<?php

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Database\QueryException;

it('casts published_at to datetime', function () {
    $a = Article::factory()->create(['published_at' => '2026-06-01 12:00:00']);
    expect($a->published_at)->toBeInstanceOf(\Illuminate\Support\Carbon::class);
});

it('stores translatable fields per locale', function () {
    $a = Article::factory()->create([
        'title' => ['uk' => 'Огляд', 'en' => 'Overview'],
    ]);
    expect($a->getTranslation('title', 'uk'))->toBe('Огляд');
    expect($a->getTranslation('title', 'en'))->toBe('Overview');
});

it('published scope hides is_published=false', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => null]);
    Article::factory()->create(['is_published' => false, 'published_at' => null]);

    expect(Article::published()->count())->toBe(1);
});

it('published scope hides future published_at', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
    Article::factory()->create(['is_published' => true, 'published_at' => now()->addDay()]);

    expect(Article::published()->count())->toBe(1);
});

it('products relation orders by pivot sort_order', function () {
    $article = Article::factory()->create();
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $p3 = Product::factory()->create();

    $article->products()->attach([
        $p3->id => ['sort_order' => 0],
        $p1->id => ['sort_order' => 1],
        $p2->id => ['sort_order' => 2],
    ]);

    expect($article->products->pluck('id')->all())->toBe([$p3->id, $p1->id, $p2->id]);
});

it('DB rejects duplicate uk slug for two articles', function () {
    Article::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-1']]);

    expect(fn () => Article::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-2']]))
        ->toThrow(QueryException::class);
});

it('DB rejects duplicate en slug for two articles', function () {
    Article::factory()->create(['slug' => ['uk' => 'foo-uk-1', 'en' => 'bar']]);

    expect(fn () => Article::factory()->create(['slug' => ['uk' => 'foo-uk-2', 'en' => 'bar']]))
        ->toThrow(QueryException::class);
});
```

- [ ] **Step 2: Run tests — expect FAIL**

Run: `php artisan test --filter=ArticleTest`
Expected: all 7 tests fail with class-not-found.

- [ ] **Step 3: Create `app/Models/Content/Article.php`**

```php
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
            ->withTimestamps()
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
```

- [ ] **Step 4: Create `database/factories/Content/ArticleFactory.php`**

```php
<?php

namespace Database\Factories\Content;

use App\Models\Content\Article;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        $titleUk = 'Стаття '.fake()->unique()->numberBetween(1, 99999);
        $titleEn = 'Article '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => [
                'uk' => Str::slug($titleUk).'-'.Str::random(4),
                'en' => Str::slug($titleEn).'-'.Str::random(4),
            ],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'intro' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'content' => ['uk' => fake('uk_UA')->paragraphs(2, true), 'en' => fake()->paragraphs(2, true)],
            'seo_title' => ['uk' => $titleUk, 'en' => $titleEn],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'is_published' => true,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }

    public function scheduled(\DateTimeInterface $at): static
    {
        return $this->state(['is_published' => true, 'published_at' => $at]);
    }
}
```

- [ ] **Step 5: Run tests — expect PASS**

Run: `php artisan test --filter=ArticleTest`
Expected: all 7 tests pass.

- [ ] **Step 6: Commit**

```bash
git add app/Models/Content/Article.php database/factories/Content/ArticleFactory.php tests/Feature/Content/ArticleTest.php
git commit -m "content: Article model, factory and tests"
```

---

## Task 6: Translations (uk + en)

**Files:**
- Create: `lang/uk/content.php`
- Create: `lang/en/content.php`

- [ ] **Step 1: Create `lang/uk/content.php`**

```php
<?php

return [
    'navigation' => [
        'group' => 'Контент',
        'pages' => 'Сторінки',
        'articles' => 'Статті',
    ],
    'page' => [
        'singular' => 'Сторінка',
        'plural' => 'Сторінки',
    ],
    'article' => [
        'singular' => 'Стаття',
        'plural' => 'Статті',
    ],
    'tabs' => [
        'main' => 'Основне',
        'seo' => 'SEO',
        'images' => 'Зображення',
    ],
    'fields' => [
        'title' => 'Заголовок',
        'slug' => 'URL',
        'intro' => 'Короткий вступ',
        'content' => 'Контент',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Опубліковано',
        'published_at' => 'Дата публікації',
        'primary' => 'Основне зображення',
        'products' => 'Прив’язані товари',
        'add_product' => 'Додати товар',
        'product_id' => 'Товар',
    ],
    'hints' => [
        'published_at' => "Стаття з'явиться на сайті в цей час. Лишіть порожнім — публікація одразу.",
    ],
    'actions' => [
        'publish' => 'Опублікувати',
        'unpublish' => 'Зняти з публікації',
    ],
    'filters' => [
        'scheduled' => 'Заплановані',
    ],
];
```

- [ ] **Step 2: Create `lang/en/content.php`**

```php
<?php

return [
    'navigation' => [
        'group' => 'Content',
        'pages' => 'Pages',
        'articles' => 'Articles',
    ],
    'page' => [
        'singular' => 'Page',
        'plural' => 'Pages',
    ],
    'article' => [
        'singular' => 'Article',
        'plural' => 'Articles',
    ],
    'tabs' => [
        'main' => 'Main',
        'seo' => 'SEO',
        'images' => 'Images',
    ],
    'fields' => [
        'title' => 'Title',
        'slug' => 'URL',
        'intro' => 'Intro',
        'content' => 'Content',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Published',
        'published_at' => 'Publish at',
        'primary' => 'Primary image',
        'products' => 'Related products',
        'add_product' => 'Add product',
        'product_id' => 'Product',
    ],
    'hints' => [
        'published_at' => 'The article will appear on the site at this time. Leave empty to publish immediately.',
    ],
    'actions' => [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
    ],
    'filters' => [
        'scheduled' => 'Scheduled',
    ],
];
```

- [ ] **Step 3: Verify translations resolve**

Run: `php artisan tinker --execute='echo trans("content.navigation.group");'`
Expected: `Контент` (default locale is `uk`).

- [ ] **Step 4: Commit**

```bash
git add lang/uk/content.php lang/en/content.php
git commit -m "content: uk/en lang strings"
```

---

## Task 7: Filament `PageResource` + test

**Files:**
- Create: `app/Filament/Resources/Pages/PageResource.php`
- Create: `app/Filament/Resources/Pages/Schemas/PageForm.php`
- Create: `app/Filament/Resources/Pages/Tables/PagesTable.php`
- Create: `app/Filament/Resources/Pages/Pages/CreatePage.php`
- Create: `app/Filament/Resources/Pages/Pages/EditPage.php`
- Create: `app/Filament/Resources/Pages/Pages/ListPages.php`
- Create: `tests/Feature/Content/Filament/PageResourceTest.php`

> Naming note: the inner directory is `Pages/Pages/` (the Filament-page subdirectory inside the `Pages` resource). Confusing but it matches the existing `Products/Pages/` layout.

- [ ] **Step 1: Create `PageResource.php`**

```php
<?php

namespace App\Filament\Resources\Pages;

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Filament\Resources\Pages\Schemas\PageForm;
use App\Filament\Resources\Pages\Tables\PagesTable;
use App\Models\Content\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PageResource extends Resource
{
    protected static ?string $model = Page::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';

    public static function getNavigationGroup(): ?string
    {
        return trans('content.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return trans('content.navigation.pages');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('content.page.plural');
    }

    public static function getModelLabel(): string
    {
        return trans('content.page.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return PageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPages::route('/'),
            'create' => CreatePage::route('/create'),
            'edit' => EditPage::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2: Create `Schemas/PageForm.php`**

```php
<?php

namespace App\Filament\Resources\Pages\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('page')
                    ->tabs([
                        Tab::make(trans('content.tabs.main'))
                            ->schema(self::mainTab()),
                        Tab::make(trans('content.tabs.seo'))
                            ->schema(self::seoTab()),
                        Tab::make(trans('content.tabs.images'))
                            ->schema(self::imagesTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function mainTab(): array
    {
        $reserved = config('content.reserved_slugs', []);

        return [
            TextInput::make('title')
                ->label(fn () => trans('content.fields.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('content.fields.slug'))
                ->required()
                ->rule('not_in:'.implode(',', $reserved))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->uk')->ignore($record?->id))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->en')->ignore($record?->id)),
            Textarea::make('intro')
                ->label(fn () => trans('content.fields.intro'))
                ->rows(3)
                ->maxLength(300),
            MarkdownEditor::make('content')
                ->label(fn () => trans('content.fields.content'))
                ->required()
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'heading',
                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                ]),
            Toggle::make('is_published')
                ->label(fn () => trans('content.fields.is_published')),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label(fn () => trans('content.fields.seo_title'))
                ->maxLength(70),
            Textarea::make('seo_description')
                ->label(fn () => trans('content.fields.seo_description'))
                ->rows(3)
                ->maxLength(160),
        ];
    }

    protected static function imagesTab(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('primary')
                ->label(fn () => trans('content.fields.primary'))
                ->collection('primary')
                ->image()
                ->imageEditor()
                ->maxSize(4096),
        ];
    }
}
```

- [ ] **Step 3: Create `Tables/PagesTable.php`**

```php
<?php

namespace App\Filament\Resources\Pages\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class PagesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('primary')
                    ->collection('primary')
                    ->conversion('thumb'),
                TextColumn::make('title')
                    ->label(fn () => trans('content.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label(fn () => trans('content.fields.slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(fn () => trans('content.fields.is_published'))
                    ->boolean(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published'),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(trans('content.actions.publish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => true])),
                    BulkAction::make('unpublish')
                        ->label(trans('content.actions.unpublish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

- [ ] **Step 4: Create the three Filament page classes**

`app/Filament/Resources/Pages/Pages/CreatePage.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreatePage extends CreateRecord
{
    use Translatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }
}
```

`app/Filament/Resources/Pages/Pages/EditPage.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditPage extends EditRecord
{
    use Translatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make(), DeleteAction::make()];
    }
}
```

`app/Filament/Resources/Pages/Pages/ListPages.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Pages;

use App\Filament\Resources\Pages\PageResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListPages extends ListRecords
{
    use Translatable;

    protected static string $resource = PageResource::class;

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make(), CreateAction::make()];
    }
}
```

- [ ] **Step 5: Create `tests/Feature/Content/Filament/PageResourceTest.php`**

```php
<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Content\Page;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the page list', function () {
    Page::factory()->count(2)->create();
    Livewire::test(ListPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Page::all());
});

it('creates a page with translatable title and slug', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка',
            'slug' => 'dostavka',
            'content' => 'Текст про доставку.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('pages', []);
    $page = Page::firstWhere('id', 1);
    expect($page->getTranslation('slug', 'uk'))->toBe('dostavka');
});

it('rejects a duplicate uk slug on create', function () {
    Page::factory()->create(['slug' => ['uk' => 'dostavka', 'en' => 'delivery']]);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка 2',
            'slug' => 'dostavka',
            'content' => 'Текст.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('rejects a reserved slug on create', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Blog page',
            'slug' => 'blog',
            'content' => 'Body.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});
```

- [ ] **Step 6: Run tests — expect PASS**

Run: `php artisan test --filter=PageResourceTest`
Expected: 4 passing tests.

- [ ] **Step 7: Manual smoke**

Run: `php artisan serve` in one terminal, then in browser open `http://127.0.0.1:8000/admin/pages`. Confirm:
- New "Контент → Сторінки" entry appears in left nav.
- Create form has Main / SEO / Images tabs.
- Locale switcher (top-right header action) is present.

- [ ] **Step 8: Commit**

```bash
git add app/Filament/Resources/Pages tests/Feature/Content/Filament/PageResourceTest.php
git commit -m "content: PageResource (Filament) with form, table, locale switcher and tests"
```

---

## Task 8: Filament `ArticleResource` + test

**Files:**
- Create: `app/Filament/Resources/Articles/ArticleResource.php`
- Create: `app/Filament/Resources/Articles/Schemas/ArticleForm.php`
- Create: `app/Filament/Resources/Articles/Tables/ArticlesTable.php`
- Create: `app/Filament/Resources/Articles/Pages/CreateArticle.php`
- Create: `app/Filament/Resources/Articles/Pages/EditArticle.php`
- Create: `app/Filament/Resources/Articles/Pages/ListArticles.php`
- Create: `tests/Feature/Content/Filament/ArticleResourceTest.php`

> The Article ↔ Product Repeater uses the `notesRepeater` pattern from `ProductForm` — Repeater field with `Select::make('product_id')` inside, no `->relationship()` call, and the persist/load logic lives in `CreateArticle`/`EditArticle` via `mutateFormDataBefore{Create,Save}` + `afterCreate` / `afterSave`. This is the existing codebase convention (`ProductForm::notesRepeater`, `CreateProduct`, `EditProduct`).

- [ ] **Step 1: Create `ArticleResource.php`**

```php
<?php

namespace App\Filament\Resources\Articles;

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Filament\Resources\Articles\Schemas\ArticleForm;
use App\Filament\Resources\Articles\Tables\ArticlesTable;
use App\Models\Content\Article;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ArticleResource extends Resource
{
    protected static ?string $model = Article::class;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-newspaper';

    public static function getNavigationGroup(): ?string
    {
        return trans('content.navigation.group');
    }

    public static function getNavigationLabel(): string
    {
        return trans('content.navigation.articles');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('content.article.plural');
    }

    public static function getModelLabel(): string
    {
        return trans('content.article.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return ArticleForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ArticlesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListArticles::route('/'),
            'create' => CreateArticle::route('/create'),
            'edit' => EditArticle::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 2: Create `Schemas/ArticleForm.php`**

```php
<?php

namespace App\Filament\Resources\Articles\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('article')
                    ->tabs([
                        Tab::make(trans('content.tabs.main'))
                            ->schema(self::mainTab()),
                        Tab::make(trans('content.tabs.seo'))
                            ->schema(self::seoTab()),
                        Tab::make(trans('content.tabs.images'))
                            ->schema(self::imagesTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function mainTab(): array
    {
        return [
            TextInput::make('title')
                ->label(fn () => trans('content.fields.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('content.fields.slug'))
                ->required()
                ->rule(fn ($record) => Rule::unique('articles', 'slug->uk')->ignore($record?->id))
                ->rule(fn ($record) => Rule::unique('articles', 'slug->en')->ignore($record?->id)),
            Textarea::make('intro')
                ->label(fn () => trans('content.fields.intro'))
                ->rows(3)
                ->maxLength(300),
            MarkdownEditor::make('content')
                ->label(fn () => trans('content.fields.content'))
                ->required()
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'heading',
                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                ]),
            Toggle::make('is_published')
                ->label(fn () => trans('content.fields.is_published'))
                ->live(),
            DateTimePicker::make('published_at')
                ->label(fn () => trans('content.fields.published_at'))
                ->seconds(false)
                ->helperText(trans('content.hints.published_at'))
                ->visible(fn (callable $get) => $get('is_published')),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label(fn () => trans('content.fields.seo_title'))
                ->maxLength(70),
            Textarea::make('seo_description')
                ->label(fn () => trans('content.fields.seo_description'))
                ->rows(3)
                ->maxLength(160),
            self::productsRepeater(),
        ];
    }

    protected static function productsRepeater(): Repeater
    {
        return Repeater::make('products')
            ->label(fn () => trans('content.fields.products'))
            ->schema([
                Select::make('product_id')
                    ->label(fn () => trans('content.fields.product_id'))
                    ->relationship('product', 'slug')
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
                    ->searchable()
                    ->preload()
                    ->required(),
            ])
            ->orderColumn('sort_order')
            ->reorderable()
            ->defaultItems(0)
            ->addActionLabel(fn () => trans('content.fields.add_product'));
    }

    protected static function imagesTab(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('primary')
                ->label(fn () => trans('content.fields.primary'))
                ->collection('primary')
                ->image()
                ->imageEditor()
                ->maxSize(4096),
        ];
    }
}
```

- [ ] **Step 3: Create `Tables/ArticlesTable.php`**

```php
<?php

namespace App\Filament\Resources\Articles\Tables;

use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ArticlesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('primary')
                    ->collection('primary')
                    ->conversion('thumb'),
                TextColumn::make('title')
                    ->label(fn () => trans('content.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                TextColumn::make('slug')
                    ->label(fn () => trans('content.fields.slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                IconColumn::make('is_published')
                    ->label(fn () => trans('content.fields.is_published'))
                    ->boolean(),
                TextColumn::make('published_at')
                    ->label(fn () => trans('content.fields.published_at'))
                    ->dateTime()
                    ->sortable()
                    ->badge()
                    ->color(fn ($record) => $record?->published_at?->isFuture() ? 'warning' : null),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                TernaryFilter::make('is_published'),
                Filter::make('scheduled')
                    ->label(fn () => trans('content.filters.scheduled'))
                    ->query(fn ($query) => $query->where('published_at', '>', now())),
            ])
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label(trans('content.actions.publish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => true, 'published_at' => now()])),
                    BulkAction::make('unpublish')
                        ->label(trans('content.actions.unpublish'))
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

- [ ] **Step 4: Create `Pages/CreateArticle.php`**

This caches the `products` array from the form, removes it before saving the model (so Eloquent doesn't try to mass-assign it), and re-attaches in `afterCreate` with `sort_order` = repeater index.

```php
<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Resources\Pages\CreateRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\CreateRecord\Concerns\Translatable;

class CreateArticle extends CreateRecord
{
    use Translatable;

    protected static string $resource = ArticleResource::class;

    protected array $cachedProducts = [];

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make()];
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->cachedProducts = $data['products'] ?? [];
        unset($data['products']);

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach ($this->cachedProducts as $i => $row) {
            if (! empty($row['product_id'])) {
                $this->record->products()->attach($row['product_id'], ['sort_order' => $i]);
            }
        }
    }
}
```

- [ ] **Step 5: Create `Pages/EditArticle.php`**

```php
<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\EditRecord\Concerns\Translatable;

class EditArticle extends EditRecord
{
    use Translatable;

    protected static string $resource = ArticleResource::class;

    protected array $cachedProducts = [];

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make(), DeleteAction::make()];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['products'] = $this->record->products()
            ->orderBy('article_product.sort_order')
            ->get()
            ->map(fn ($p) => ['product_id' => $p->id])
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->cachedProducts = $data['products'] ?? [];
        unset($data['products']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->products()->detach();
        foreach ($this->cachedProducts as $i => $row) {
            if (! empty($row['product_id'])) {
                $this->record->products()->attach($row['product_id'], ['sort_order' => $i]);
            }
        }
    }
}
```

- [ ] **Step 6: Create `Pages/ListArticles.php`**

```php
<?php

namespace App\Filament\Resources\Articles\Pages;

use App\Filament\Resources\Articles\ArticleResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use LaraZeus\SpatieTranslatable\Actions\LocaleSwitcher;
use LaraZeus\SpatieTranslatable\Resources\Pages\ListRecords\Concerns\Translatable;

class ListArticles extends ListRecords
{
    use Translatable;

    protected static string $resource = ArticleResource::class;

    protected function getHeaderActions(): array
    {
        return [LocaleSwitcher::make(), CreateAction::make()];
    }
}
```

- [ ] **Step 7: Create `tests/Feature/Content/Filament/ArticleResourceTest.php`**

```php
<?php

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the article list', function () {
    Article::factory()->count(2)->create();
    Livewire::test(ListArticles::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Article::all());
});

it('creates an article with title and slug', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Огляд ароматів',
            'slug' => 'oglyad-aromaniv',
            'content' => 'Текст огляду.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::firstWhere('id', 1);
    expect($article->getTranslation('slug', 'uk'))->toBe('oglyad-aromaniv');
});

it('rejects a duplicate uk slug on create', function () {
    Article::factory()->create(['slug' => ['uk' => 'oglyad', 'en' => 'overview-en-1']]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Інша назва',
            'slug' => 'oglyad',
            'content' => 'Body.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('attaches products with sort_order matching repeater order', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $p3 = Product::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Topic A',
            'slug' => 'topic-a',
            'content' => 'Body.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
            'products' => [
                ['product_id' => $p2->id],
                ['product_id' => $p3->id],
                ['product_id' => $p1->id],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::firstWhere('id', 1);
    expect($article->products->pluck('id')->all())->toBe([$p2->id, $p3->id, $p1->id]);
});

it('persists reorder of products on edit', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $article = Article::factory()->create();
    $article->products()->attach([
        $p1->id => ['sort_order' => 0],
        $p2->id => ['sort_order' => 1],
    ]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm([
            'products' => [
                ['product_id' => $p2->id],
                ['product_id' => $p1->id],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->products->pluck('id')->all())->toBe([$p2->id, $p1->id]);
});
```

- [ ] **Step 8: Run tests — expect PASS**

Run: `php artisan test --filter=ArticleResourceTest`
Expected: 5 passing tests.

- [ ] **Step 9: Manual smoke**

With `php artisan serve` running, visit `http://127.0.0.1:8000/admin/articles`. Confirm:
- "Контент → Статті" nav entry.
- Create form has Main/SEO/Images tabs.
- In SEO tab, the "Прив'язані товари" repeater renders a row with a searchable product select.
- `published_at` field is only visible when `is_published` is toggled on.

- [ ] **Step 10: Commit**

```bash
git add app/Filament/Resources/Articles tests/Feature/Content/Filament/ArticleResourceTest.php
git commit -m "content: ArticleResource (Filament) with products repeater and tests"
```

---

## Task 9: Seeders + DatabaseSeeder integration

**Files:**
- Create: `database/seeders/Content/PageSeeder.php`
- Create: `database/seeders/Content/ArticleSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Create `PageSeeder.php` (placeholder)**

```php
<?php

namespace Database\Seeders\Content;

use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Static pages (About, Delivery, Payment, ...) are added manually
        // in the admin UI — no fixtures here.
    }
}
```

- [ ] **Step 2: Create `ArticleSeeder.php`**

```php
<?php

namespace Database\Seeders\Content;

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Database\Seeder;

class ArticleSeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::query()->inRandomOrder()->limit(9)->get();
        if ($products->count() < 3) {
            return;
        }

        $chunks = $products->chunk(3)->values();

        Article::factory()->count(3)->create()->each(function (Article $article, int $i) use ($chunks) {
            $set = $chunks[$i] ?? collect();
            foreach ($set->values() as $idx => $product) {
                $article->products()->attach($product->id, ['sort_order' => $idx]);
            }
        });
    }
}
```

- [ ] **Step 3: Wire seeders into `DatabaseSeeder.php`**

Modify `database/seeders/DatabaseSeeder.php` — add the two new seeders at the end of the `$this->call([...])` list:

```php
<?php

namespace Database\Seeders;

use App\Models\User;
use Database\Seeders\Catalogue\AudienceSeeder;
use Database\Seeders\Catalogue\BrandSeeder;
use Database\Seeders\Catalogue\ConcentrationSeeder;
use Database\Seeders\Catalogue\NoteSeeder;
use Database\Seeders\Catalogue\OccasionSeeder;
use Database\Seeders\Catalogue\PerfumeFamilySeeder;
use Database\Seeders\Catalogue\ProductSeeder;
use Database\Seeders\Catalogue\SeasonSeeder;
use Database\Seeders\Catalogue\SeriesSeeder;
use Database\Seeders\Catalogue\TagSeeder;
use Database\Seeders\Content\ArticleSeeder;
use Database\Seeders\Content\PageSeeder;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@levantparfums.test'],
            [
                'name' => 'Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ],
        );

        $this->call([
            PerfumeFamilySeeder::class,
            ConcentrationSeeder::class,
            SeasonSeeder::class,
            TagSeeder::class,
            SeriesSeeder::class,
            OccasionSeeder::class,
            AudienceSeeder::class,
            BrandSeeder::class,
            NoteSeeder::class,
            ProductSeeder::class,
            PageSeeder::class,
            ArticleSeeder::class,
        ]);
    }
}
```

- [ ] **Step 4: Run seeders against a fresh DB**

Run: `php artisan migrate:fresh --seed`
Expected: completes with no errors; `articles` table populated with 3 rows; `article_product` populated with up to 9 rows.

- [ ] **Step 5: Verify counts**

Run: `php artisan tinker --execute='echo \App\Models\Content\Article::count()." articles, ".\DB::table("article_product")->count()." pivot rows";'`
Expected: `3 articles, 9 pivot rows` (or fewer pivot rows if fewer products were seeded).

- [ ] **Step 6: Commit**

```bash
git add database/seeders/Content database/seeders/DatabaseSeeder.php
git commit -m "content: seeders for Page (placeholder) and Article (3 demo)"
```

---

## Task 10: Full test suite + final verification

- [ ] **Step 1: Run the whole test suite**

Run: `composer test`
Expected: all tests pass (existing catalogue tests + the new content tests). If anything fails, fix the failing test before continuing.

- [ ] **Step 2: Manual smoke of the admin UI**

Run: `php artisan serve` then in a browser open `http://127.0.0.1:8000/admin/`:
- Sign in as `admin@levantparfums.test` / `password`.
- Confirm a "Контент" navigation group exists with "Сторінки" and "Статті" entries.
- Click "Створити" (Create) on Articles. Fill in title/slug/content; add 2 products in the repeater; reorder them via drag handles; save. Open the record again — products are in the order you left them.
- Create a Page with slug `blog` — confirm the form rejects with an error.
- Switch the locale via the locale switcher header action; confirm the form fields swap between uk/en values cleanly.

- [ ] **Step 3: Commit anything left over**

If Step 1 or 2 surfaced small fixes (typos, missing translations, etc.), commit them as a single follow-up:

```bash
git add -A
git commit -m "content: follow-up fixes from verification"
```

If nothing changed, no commit is needed.

---

## Self-Review Notes (recorded before handoff)

Spec coverage check against `docs/superpowers/specs/2026-05-22-content-pages-articles-design.md`:

- **Data model** (pages, articles, article_product, functional unique indexes) — Tasks 2, 3.
- **Slug uniqueness defense in depth** (DB indexes / Filament rule / factory suffix) — Task 2 + 3 (DB), Task 7/8 (Filament rule), Task 4/5 (factory suffix via `Str::random(4)`).
- **Reserved slugs** (config + form rule + saving hook) — Task 1 (config), Task 4 (model hook), Task 7 (form rule).
- **Article visibility scope** (published) — Task 5.
- **Media** (primary collection, thumb/card/detail conversions) — Tasks 4 + 5 (`registerMediaCollections` / `registerMediaConversions`).
- **Filament admin** (Page + Article resources, products repeater pattern) — Tasks 7 + 8.
- **Translations** — Task 6.
- **Factories + seeders** — Tasks 4, 5, 9.
- **Tests** — Tasks 4, 5, 7, 8 (model + Filament).
- **Implementation order** from spec section "Implementation order" — followed verbatim with one merge (config + migrations are split out as separate tasks; tests are interleaved with implementation rather than batched at the end, which is more TDD-faithful).

Deviation from spec: the spec's Repeater snippet showed `->relationship()`, but the existing codebase pattern (`ProductForm::notesRepeater`) does NOT use `->relationship()` and instead handles attach/detach manually in `CreateProduct`/`EditProduct`. The plan follows the actual codebase pattern, not the spec snippet — both achieve the deterministic `sort_order` persistence the reviewer asked for.

Placeholder scan: no TBD / TODO / "implement later" left in any task body.

Type consistency check: `mutateFormDataBeforeCreate`/`mutateFormDataBeforeFill`/`mutateFormDataBeforeSave`/`afterCreate`/`afterSave` signatures match Filament 5 conventions used in `CreateProduct.php`/`EditProduct.php`. Repeater key `products`, inner field `product_id`, and pivot column `sort_order` are consistent across form, Pages, model, migration, and tests.
