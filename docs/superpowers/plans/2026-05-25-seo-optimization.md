# SEO Optimization Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add complete SEO surface (title/description, canonical, hreflang+x-default, OG, Twitter, JSON-LD, sitemap, robots) to every public page; eliminate duplicate-content indexing on the filtered/sorted catalog.

**Architecture:** New `App\Seo` domain module mirroring the existing `App\Forms` layout — one immutable `SeoData` DTO, per-page-type `Builder` classes, pure JSON-LD generator classes. Two Blade components in `<head>` render everything. Sitemap and robots served via dedicated controllers.

**Tech Stack:** Laravel 13, Filament 5, Spatie MediaLibrary, Spatie Translatable, mcamara/laravel-localization, Pest 4.

**Spec:** `docs/superpowers/specs/2026-05-25-seo-optimization-design.md`

---

## File map

**New PHP:**
- `app/Seo/SeoData.php`
- `app/Seo/AlternateUrlResolver.php`
- `app/Seo/Builders/CatalogSeoInput.php`
- `app/Seo/Builders/PageSeoBuilder.php`
- `app/Seo/Builders/ArticleSeoBuilder.php`
- `app/Seo/Builders/ArticleIndexSeoBuilder.php`
- `app/Seo/Builders/ProductSeoBuilder.php`
- `app/Seo/Builders/CatalogSeoBuilder.php`
- `app/Seo/StructuredData/OrganizationSchema.php`
- `app/Seo/StructuredData/WebSiteSchema.php`
- `app/Seo/StructuredData/BreadcrumbSchema.php`
- `app/Seo/StructuredData/ProductSchema.php`
- `app/Seo/StructuredData/ArticleSchema.php`
- `app/Http/Controllers/SitemapController.php`
- `app/Http/Controllers/RobotsController.php`

**New Blade:**
- `resources/views/components/site/json-ld.blade.php`
- `resources/views/components/site/seo-meta.blade.php`
- `resources/views/sitemap/index.blade.php`

**New tests:** `tests/Feature/Seo/*` (single namespace to match project convention — all Pest tests live under `tests/Feature`).

**Modified:**
- `config/site.php` (add `organization` + `seo` blocks)
- `.env.example` (document `SEO_ORG_*` keys)
- `app/Models/Catalogue/Product.php` (add `og` conversion)
- `app/Models/Content/Article.php` (add `og` conversion)
- `app/Models/Content/Page.php` (add `og` conversion)
- `app/Http/Controllers/PageController.php`
- `app/Http/Controllers/ProductCatalogController.php`
- `app/Http/Controllers/ArticleController.php`
- `routes/web.php` (add sitemap + robots routes outside the localised group)
- `resources/views/layouts/site.blade.php`
- `resources/views/products/index.blade.php` (remove old `@section` lines)
- `resources/views/products/show.blade.php` (same)
- `resources/views/articles/index.blade.php` (same)
- `resources/views/articles/show.blade.php` (same)
- `resources/views/pages/templates/simple.blade.php` (same)
- `resources/views/pages/templates/landing.blade.php` (same)

**Deleted:** `public/robots.txt`

**New static assets (placeholder during dev — designer artefacts later):**
- `public/images/og/default.jpg` (1200×630 brand cover)
- `public/images/og/logo.png` (square brand logo)

---

## Conventions used by every test in this plan

- Pest 4, `Tests\TestCase`, `RefreshDatabase` is auto-applied to anything under `tests/Feature` (configured in `tests/Pest.php`).
- Locale: lock the test session with `$this->withSession(['locale' => 'uk'])` in `beforeEach`. To exercise the `/en` URL prefix, call `refreshApplicationWithLocale('en')` (helper already defined in `tests/Pest.php`).
- Factories: `Product`, `Page`, `Article`, `Series`, `Tag`, `Brand`, etc. exist under `database/factories/{Catalogue,Content}/`.
- `APP_URL` in tests defaults to whatever `phpunit.xml` sets — assertions use `config('app.url')` to compose expected absolute URLs.
- Run a single test file: `php artisan test --filter=SeoDataTest`. Run a single test by name: `php artisan test --filter='it builds canonical'`.
- After every implementation step, run `./vendor/bin/pint --dirty` before committing.

---

## Task 1: Extend `config/site.php` with organization + seo blocks

**Files:**
- Modify: `config/site.php`
- Modify: `.env.example`
- Create: `public/images/og/default.jpg` (1200×630 placeholder)
- Create: `public/images/og/logo.png` (square placeholder)

- [ ] **Step 1: Create the OG placeholder directory and placeholder images**

```bash
mkdir -p public/images/og
# Placeholder 1200x630 brand image (real artwork lands later)
php -r '$im=imagecreatetruecolor(1200,630); $bg=imagecolorallocate($im,18,18,18); $fg=imagecolorallocate($im,235,225,205); imagefilledrectangle($im,0,0,1200,630,$bg); imagestring($im,5,520,310,"LEVANT PARFUMS",$fg); imagejpeg($im,"public/images/og/default.jpg",82); imagedestroy($im);'
# Square logo placeholder
php -r '$im=imagecreatetruecolor(512,512); $bg=imagecolorallocate($im,18,18,18); $fg=imagecolorallocate($im,235,225,205); imagefilledrectangle($im,0,0,512,512,$bg); imagestring($im,5,180,250,"LEVANT",$fg); imagepng($im,"public/images/og/logo.png"); imagedestroy($im);'
```

Expected: two files in `public/images/og/`.

- [ ] **Step 2: Extend `config/site.php`**

Replace file contents with:

```php
<?php

return [
    'themes' => [
        'theme-cream' => 'Cream (Luxury)',
        'theme-onyx' => 'Onyx (Dark)',
        'theme-editorial' => 'Editorial (Minimal)',
    ],

    'organization' => [
        'name'    => env('SEO_ORG_NAME', 'LEVANT Parfums'),
        'logo'    => env('SEO_ORG_LOGO', '/images/og/logo.png'),
        'phone'   => env('SEO_ORG_PHONE'),
        'email'   => env('SEO_ORG_EMAIL'),
        'address' => [
            'country'  => env('SEO_ORG_COUNTRY', 'UA'),
            'locality' => env('SEO_ORG_CITY'),
            'street'   => env('SEO_ORG_STREET'),
        ],
        'same_as' => array_values(array_filter(array_map('trim', explode(',', (string) env('SEO_ORG_SAME_AS', ''))))),
    ],

    'seo' => [
        'default_og_image' => '/images/og/default.jpg',
        'title_suffix'     => 'LEVANT Parfums',
        'twitter_card'     => 'summary_large_image',
    ],
];
```

- [ ] **Step 3: Append SEO env keys to `.env.example`**

Append at the end of `.env.example`:

```
SEO_ORG_NAME="LEVANT Parfums"
SEO_ORG_LOGO=/images/og/logo.png
SEO_ORG_PHONE=
SEO_ORG_EMAIL=
SEO_ORG_COUNTRY=UA
SEO_ORG_CITY=
SEO_ORG_STREET=
SEO_ORG_SAME_AS=
```

- [ ] **Step 4: Clear config cache and verify the config is loadable**

```bash
php artisan config:clear
php artisan tinker --execute='dump(config("site.organization.name"), config("site.seo.default_og_image"));'
```

Expected: `"LEVANT Parfums"` and `"/images/og/default.jpg"`.

- [ ] **Step 5: Commit**

```bash
git add config/site.php .env.example public/images/og/
git commit -m "chore(seo): add organization + seo config keys and OG placeholders"
```

---

## Task 2: Add `og` media conversion to Product, Article, Page

**Files:**
- Modify: `app/Models/Catalogue/Product.php:142-160`
- Modify: `app/Models/Content/Article.php` (`registerMediaConversions`)
- Modify: `app/Models/Content/Page.php` (`registerMediaConversions`)
- Test: `tests/Feature/Seo/OgMediaConversionTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/OgMediaConversionTest.php`:

```php
<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Http\UploadedFile;

beforeEach(function () {
    $this->withSession(['locale' => 'uk']);
    $this->image = UploadedFile::fake()->image('hero.jpg', 1600, 1200);
});

it('generates an og conversion for Product primary media', function () {
    $product = Product::factory()->for(Series::factory(), 'series')->create();
    $product->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    $media = $product->getFirstMedia('primary');
    expect($media->hasGeneratedConversion('og'))->toBeTrue();
});

it('generates an og conversion for Article primary media', function () {
    $article = Article::factory()->create();
    $article->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    expect($article->getFirstMedia('primary')->hasGeneratedConversion('og'))->toBeTrue();
});

it('generates an og conversion for Page primary media', function () {
    $page = Page::factory()->create();
    $page->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    expect($page->getFirstMedia('primary')->hasGeneratedConversion('og'))->toBeTrue();
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=OgMediaConversionTest
```

Expected: 3 failures with `hasGeneratedConversion('og')` returning `false`.

- [ ] **Step 3: Add the conversion to Product**

In `app/Models/Catalogue/Product.php`, inside `registerMediaConversions()` (after the `detail` block), add:

```php
$this->addMediaConversion('og')
    ->fit(Fit::Crop, 1200, 630)
    ->format('jpg')
    ->quality(82)
    ->nonQueued()
    ->performOnCollections('primary');
```

- [ ] **Step 4: Add the conversion to Article**

In `app/Models/Content/Article.php`, ensure `use Spatie\Image\Enums\Fit;` is imported (add if missing). Inside `registerMediaConversions()`, append the same block as in Step 3.

- [ ] **Step 5: Add the conversion to Page**

In `app/Models/Content/Page.php`, ensure `use Spatie\Image\Enums\Fit;` is imported. Inside `registerMediaConversions()`, append the same block.

- [ ] **Step 6: Run the test and verify it passes**

```bash
php artisan test --filter=OgMediaConversionTest
```

Expected: 3 passes.

- [ ] **Step 7: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Models/ tests/Feature/Seo/OgMediaConversionTest.php
git commit -m "feat(seo): add 1200x630 jpg og media conversion to Product, Article, Page"
```

---

## Task 3: Build the `SeoData` DTO

**Files:**
- Create: `app/Seo/SeoData.php`
- Test: `tests/Feature/Seo/SeoDataTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/SeoDataTest.php`:

```php
<?php

use App\Seo\SeoData;

it('constructs with required fields and sensible defaults', function () {
    $seo = new SeoData(
        title: 'Example',
        description: 'desc',
        canonical: 'https://example.test/foo',
    );

    expect($seo->title)->toBe('Example')
        ->and($seo->description)->toBe('desc')
        ->and($seo->canonical)->toBe('https://example.test/foo')
        ->and($seo->ogType)->toBe('website')
        ->and($seo->ogImage)->toBeNull()
        ->and($seo->alternates)->toBe([])
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->jsonLd)->toBe([])
        ->and($seo->publishedTime)->toBeNull()
        ->and($seo->modifiedTime)->toBeNull();
});

it('accepts full set of fields including alternates and jsonLd', function () {
    $seo = new SeoData(
        title: 'T',
        description: 'D',
        canonical: 'https://x.test/a',
        ogType: 'article',
        ogImage: 'https://x.test/og.jpg',
        ogImageWidth: 1200,
        ogImageHeight: 630,
        alternates: ['uk' => 'https://x.test/a', 'en' => 'https://x.test/en/a', 'x-default' => 'https://x.test/a'],
        robots: 'noindex,follow',
        jsonLd: [['@type' => 'Article']],
        publishedTime: '2026-05-25T10:00:00+00:00',
        modifiedTime: '2026-05-25T11:00:00+00:00',
    );

    expect($seo->ogType)->toBe('article')
        ->and($seo->ogImage)->toBe('https://x.test/og.jpg')
        ->and($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default'])
        ->and($seo->robots)->toBe('noindex,follow')
        ->and($seo->jsonLd[0]['@type'])->toBe('Article')
        ->and($seo->publishedTime)->toBe('2026-05-25T10:00:00+00:00');
});

it('is immutable (readonly)', function () {
    $seo = new SeoData(title: 'T', description: null, canonical: 'https://x.test/');

    expect(fn () => $seo->title = 'Other')->toThrow(Error::class);
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=SeoDataTest
```

Expected: failure — `Class "App\Seo\SeoData" not found`.

- [ ] **Step 3: Create the DTO**

Create `app/Seo/SeoData.php`:

```php
<?php

namespace App\Seo;

final readonly class SeoData
{
    /**
     * @param  array<string,string>  $alternates  hreflang code (e.g. 'uk', 'en', 'x-default') => absolute URL
     * @param  list<array<string,mixed>>  $jsonLd  list of JSON-LD graphs to emit on the page
     */
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,
        public string $ogType = 'website',
        public ?string $ogImage = null,
        public ?int $ogImageWidth = null,
        public ?int $ogImageHeight = null,
        public array $alternates = [],
        public string $robots = 'index,follow',
        public array $jsonLd = [],
        public ?string $publishedTime = null,
        public ?string $modifiedTime = null,
    ) {}
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=SeoDataTest
```

Expected: 3 passes.

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/SeoData.php tests/Feature/Seo/SeoDataTest.php
git commit -m "feat(seo): add SeoData DTO"
```

---

## Task 4: Build the `AlternateUrlResolver`

**Files:**
- Create: `app/Seo/AlternateUrlResolver.php`
- Test: `tests/Feature/Seo/AlternateUrlResolverTest.php`

This resolver builds absolute hreflang URLs without relying on `LaravelLocalization::getLocalizedURL`, because the default locale (uk) has no prefix and we need stable absolute URLs for canonical/OG/sitemap. The host is taken from `config('app.url')`. The `/en` prefix is added explicitly for the `en` locale.

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/AlternateUrlResolverTest.php`:

```php
<?php

use App\Seo\AlternateUrlResolver;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->resolver = new AlternateUrlResolver;
});

it('forSharedSlug emits both locales and x-default for a product', function () {
    $alternates = $this->resolver->forSharedSlug('/products/parfum-noir');

    expect($alternates)->toBe([
        'uk'        => 'https://example.test/products/parfum-noir',
        'en'        => 'https://example.test/en/products/parfum-noir',
        'x-default' => 'https://example.test/products/parfum-noir',
    ]);
});

it('forStaticRoute emits both locales for a path without query params', function () {
    expect($this->resolver->forStaticRoute('/products'))->toBe([
        'uk'        => 'https://example.test/products',
        'en'        => 'https://example.test/en/products',
        'x-default' => 'https://example.test/products',
    ]);
});

it('forStaticRoute appends query params verbatim to both locales', function () {
    expect($this->resolver->forStaticRoute('/products', ['page' => 2]))->toBe([
        'uk'        => 'https://example.test/products?page=2',
        'en'        => 'https://example.test/en/products?page=2',
        'x-default' => 'https://example.test/products?page=2',
    ]);
});

it('forStaticRoute treats / as the home path', function () {
    expect($this->resolver->forStaticRoute('/'))->toBe([
        'uk'        => 'https://example.test/',
        'en'        => 'https://example.test/en',
        'x-default' => 'https://example.test/',
    ]);
});

it('forTranslatedSlug emits both locales when both translations exist', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => 'pro-nas', 'en' => 'about']);

    expect($alternates)->toBe([
        'uk'        => 'https://example.test/pro-nas',
        'en'        => 'https://example.test/en/about',
        'x-default' => 'https://example.test/pro-nas',
    ]);
});

it('forTranslatedSlug omits a locale that has no translation', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => 'pro-nas', 'en' => null]);

    expect($alternates)->toBe([
        'uk'        => 'https://example.test/pro-nas',
        'x-default' => 'https://example.test/pro-nas',
    ]);
});

it('forTranslatedSlug omits x-default when uk translation is missing', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => null, 'en' => 'about']);

    expect($alternates)->toBe([
        'en' => 'https://example.test/en/about',
    ]);
});

it('forTranslatedSlug supports a nested path prefix', function () {
    $alternates = $this->resolver->forTranslatedSlug('/articles/', ['uk' => 'novyna', 'en' => 'news']);

    expect($alternates)->toBe([
        'uk'        => 'https://example.test/articles/novyna',
        'en'        => 'https://example.test/en/articles/news',
        'x-default' => 'https://example.test/articles/novyna',
    ]);
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=AlternateUrlResolverTest
```

Expected: failure — class missing.

- [ ] **Step 3: Implement `AlternateUrlResolver`**

Create `app/Seo/AlternateUrlResolver.php`:

```php
<?php

namespace App\Seo;

final class AlternateUrlResolver
{
    private const DEFAULT_LOCALE = 'uk';
    private const SUPPORTED = ['uk', 'en'];

    /**
     * Build hreflang map for a translated-slug route (Page, Article).
     *
     * @param  string  $pathPrefix  e.g. "/" for /{slug}, "/articles/" for /articles/{slug}.
     * @param  array<string,?string>  $slugs  locale => translated slug (null = no translation)
     * @return array<string,string>
     */
    public function forTranslatedSlug(string $pathPrefix, array $slugs): array
    {
        $result = [];

        foreach (self::SUPPORTED as $locale) {
            $slug = $slugs[$locale] ?? null;
            if ($slug === null || $slug === '') {
                continue;
            }
            $result[$locale] = $this->buildUrl($locale, $pathPrefix.$slug);
        }

        if (isset($result[self::DEFAULT_LOCALE])) {
            $result['x-default'] = $result[self::DEFAULT_LOCALE];
        }

        return $result;
    }

    /**
     * Build hreflang map for a shared-slug route (Product — slug identical across locales).
     *
     * @return array<string,string>
     */
    public function forSharedSlug(string $path): array
    {
        $result = [];
        foreach (self::SUPPORTED as $locale) {
            $result[$locale] = $this->buildUrl($locale, $path);
        }
        $result['x-default'] = $result[self::DEFAULT_LOCALE];

        return $result;
    }

    /**
     * Build hreflang map for a static route (home, /products, /articles).
     *
     * @param  array<string,scalar>  $queryParams
     * @return array<string,string>
     */
    public function forStaticRoute(string $path, array $queryParams = []): array
    {
        $query = $queryParams === [] ? '' : '?'.http_build_query($queryParams);

        $result = [];
        foreach (self::SUPPORTED as $locale) {
            $result[$locale] = $this->buildUrl($locale, $path).$query;
        }
        $result['x-default'] = $result[self::DEFAULT_LOCALE];

        return $result;
    }

    private function buildUrl(string $locale, string $path): string
    {
        $base = rtrim((string) config('app.url'), '/');

        if ($locale === self::DEFAULT_LOCALE) {
            return $base.$path;
        }

        // Non-default locale: insert `/en` prefix. Avoid double slashes when path is "/".
        if ($path === '/') {
            return $base.'/'.$locale;
        }

        return $base.'/'.$locale.$path;
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=AlternateUrlResolverTest
```

Expected: 8 passes.

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/AlternateUrlResolver.php tests/Feature/Seo/AlternateUrlResolverTest.php
git commit -m "feat(seo): add AlternateUrlResolver for hreflang URL generation"
```

---

## Task 5: Build the `CatalogSeoInput` DTO

**Files:**
- Create: `app/Seo/Builders/CatalogSeoInput.php`

This is a trivial DTO with no behaviour; tests for it are folded into `CatalogSeoBuilderTest` later.

- [ ] **Step 1: Create the DTO**

Create `app/Seo/Builders/CatalogSeoInput.php`:

```php
<?php

namespace App\Seo\Builders;

final readonly class CatalogSeoInput
{
    public function __construct(
        public bool $hasSortParam,
        public bool $hasSeriesParam,
        public int $page = 1,
    ) {}
}
```

- [ ] **Step 2: Verify autoload by tinker probe**

```bash
php artisan tinker --execute='dump(new App\Seo\Builders\CatalogSeoInput(true, false, 2));'
```

Expected: dump shows the readonly object with the three properties.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/CatalogSeoInput.php
git commit -m "feat(seo): add CatalogSeoInput DTO for raw catalog query state"
```

---

## Task 6: Build `OrganizationSchema` and `WebSiteSchema`

**Files:**
- Create: `app/Seo/StructuredData/OrganizationSchema.php`
- Create: `app/Seo/StructuredData/WebSiteSchema.php`
- Test: `tests/Feature/Seo/StructuredData/OrganizationSchemaTest.php`
- Test: `tests/Feature/Seo/StructuredData/WebSiteSchemaTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Seo/StructuredData/OrganizationSchemaTest.php`:

```php
<?php

use App\Seo\StructuredData\OrganizationSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization' => [
            'name'    => 'LEVANT Parfums',
            'logo'    => '/images/og/logo.png',
            'phone'   => '+380000000000',
            'email'   => 'hi@example.test',
            'address' => ['country' => 'UA', 'locality' => 'Kyiv', 'street' => 'Some St 1'],
            'same_as' => ['https://instagram.com/levant', 'https://t.me/levant'],
        ],
    ]);
});

it('emits a fully populated Organization graph', function () {
    $data = OrganizationSchema::generate();

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@type'])->toBe('Organization')
        ->and($data['name'])->toBe('LEVANT Parfums')
        ->and($data['url'])->toBe('https://example.test/')
        ->and($data['logo'])->toBe('https://example.test/images/og/logo.png')
        ->and($data['email'])->toBe('hi@example.test')
        ->and($data['telephone'])->toBe('+380000000000')
        ->and($data['address'])->toMatchArray([
            '@type'           => 'PostalAddress',
            'addressCountry'  => 'UA',
            'addressLocality' => 'Kyiv',
            'streetAddress'   => 'Some St 1',
        ])
        ->and($data['sameAs'])->toBe(['https://instagram.com/levant', 'https://t.me/levant']);
});

it('omits optional fields that are empty', function () {
    config([
        'site.organization' => [
            'name'    => 'LEVANT Parfums',
            'logo'    => '/images/og/logo.png',
            'phone'   => null,
            'email'   => null,
            'address' => ['country' => null, 'locality' => null, 'street' => null],
            'same_as' => [],
        ],
    ]);

    $data = OrganizationSchema::generate();

    expect($data)->not->toHaveKey('email')
        ->and($data)->not->toHaveKey('telephone')
        ->and($data)->not->toHaveKey('address')
        ->and($data)->not->toHaveKey('sameAs');
});
```

Create `tests/Feature/Seo/StructuredData/WebSiteSchemaTest.php`:

```php
<?php

use App\Seo\StructuredData\WebSiteSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
});

it('emits WebSite graph for uk locale with uk-UA inLanguage', function () {
    $data = WebSiteSchema::generate('uk');

    expect($data['@type'])->toBe('WebSite')
        ->and($data['url'])->toBe('https://example.test/')
        ->and($data['name'])->toBe('LEVANT Parfums')
        ->and($data['inLanguage'])->toBe('uk-UA');
});

it('emits en-GB inLanguage for en locale', function () {
    expect(WebSiteSchema::generate('en')['inLanguage'])->toBe('en-GB');
});
```

- [ ] **Step 2: Run the tests and verify they fail**

```bash
php artisan test --filter='OrganizationSchemaTest|WebSiteSchemaTest'
```

Expected: failures (classes missing).

- [ ] **Step 3: Implement `OrganizationSchema`**

Create `app/Seo/StructuredData/OrganizationSchema.php`:

```php
<?php

namespace App\Seo\StructuredData;

final class OrganizationSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(): array
    {
        $org = (array) config('site.organization');
        $base = rtrim((string) config('app.url'), '/');
        $logo = (string) ($org['logo'] ?? '');

        $data = [
            '@context' => 'https://schema.org',
            '@type'    => 'Organization',
            'name'     => (string) ($org['name'] ?? ''),
            'url'      => $base.'/',
            'logo'     => self::absolutize($logo, $base),
        ];

        if (! empty($org['email'])) {
            $data['email'] = $org['email'];
        }
        if (! empty($org['phone'])) {
            $data['telephone'] = $org['phone'];
        }

        $address = $org['address'] ?? [];
        $addressFields = array_filter([
            'addressCountry'  => $address['country'] ?? null,
            'addressLocality' => $address['locality'] ?? null,
            'streetAddress'   => $address['street'] ?? null,
        ]);
        if ($addressFields !== []) {
            $data['address'] = ['@type' => 'PostalAddress', ...$addressFields];
        }

        $sameAs = array_values(array_filter((array) ($org['same_as'] ?? [])));
        if ($sameAs !== []) {
            $data['sameAs'] = $sameAs;
        }

        return $data;
    }

    private static function absolutize(string $path, string $base): string
    {
        if ($path === '') {
            return $base.'/';
        }
        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        return $base.'/'.ltrim($path, '/');
    }
}
```

- [ ] **Step 4: Implement `WebSiteSchema`**

Create `app/Seo/StructuredData/WebSiteSchema.php`:

```php
<?php

namespace App\Seo\StructuredData;

final class WebSiteSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(string $locale): array
    {
        $base = rtrim((string) config('app.url'), '/');

        return [
            '@context'   => 'https://schema.org',
            '@type'      => 'WebSite',
            'url'        => $base.'/',
            'name'       => (string) config('site.organization.name', 'LEVANT Parfums'),
            'inLanguage' => $locale === 'uk' ? 'uk-UA' : 'en-GB',
        ];
    }
}
```

- [ ] **Step 5: Run the tests and verify they pass**

```bash
php artisan test --filter='OrganizationSchemaTest|WebSiteSchemaTest'
```

Expected: 4 passes.

- [ ] **Step 6: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/StructuredData/ tests/Feature/Seo/StructuredData/
git commit -m "feat(seo): add Organization and WebSite JSON-LD generators"
```

---

## Task 7: Build `BreadcrumbSchema`

**Files:**
- Create: `app/Seo/StructuredData/BreadcrumbSchema.php`
- Test: `tests/Feature/Seo/StructuredData/BreadcrumbSchemaTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/StructuredData/BreadcrumbSchemaTest.php`:

```php
<?php

use App\Seo\StructuredData\BreadcrumbSchema;

it('produces a BreadcrumbList with 1-indexed items', function () {
    $data = BreadcrumbSchema::generate([
        ['name' => 'Home', 'url' => 'https://example.test/'],
        ['name' => 'Catalogue', 'url' => 'https://example.test/products'],
        ['name' => 'Parfum Noir', 'url' => 'https://example.test/products/parfum-noir'],
    ]);

    expect($data['@type'])->toBe('BreadcrumbList')
        ->and($data['itemListElement'])->toHaveCount(3)
        ->and($data['itemListElement'][0])->toMatchArray([
            '@type'    => 'ListItem',
            'position' => 1,
            'name'     => 'Home',
            'item'     => 'https://example.test/',
        ])
        ->and($data['itemListElement'][2]['position'])->toBe(3)
        ->and($data['itemListElement'][2]['name'])->toBe('Parfum Noir');
});

it('returns an empty graph (no schema) when given no crumbs', function () {
    expect(BreadcrumbSchema::generate([]))->toBe([]);
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=BreadcrumbSchemaTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/StructuredData/BreadcrumbSchema.php`:

```php
<?php

namespace App\Seo\StructuredData;

final class BreadcrumbSchema
{
    /**
     * @param  list<array{name:string,url:string}>  $crumbs
     * @return array<string,mixed>
     */
    public static function generate(array $crumbs): array
    {
        if ($crumbs === []) {
            return [];
        }

        return [
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => array_map(
                static fn (array $crumb, int $index): array => [
                    '@type'    => 'ListItem',
                    'position' => $index + 1,
                    'name'     => $crumb['name'],
                    'item'     => $crumb['url'],
                ],
                $crumbs,
                array_keys($crumbs),
            ),
        ];
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=BreadcrumbSchemaTest
```

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/StructuredData/BreadcrumbSchema.php tests/Feature/Seo/StructuredData/BreadcrumbSchemaTest.php
git commit -m "feat(seo): add BreadcrumbList JSON-LD generator"
```

---

## Task 8: Build `ProductSchema`

**Files:**
- Create: `app/Seo/StructuredData/ProductSchema.php`
- Test: `tests/Feature/Seo/StructuredData/ProductSchemaTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/StructuredData/ProductSchemaTest.php`:

```php
<?php

use App\Models\Catalogue\Brand;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Seo\StructuredData\ProductSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
    $this->series = Series::factory()->create();
    $this->family = PerfumeFamily::factory()->create([
        'name' => ['uk' => 'Шипрові', 'en' => 'Chypre'],
    ]);
});

it('emits Product graph with UAH for uk locale', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create([
            'name'        => ['uk' => 'Парфум Нуар', 'en' => 'Parfum Noir'],
            'description' => ['uk' => 'Опис парфуму', 'en' => 'Description'],
            'price_uah'   => '2400.00',
            'price_eur'   => '60.00',
            'in_stock'    => true,
        ]);

    $data = ProductSchema::generate(
        $product,
        locale: 'uk',
        canonical: 'https://example.test/products/'.$product->slug,
        ogImage: 'https://example.test/images/og/default.jpg',
    );

    expect($data['@type'])->toBe('Product')
        ->and($data['name'])->toBe('Парфум Нуар')
        ->and($data['description'])->toBe('Опис парфуму')
        ->and($data['image'])->toBe(['https://example.test/images/og/default.jpg'])
        ->and($data['sku'])->toBe((string) $product->id)
        ->and($data['category'])->toBe('Шипрові')
        ->and($data['offers']['priceCurrency'])->toBe('UAH')
        ->and($data['offers']['price'])->toBe('2400.00')
        ->and($data['offers']['availability'])->toBe('https://schema.org/InStock')
        ->and($data['offers']['itemCondition'])->toBe('https://schema.org/NewCondition')
        ->and($data['offers']['url'])->toBe('https://example.test/products/'.$product->slug);
});

it('emits EUR for en locale', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['price_uah' => '2400.00', 'price_eur' => '60.00']);

    $data = ProductSchema::generate($product, 'en', 'https://example.test/en/products/x', null);

    expect($data['offers']['priceCurrency'])->toBe('EUR')
        ->and($data['offers']['price'])->toBe('60.00');
});

it('emits OutOfStock when in_stock is false', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['in_stock' => false]);

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data['offers']['availability'])->toBe('https://schema.org/OutOfStock');
});

it('always uses organization name as brand, never inspired brand', function () {
    $inspired = Brand::factory()->create(['name' => 'Tom Ford']);
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->for($inspired, 'inspiredBrand')
        ->create();

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data['brand'])->toBe(['@type' => 'Brand', 'name' => 'LEVANT Parfums']);
});

it('omits image key when no ogImage is provided', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create();

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data)->not->toHaveKey('image');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=ProductSchemaTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/StructuredData/ProductSchema.php`:

```php
<?php

namespace App\Seo\StructuredData;

use App\Models\Catalogue\Product;
use Illuminate\Support\Str;

final class ProductSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(Product $product, string $locale, string $canonical, ?string $ogImage): array
    {
        $currency = $locale === 'uk' ? 'UAH' : 'EUR';
        $price = $locale === 'uk' ? (string) $product->price_uah : (string) $product->price_eur;
        $availability = $product->in_stock
            ? 'https://schema.org/InStock'
            : 'https://schema.org/OutOfStock';

        $data = [
            '@context'    => 'https://schema.org',
            '@type'       => 'Product',
            'name'        => (string) $product->getTranslation('name', $locale),
            'description' => Str::limit(
                trim(strip_tags((string) $product->getTranslation('description', $locale))),
                500,
                ''
            ),
            'sku'         => (string) $product->id,
            'brand'       => ['@type' => 'Brand', 'name' => (string) config('site.organization.name', 'LEVANT Parfums')],
            'offers'      => [
                '@type'         => 'Offer',
                'url'           => $canonical,
                'priceCurrency' => $currency,
                'price'         => $price,
                'availability'  => $availability,
                'itemCondition' => 'https://schema.org/NewCondition',
            ],
        ];

        if ($ogImage !== null && $ogImage !== '') {
            $data['image'] = [$ogImage];
        }

        $family = $product->perfumeFamily;
        if ($family !== null) {
            $category = (string) $family->getTranslation('name', $locale);
            if ($category !== '') {
                $data['category'] = $category;
            }
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=ProductSchemaTest
```

Expected: 5 passes.

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/StructuredData/ProductSchema.php tests/Feature/Seo/StructuredData/ProductSchemaTest.php
git commit -m "feat(seo): add Product JSON-LD generator with locale-aware currency"
```

---

## Task 9: Build `ArticleSchema`

**Files:**
- Create: `app/Seo/StructuredData/ArticleSchema.php`
- Test: `tests/Feature/Seo/StructuredData/ArticleSchemaTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/StructuredData/ArticleSchemaTest.php`:

```php
<?php

use App\Models\Content\Article;
use App\Seo\StructuredData\ArticleSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
        'site.organization.logo' => '/images/og/logo.png',
    ]);
});

it('emits Article graph for uk locale', function () {
    $article = Article::factory()->create([
        'title'        => ['uk' => 'Заголовок', 'en' => 'Headline'],
        'intro'        => ['uk' => 'Інтро', 'en' => 'Intro'],
        'published_at' => '2026-05-20 10:00:00',
    ]);

    $data = ArticleSchema::generate(
        $article,
        locale: 'uk',
        canonical: 'https://example.test/articles/zagolovok',
        ogImage: 'https://example.test/og.jpg',
    );

    expect($data['@type'])->toBe('Article')
        ->and($data['headline'])->toBe('Заголовок')
        ->and($data['description'])->toBe('Інтро')
        ->and($data['image'])->toBe('https://example.test/og.jpg')
        ->and($data['datePublished'])->toStartWith('2026-05-20T10:00:00')
        ->and($data['dateModified'])->not->toBeNull()
        ->and($data['author'])->toBe(['@type' => 'Organization', 'name' => 'LEVANT Parfums'])
        ->and($data['publisher']['@type'])->toBe('Organization')
        ->and($data['publisher']['logo']['url'])->toBe('https://example.test/images/og/logo.png')
        ->and($data['mainEntityOfPage'])->toBe('https://example.test/articles/zagolovok')
        ->and($data['inLanguage'])->toBe('uk-UA');
});

it('emits en-GB inLanguage for en locale', function () {
    $article = Article::factory()->create();

    $data = ArticleSchema::generate($article, 'en', 'https://example.test/en/articles/x', null);

    expect($data['inLanguage'])->toBe('en-GB');
});

it('omits image when no ogImage is provided', function () {
    $article = Article::factory()->create();

    $data = ArticleSchema::generate($article, 'uk', 'https://example.test/articles/x', null);

    expect($data)->not->toHaveKey('image');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=ArticleSchemaTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/StructuredData/ArticleSchema.php`:

```php
<?php

namespace App\Seo\StructuredData;

use App\Models\Content\Article;
use Illuminate\Support\Str;

final class ArticleSchema
{
    /**
     * @return array<string,mixed>
     */
    public static function generate(Article $article, string $locale, string $canonical, ?string $ogImage): array
    {
        $base = rtrim((string) config('app.url'), '/');
        $orgName = (string) config('site.organization.name', 'LEVANT Parfums');
        $logoPath = (string) config('site.organization.logo', '/images/og/logo.png');
        $logoUrl = str_starts_with($logoPath, 'http') ? $logoPath : $base.'/'.ltrim($logoPath, '/');

        $description = Str::limit(
            trim(strip_tags((string) $article->getTranslation('intro', $locale))),
            300,
            ''
        );

        $data = [
            '@context'         => 'https://schema.org',
            '@type'            => 'Article',
            'headline'         => (string) $article->getTranslation('title', $locale),
            'description'      => $description,
            'datePublished'    => $article->published_at?->toIso8601String(),
            'dateModified'     => $article->updated_at?->toIso8601String(),
            'author'           => ['@type' => 'Organization', 'name' => $orgName],
            'publisher'        => [
                '@type' => 'Organization',
                'name'  => $orgName,
                'logo'  => ['@type' => 'ImageObject', 'url' => $logoUrl],
            ],
            'mainEntityOfPage' => $canonical,
            'inLanguage'       => $locale === 'uk' ? 'uk-UA' : 'en-GB',
        ];

        if ($ogImage !== null && $ogImage !== '') {
            $data['image'] = $ogImage;
        }

        return $data;
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=ArticleSchemaTest
```

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/StructuredData/ArticleSchema.php tests/Feature/Seo/StructuredData/ArticleSchemaTest.php
git commit -m "feat(seo): add Article JSON-LD generator"
```

---

## Task 10: Build `PageSeoBuilder`

**Files:**
- Create: `app/Seo/Builders/PageSeoBuilder.php`
- Test: `tests/Feature/Seo/Builders/PageSeoBuilderTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/Builders/PageSeoBuilderTest.php`:

```php
<?php

use App\Models\Content\Page;
use App\Seo\Builders\PageSeoBuilder;
use App\Seo\SeoData;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
    ]);
    $this->builder = app(PageSeoBuilder::class);
});

it('returns SeoData with title fallback chain', function () {
    $page = Page::factory()->create([
        'title'       => ['uk' => 'Про нас', 'en' => 'About us'],
        'seo_title'   => ['uk' => null, 'en' => null],
        'slug'        => ['uk' => 'pro-nas', 'en' => 'about'],
        'is_homepage' => false,
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo)->toBeInstanceOf(SeoData::class)
        ->and($seo->title)->toBe('Про нас · LEVANT Parfums');
});

it('prefers seo_title when present and does not double-append suffix', function () {
    $page = Page::factory()->create([
        'title'     => ['uk' => 'Про нас'],
        'seo_title' => ['uk' => 'Про нас · LEVANT Parfums'],
        'slug'      => ['uk' => 'pro-nas'],
    ]);

    expect($this->builder->build($page, 'uk')->title)->toBe('Про нас · LEVANT Parfums');
});

it('builds canonical and both-locale alternates for a fully translated page', function () {
    $page = Page::factory()->create([
        'slug' => ['uk' => 'pro-nas', 'en' => 'about'],
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo->canonical)->toBe('https://example.test/pro-nas')
        ->and($seo->alternates)->toBe([
            'uk'        => 'https://example.test/pro-nas',
            'en'        => 'https://example.test/en/about',
            'x-default' => 'https://example.test/pro-nas',
        ]);
});

it('omits en alternate when only uk translation exists', function () {
    $page = Page::factory()->create([
        'slug' => ['uk' => 'pro-nas', 'en' => null],
    ]);

    $alternates = $this->builder->build($page, 'uk')->alternates;

    expect($alternates)->toHaveKeys(['uk', 'x-default'])
        ->and($alternates)->not->toHaveKey('en');
});

it('uses static-route alternates and "/" canonical for the homepage', function () {
    $page = Page::factory()->create([
        'is_homepage' => true,
        'title'       => ['uk' => 'Головна'],
        'slug'        => ['uk' => 'home', 'en' => 'home'],
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo->canonical)->toBe('https://example.test/')
        ->and($seo->alternates['uk'])->toBe('https://example.test/');
});

it('falls back to default og image when page has no media', function () {
    $page = Page::factory()->create(['slug' => ['uk' => 'x']]);

    expect($this->builder->build($page, 'uk')->ogImage)
        ->toBe('https://example.test/images/og/default.jpg');
});

it('marks ogType as website and robots as index,follow', function () {
    $page = Page::factory()->create(['slug' => ['uk' => 'x']]);
    $seo = $this->builder->build($page, 'uk');

    expect($seo->ogType)->toBe('website')->and($seo->robots)->toBe('index,follow');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=PageSeoBuilderTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/Builders/PageSeoBuilder.php`:

```php
<?php

namespace App\Seo\Builders;

use App\Models\Content\Page;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;
use Illuminate\Support\Str;

final class PageSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Page $page, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $page->getTranslation('seo_title', $locale),
            (string) $page->getTranslation('title', $locale),
        );

        $description = $this->buildDescription(
            (string) $page->getTranslation('seo_description', $locale),
            (string) $page->getTranslation('intro', $locale),
            (string) $page->getTranslation('content', $locale),
        );

        $alternates = $page->is_homepage
            ? $this->resolver->forStaticRoute('/')
            : $this->resolver->forTranslatedSlug('/', $page->getTranslations('slug'));

        $canonical = $alternates[$locale] ?? $alternates['x-default'] ?? (string) config('app.url');

        $ogImage = $this->resolveOgImage($page);

        $jsonLd = [];
        if (! $page->is_homepage) {
            $jsonLd[] = BreadcrumbSchema::generate([
                ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                ['name' => (string) $page->getTranslation('title', $locale), 'url' => $canonical],
            ]);
        }

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'website',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter($jsonLd)),
        );
    }

    private function buildTitle(string $seoTitle, string $title): string
    {
        $base = $seoTitle !== '' ? $seoTitle : $title;
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        if ($base === '' || str_contains($base, $suffix)) {
            return $base !== '' ? $base : $suffix;
        }

        return $base.' · '.$suffix;
    }

    private function buildDescription(string $seoDescription, string $intro, string $content): ?string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }
        $source = $intro !== '' ? $intro : $content;
        if ($source === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($source)), 160);
    }

    private function resolveOgImage(Page $page): string
    {
        $media = $page->getFirstMedia('primary');
        $base = rtrim((string) config('app.url'), '/');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        $fallback = (string) config('site.seo.default_og_image', '/images/og/default.jpg');

        return $base.'/'.ltrim($fallback, '/');
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=PageSeoBuilderTest
```

Expected: 7 passes. The breadcrumb label uses `catalogue.public.crumb_home` which already exists in `lang/uk/catalogue.php` (= "Головна"); if for some reason a similar key needs adding to `lang/en/catalogue.php`, mirror the uk value as "Home".

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/PageSeoBuilder.php tests/Feature/Seo/Builders/PageSeoBuilderTest.php
git commit -m "feat(seo): add PageSeoBuilder with translated-slug alternates and homepage handling"
```

---

## Task 11: Build `ArticleSeoBuilder`

**Files:**
- Create: `app/Seo/Builders/ArticleSeoBuilder.php`
- Test: `tests/Feature/Seo/Builders/ArticleSeoBuilderTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/Builders/ArticleSeoBuilderTest.php`:

```php
<?php

use App\Models\Content\Article;
use App\Seo\Builders\ArticleSeoBuilder;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
        'site.organization.name' => 'LEVANT Parfums',
        'site.organization.logo' => '/images/og/logo.png',
    ]);
    $this->builder = app(ArticleSeoBuilder::class);
});

it('builds canonical, alternates and article json-ld', function () {
    $article = Article::factory()->create([
        'title'        => ['uk' => 'Стаття', 'en' => 'Article'],
        'intro'        => ['uk' => 'Вступ', 'en' => 'Intro'],
        'slug'         => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => '2026-05-20 10:00:00',
    ]);

    $seo = $this->builder->build($article, 'uk');

    expect($seo->title)->toBe('Стаття · LEVANT Parfums')
        ->and($seo->canonical)->toBe('https://example.test/articles/novyna')
        ->and($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default'])
        ->and($seo->ogType)->toBe('article')
        ->and($seo->publishedTime)->toStartWith('2026-05-20T10:00:00')
        ->and($seo->modifiedTime)->not->toBeNull()
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('Article')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('BreadcrumbList');
});

it('drops the en alternate when only uk slug exists', function () {
    $article = Article::factory()->create(['slug' => ['uk' => 'novyna', 'en' => null]]);

    $alternates = $this->builder->build($article, 'uk')->alternates;

    expect($alternates)->toHaveKeys(['uk', 'x-default'])
        ->and($alternates)->not->toHaveKey('en');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=ArticleSeoBuilderTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/Builders/ArticleSeoBuilder.php`:

```php
<?php

namespace App\Seo\Builders;

use App\Models\Content\Article;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\ArticleSchema;
use App\Seo\StructuredData\BreadcrumbSchema;
use Illuminate\Support\Str;

final class ArticleSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Article $article, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $article->getTranslation('seo_title', $locale),
            (string) $article->getTranslation('title', $locale),
        );

        $description = $this->buildDescription(
            (string) $article->getTranslation('seo_description', $locale),
            (string) $article->getTranslation('intro', $locale),
            (string) $article->getTranslation('content', $locale),
        );

        $alternates = $this->resolver->forTranslatedSlug('/articles/', $article->getTranslations('slug'));
        $canonical = $alternates[$locale] ?? $alternates['x-default'] ?? (string) config('app.url');
        $ogImage = $this->resolveOgImage($article);

        $articleSchema = ArticleSchema::generate($article, $locale, $canonical, $ogImage);
        $breadcrumb = BreadcrumbSchema::generate([
            ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
            ['name' => (string) trans('site.articles.meta_title'), 'url' => $this->resolver->forStaticRoute('/articles')[$locale]],
            ['name' => (string) $article->getTranslation('title', $locale), 'url' => $canonical],
        ]);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'article',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([$articleSchema, $breadcrumb])),
            publishedTime: $article->published_at?->toIso8601String(),
            modifiedTime: $article->updated_at?->toIso8601String(),
        );
    }

    private function buildTitle(string $seoTitle, string $title): string
    {
        $base = $seoTitle !== '' ? $seoTitle : $title;
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        if ($base === '' || str_contains($base, $suffix)) {
            return $base !== '' ? $base : $suffix;
        }

        return $base.' · '.$suffix;
    }

    private function buildDescription(string $seoDescription, string $intro, string $content): ?string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }
        $source = $intro !== '' ? $intro : $content;
        if ($source === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($source)), 160);
    }

    private function resolveOgImage(Article $article): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $media = $article->getFirstMedia('primary');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        return $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=ArticleSeoBuilderTest
```

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/ArticleSeoBuilder.php tests/Feature/Seo/Builders/ArticleSeoBuilderTest.php
git commit -m "feat(seo): add ArticleSeoBuilder"
```

---

## Task 12: Build `ArticleIndexSeoBuilder`

**Files:**
- Create: `app/Seo/Builders/ArticleIndexSeoBuilder.php`
- Test: `tests/Feature/Seo/Builders/ArticleIndexSeoBuilderTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/Builders/ArticleIndexSeoBuilderTest.php`:

```php
<?php

use App\Seo\Builders\ArticleIndexSeoBuilder;

beforeEach(function () {
    config(['app.url' => 'https://example.test', 'site.seo.title_suffix' => 'LEVANT Parfums']);
    $this->builder = app(ArticleIndexSeoBuilder::class);
});

it('builds canonical /articles for page 1', function () {
    $seo = $this->builder->build('uk', page: 1);

    expect($seo->canonical)->toBe('https://example.test/articles')
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->alternates['uk'])->toBe('https://example.test/articles')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/articles')
        ->and($seo->alternates['x-default'])->toBe('https://example.test/articles');
});

it('self-canonicalises page > 1 with ?page= in the URL', function () {
    $seo = $this->builder->build('uk', page: 3);

    expect($seo->canonical)->toBe('https://example.test/articles?page=3')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/articles?page=3')
        ->and($seo->robots)->toBe('index,follow');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=ArticleIndexSeoBuilderTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/Builders/ArticleIndexSeoBuilder.php`:

```php
<?php

namespace App\Seo\Builders;

use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;

final class ArticleIndexSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(string $locale, int $page = 1): SeoData
    {
        $query = $page > 1 ? ['page' => $page] : [];
        $alternates = $this->resolver->forStaticRoute('/articles', $query);
        $canonical = $alternates[$locale];

        $title = (string) trans('site.articles.meta_title');
        $description = (string) trans('site.articles.meta_description');
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        return new SeoData(
            title: str_contains($title, $suffix) ? $title : $title.' · '.$suffix,
            description: $description !== '' ? $description : null,
            canonical: $canonical,
            ogType: 'website',
            ogImage: rtrim((string) config('app.url'), '/').'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/'),
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([
                BreadcrumbSchema::generate([
                    ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                    ['name' => $title, 'url' => $canonical],
                ]),
            ])),
        );
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=ArticleIndexSeoBuilderTest
```

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/ArticleIndexSeoBuilder.php tests/Feature/Seo/Builders/ArticleIndexSeoBuilderTest.php
git commit -m "feat(seo): add ArticleIndexSeoBuilder with pagination self-canonical"
```

---

## Task 13: Build `ProductSeoBuilder`

**Files:**
- Create: `app/Seo/Builders/ProductSeoBuilder.php`
- Test: `tests/Feature/Seo/Builders/ProductSeoBuilderTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/Builders/ProductSeoBuilderTest.php`:

```php
<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Seo\Builders\ProductSeoBuilder;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
    $this->builder = app(ProductSeoBuilder::class);
    $this->series = Series::factory()->create();
    $this->family = PerfumeFamily::factory()->create();
});

it('builds canonical with shared slug and product json-ld', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create([
            'slug'      => 'parfum-noir',
            'name'      => ['uk' => 'Парфум Нуар', 'en' => 'Parfum Noir'],
            'price_uah' => '2400.00',
            'price_eur' => '60.00',
        ]);

    $seo = $this->builder->build($product, 'uk');

    expect($seo->canonical)->toBe('https://example.test/products/parfum-noir')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/products/parfum-noir')
        ->and($seo->ogType)->toBe('product')
        ->and($seo->title)->toBe('Парфум Нуар · LEVANT Parfums')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('Product')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('BreadcrumbList');
});

it('uses EUR currency in Product json-ld when locale is en', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['slug' => 'p', 'price_uah' => '2400.00', 'price_eur' => '60.00']);

    $seo = $this->builder->build($product, 'en');
    $offer = collect($seo->jsonLd)->firstWhere('@type', 'Product')['offers'];

    expect($offer['priceCurrency'])->toBe('EUR')->and($offer['price'])->toBe('60.00');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=ProductSeoBuilderTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/Builders/ProductSeoBuilder.php`:

```php
<?php

namespace App\Seo\Builders;

use App\Models\Catalogue\Product;
use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;
use App\Seo\StructuredData\ProductSchema;
use Illuminate\Support\Str;

final class ProductSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(Product $product, string $locale): SeoData
    {
        $title = $this->buildTitle(
            (string) $product->getTranslation('seo_title', $locale),
            (string) $product->getTranslation('name', $locale),
        );

        $description = $this->buildDescription(
            (string) $product->getTranslation('seo_description', $locale),
            (string) $product->getTranslation('tagline', $locale),
            (string) $product->getTranslation('description', $locale),
        );

        $alternates = $this->resolver->forSharedSlug('/products/'.$product->slug);
        $canonical = $alternates[$locale];
        $ogImage = $this->resolveOgImage($product);

        $productSchema = ProductSchema::generate($product, $locale, $canonical, $ogImage);
        $breadcrumb = BreadcrumbSchema::generate([
            ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
            ['name' => (string) trans('catalogue.public.title'), 'url' => $this->resolver->forStaticRoute('/products')[$locale]],
            ['name' => (string) $product->getTranslation('name', $locale), 'url' => $canonical],
        ]);

        return new SeoData(
            title: $title,
            description: $description,
            canonical: $canonical,
            ogType: 'product',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: 'index,follow',
            jsonLd: array_values(array_filter([$productSchema, $breadcrumb])),
        );
    }

    private function buildTitle(string $seoTitle, string $name): string
    {
        $base = $seoTitle !== '' ? $seoTitle : $name;
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        if ($base === '' || str_contains($base, $suffix)) {
            return $base !== '' ? $base : $suffix;
        }

        return $base.' · '.$suffix;
    }

    private function buildDescription(string $seoDescription, string $tagline, string $description): ?string
    {
        if ($seoDescription !== '') {
            return $seoDescription;
        }
        if ($tagline !== '') {
            return $tagline;
        }
        if ($description === '') {
            return null;
        }

        return Str::limit(trim(strip_tags($description)), 160);
    }

    private function resolveOgImage(Product $product): string
    {
        $base = rtrim((string) config('app.url'), '/');
        $media = $product->getFirstMedia('primary');

        if ($media !== null) {
            $url = $media->getUrl('og');

            return str_starts_with($url, 'http') ? $url : $base.'/'.ltrim($url, '/');
        }

        return $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=ProductSeoBuilderTest
```

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/ProductSeoBuilder.php tests/Feature/Seo/Builders/ProductSeoBuilderTest.php
git commit -m "feat(seo): add ProductSeoBuilder with shared-slug alternates"
```

---

## Task 14: Build `CatalogSeoBuilder`

**Files:**
- Create: `app/Seo/Builders/CatalogSeoBuilder.php`
- Test: `tests/Feature/Seo/Builders/CatalogSeoBuilderTest.php`

- [ ] **Step 1: Write the failing test (covers the full indexing matrix)**

Create `tests/Feature/Seo/Builders/CatalogSeoBuilderTest.php`:

```php
<?php

use App\Seo\Builders\CatalogSeoBuilder;
use App\Seo\Builders\CatalogSeoInput;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
    ]);
    $this->builder = app(CatalogSeoBuilder::class);
});

it('clean /products is index,follow with canonical /products', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 1), 'uk');

    expect($seo->canonical)->toBe('https://example.test/products')
        ->and($seo->robots)->toBe('index,follow');
});

it('?page=2 alone is self-canonical and index,follow', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 2), 'uk');

    expect($seo->canonical)->toBe('https://example.test/products?page=2')
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/products?page=2');
});

it('?sort=* (any value, including pop) is noindex with canonical /products', function () {
    foreach (['pop', 'priceA', 'priceB', 'bad'] as $sort) {
        $seo = $this->builder->build(new CatalogSeoInput(hasSortParam: true, hasSeriesParam: false, page: 1), 'uk');

        expect($seo->robots)->toBe('noindex,follow')
            ->and($seo->canonical)->toBe('https://example.test/products');
    }
});

it('?series=* is noindex with canonical /products', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, true, 1), 'uk');

    expect($seo->robots)->toBe('noindex,follow')
        ->and($seo->canonical)->toBe('https://example.test/products');
});

it('?page=2&sort=priceA is noindex with canonical /products?page=2', function () {
    $seo = $this->builder->build(new CatalogSeoInput(true, false, 2), 'uk');

    expect($seo->robots)->toBe('noindex,follow')
        ->and($seo->canonical)->toBe('https://example.test/products?page=2');
});

it('always provides both-locale alternates for catalog', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 1), 'uk');

    expect($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default']);
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=CatalogSeoBuilderTest
```

- [ ] **Step 3: Implement**

Create `app/Seo/Builders/CatalogSeoBuilder.php`:

```php
<?php

namespace App\Seo\Builders;

use App\Seo\AlternateUrlResolver;
use App\Seo\SeoData;
use App\Seo\StructuredData\BreadcrumbSchema;

final class CatalogSeoBuilder
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function build(CatalogSeoInput $input, string $locale): SeoData
    {
        $isFiltered = $input->hasSortParam || $input->hasSeriesParam;
        $query = $input->page > 1 ? ['page' => $input->page] : [];
        $alternates = $this->resolver->forStaticRoute('/products', $query);
        $canonical = $alternates[$locale];

        $robots = $isFiltered ? 'noindex,follow' : 'index,follow';

        $title = (string) trans('catalogue.public.title');
        $description = (string) trans('catalogue.public.subtitle');
        $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');

        $base = rtrim((string) config('app.url'), '/');
        $ogImage = $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');

        return new SeoData(
            title: str_contains($title, $suffix) ? $title : $title.' · '.$suffix,
            description: $description !== '' ? $description : null,
            canonical: $canonical,
            ogType: 'website',
            ogImage: $ogImage,
            ogImageWidth: 1200,
            ogImageHeight: 630,
            alternates: $alternates,
            robots: $robots,
            jsonLd: array_values(array_filter([
                BreadcrumbSchema::generate([
                    ['name' => (string) trans('catalogue.public.crumb_home'), 'url' => $this->resolver->forStaticRoute('/')[$locale]],
                    ['name' => $title, 'url' => $this->resolver->forStaticRoute('/products')[$locale]],
                ]),
            ])),
        );
    }
}
```

- [ ] **Step 4: Run the test and verify it passes**

```bash
php artisan test --filter=CatalogSeoBuilderTest
```

Expected: 6 passes.

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Seo/Builders/CatalogSeoBuilder.php tests/Feature/Seo/Builders/CatalogSeoBuilderTest.php
git commit -m "feat(seo): add CatalogSeoBuilder with full noindex matrix"
```

---

## Task 15: Build the `<x-site.json-ld>` Blade component

**Files:**
- Create: `resources/views/components/site/json-ld.blade.php`

This component wraps an array in a `<script type="application/ld+json">` block. Skips rendering when the array is empty.

- [ ] **Step 1: Create the component**

Create `resources/views/components/site/json-ld.blade.php`:

```blade
@props(['data' => []])

@if(! empty($data))
    <script type="application/ld+json">{!! json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}</script>
@endif
```

- [ ] **Step 2: Smoke-check renderability**

```bash
php artisan tinker --execute='echo Blade::render(\'<x-site.json-ld :data="$d" />\', ["d" => ["@type" => "Thing"]]);'
```

Expected output contains `<script type="application/ld+json">{"@type":"Thing"}</script>`.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add resources/views/components/site/json-ld.blade.php
git commit -m "feat(seo): add json-ld Blade component"
```

---

## Task 16: Build the `<x-site.seo-meta>` Blade component

**Files:**
- Create: `resources/views/components/site/seo-meta.blade.php`

Renders the full SEO head: title, description, canonical, hreflang+x-default, OG, Twitter, robots, per-page JSON-LD. When `$seo === null`, falls back to defaults from `config('site.seo')`.

- [ ] **Step 1: Create the component**

Create `resources/views/components/site/seo-meta.blade.php`:

```blade
@props(['seo' => null, 'locale' => 'uk'])

@php
    $suffix = (string) config('site.seo.title_suffix', 'LEVANT Parfums');
    $base = rtrim((string) config('app.url'), '/');
    $defaultOg = $base.'/'.ltrim((string) config('site.seo.default_og_image', '/images/og/default.jpg'), '/');

    $title       = $seo?->title ?? $suffix;
    $description = $seo?->description;
    $canonical   = $seo?->canonical ?? $base.request()->getPathInfo();
    $robots      = $seo?->robots ?? 'index,follow';
    $ogType      = $seo?->ogType ?? 'website';
    $ogImage     = $seo?->ogImage ?? $defaultOg;
    $ogImageW    = $seo?->ogImageWidth ?? 1200;
    $ogImageH    = $seo?->ogImageHeight ?? 630;
    $alternates  = $seo?->alternates ?? [];
    $jsonLd      = $seo?->jsonLd ?? [];
    $ogLocale    = $locale === 'uk' ? 'uk_UA' : 'en_GB';
    $ogLocaleAlt = $locale === 'uk' ? 'en_GB' : 'uk_UA';
@endphp

<title>{{ $title }}</title>
@if($description)
    <meta name="description" content="{{ $description }}">
@endif
<meta name="robots" content="{{ $robots }}">
<link rel="canonical" href="{{ $canonical }}">

@foreach($alternates as $hreflang => $url)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
@endforeach

<meta property="og:type" content="{{ $ogType }}">
<meta property="og:title" content="{{ $title }}">
@if($description)
    <meta property="og:description" content="{{ $description }}">
@endif
<meta property="og:url" content="{{ $canonical }}">
<meta property="og:site_name" content="{{ $suffix }}">
<meta property="og:locale" content="{{ $ogLocale }}">
<meta property="og:locale:alternate" content="{{ $ogLocaleAlt }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="{{ $ogImageW }}">
<meta property="og:image:height" content="{{ $ogImageH }}">

@if($ogType === 'article' && $seo?->publishedTime)
    <meta property="article:published_time" content="{{ $seo->publishedTime }}">
    @if($seo->modifiedTime)
        <meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
    @endif
@endif

<meta name="twitter:card" content="{{ config('site.seo.twitter_card', 'summary_large_image') }}">
<meta name="twitter:title" content="{{ $title }}">
@if($description)
    <meta name="twitter:description" content="{{ $description }}">
@endif
<meta name="twitter:image" content="{{ $ogImage }}">

@foreach($jsonLd as $schema)
    <x-site.json-ld :data="$schema" />
@endforeach
```

- [ ] **Step 2: Smoke-check the no-SeoData path**

```bash
php artisan tinker --execute='echo Blade::render(\'<x-site.seo-meta />\');'
```

Expected output contains `<title>LEVANT Parfums</title>` and `<meta name="robots" content="index,follow">`.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add resources/views/components/site/seo-meta.blade.php
git commit -m "feat(seo): add seo-meta Blade component"
```

---

## Task 17: Integrate the SEO head into the site layout and strip obsolete `@section` from page views

**Files:**
- Modify: `resources/views/layouts/site.blade.php`
- Modify: `resources/views/products/index.blade.php`
- Modify: `resources/views/products/show.blade.php`
- Modify: `resources/views/articles/index.blade.php`
- Modify: `resources/views/articles/show.blade.php`
- Modify: `resources/views/pages/templates/simple.blade.php`
- Modify: `resources/views/pages/templates/landing.blade.php`

- [ ] **Step 1: Update the layout**

Replace `resources/views/layouts/site.blade.php` with:

```blade
@php($currentLocale = app()->getLocale())
@php($seo = $seo ?? null)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', $currentLocale) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <x-site.seo-meta :seo="$seo" :locale="$currentLocale" />
    <x-site.json-ld :data="\App\Seo\StructuredData\OrganizationSchema::generate()" />
    <x-site.json-ld :data="\App\Seo\StructuredData\WebSiteSchema::generate($currentLocale)" />

    @fonts
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="{{ $theme ?? 'theme-cream' }}">
    <x-site.intro-veil />
    <x-site.announcement />
    <x-site.header :locale="$currentLocale" />

    <main class="page-fade">
        @yield('content')
    </main>

    <x-site.footer :locale="$currentLocale" />

    @stack('scripts')
</body>
</html>
```

- [ ] **Step 2: Strip the old `@section('title')` / `@section('description')` directives from six page views**

The six files have three different shapes — handle each precisely:

**a) Single-line inline (3 files):** `resources/views/products/index.blade.php`, `resources/views/products/show.blade.php`, `resources/views/articles/index.blade.php`. Each has lines 3 and 4 of the form:

```blade
@section('title', ...)
@section('description', ...)
```

Delete both lines (leave the blank line that precedes `@section('content')`).

**b) Two-line description (1 file):** `resources/views/articles/show.blade.php`. Lines 3–5 are:

```blade
@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))
```

Delete all three lines.

**c) Conditional description block (2 files):** `resources/views/pages/templates/simple.blade.php`, `resources/views/pages/templates/landing.blade.php`. Lines 3–6 are:

```blade
@section('title', $page->seo_title ?: $page->title)
@if($page->seo_description)
    @section('description', $page->seo_description)
@endif
```

Delete all four lines.

Verify with:

```bash
grep -n "@section('title'\|@section('description'" resources/views/products/index.blade.php resources/views/products/show.blade.php resources/views/articles/index.blade.php resources/views/articles/show.blade.php resources/views/pages/templates/simple.blade.php resources/views/pages/templates/landing.blade.php
```

Expected: no output (zero matches).

- [ ] **Step 3: Smoke-render one page to confirm nothing exploded**

```bash
php artisan serve --port=8123 >/tmp/serve.log 2>&1 &
SERVER_PID=$!
sleep 2
curl -s http://127.0.0.1:8123/products | grep -E '<title>|rel="canonical"|og:type' | head -5
kill $SERVER_PID 2>/dev/null
```

Expected output: at least `<title>` and a canonical/OG line. (The page may still show defaults since controllers don't pass `$seo` yet — that's intentional, fixed in Task 18+.)

- [ ] **Step 4: Run the full test suite**

```bash
composer test
```

Expected: all existing tests still pass (none assert old `@section('title')` output).

- [ ] **Step 5: Commit**

```bash
./vendor/bin/pint --dirty
git add resources/views/
git commit -m "feat(seo): integrate seo-meta into layout; remove obsolete @section title/description"
```

---

## Task 18: Wire `PageSeoBuilder` into `PageController`

**Files:**
- Modify: `app/Http/Controllers/PageController.php`

- [ ] **Step 1: Update both methods**

Replace `app/Http/Controllers/PageController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Content\Page;
use App\Seo\Builders\PageSeoBuilder;
use Illuminate\Support\Facades\View;

class PageController extends Controller
{
    public function __construct(private readonly PageSeoBuilder $seoBuilder) {}

    public function home()
    {
        $page = Page::query()->homepage()->published()->firstOrFail();
        $seo = $this->seoBuilder->build($page, app()->getLocale());

        return view("pages.templates.{$page->template->value}", ['page' => $page, 'seo' => $seo]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $page = Page::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        View::share('alternateSlugs', $page->getTranslations('slug'));

        $seo = $this->seoBuilder->build($page, $locale);

        return view("pages.templates.{$page->template->value}", ['page' => $page, 'seo' => $seo]);
    }
}
```

- [ ] **Step 2: Quick smoke test**

```bash
php artisan test --filter='HomePageRenderTest|PageRoutingTest|ContactsPageTest|PhilosophyPageTest'
```

Expected: existing tests still pass.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/PageController.php
git commit -m "feat(seo): wire PageSeoBuilder into PageController"
```

---

## Task 19: Wire `ProductSeoBuilder` and `CatalogSeoBuilder` into `ProductCatalogController`

**Files:**
- Modify: `app/Http/Controllers/ProductCatalogController.php`

- [ ] **Step 1: Update the controller**

Replace `app/Http/Controllers/ProductCatalogController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Catalogue\Product;
use App\Seo\Builders\CatalogSeoBuilder;
use App\Seo\Builders\CatalogSeoInput;
use App\Seo\Builders\ProductSeoBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ProductCatalogController extends Controller
{
    private const PER_PAGE = 8;

    private const ALLOWED_SERIES = ['onyx', 'luxury'];

    private const ALLOWED_SORTS = ['pop', 'new', 'priceA', 'priceB'];

    public function __construct(
        private readonly CatalogSeoBuilder $catalogSeoBuilder,
        private readonly ProductSeoBuilder $productSeoBuilder,
    ) {}

    public function index(Request $request): View
    {
        $rawSeries = $request->query('series');
        $series = in_array($rawSeries, self::ALLOWED_SERIES, true) ? $rawSeries : null;

        $rawSort = $request->query('sort', 'pop');
        $sort = in_array($rawSort, self::ALLOWED_SORTS, true) ? $rawSort : 'pop';

        $base = Product::query()
            ->where('is_published', true)
            ->when($series, fn (Builder $q) => $q->whereHas(
                'series',
                fn (Builder $s) => $s->where('slug', $series)
            ));

        $list = (clone $base)
            ->with(['series', 'perfumeFamily', 'tags', 'media']);

        $this->applySort($list, $sort);

        $products = $list->paginate(self::PER_PAGE)->withQueryString();

        $total = (clone $base)->count();
        $totalAll = Product::where('is_published', true)->count();

        $seo = $this->catalogSeoBuilder->build(
            new CatalogSeoInput(
                hasSortParam:   $request->has('sort'),
                hasSeriesParam: $request->has('series'),
                page:           max(1, $request->integer('page', 1)),
            ),
            app()->getLocale(),
        );

        return view('products.index', [
            'products' => $products,
            'total' => $total,
            'totalAll' => $totalAll,
            'series' => $series,
            'sort' => $sort,
            'seo' => $seo,
        ]);
    }

    public function show(Product $product): View
    {
        abort_unless($product->is_published, 404);

        $product->load(['series', 'perfumeFamily', 'concentration', 'notes', 'tags', 'occasions', 'media']);

        $sameSeries = Product::query()
            ->where('is_published', true)
            ->where('series_id', $product->series_id)
            ->where('id', '!=', $product->id)
            ->with(['series', 'perfumeFamily', 'tags', 'media'])
            ->take(6)
            ->get();

        if ($sameSeries->count() < 4 && $product->series_id) {
            $need = 6 - $sameSeries->count();
            $cross = Product::query()
                ->where('is_published', true)
                ->where('series_id', '!=', $product->series_id)
                ->where('id', '!=', $product->id)
                ->with(['series', 'perfumeFamily', 'tags', 'media'])
                ->take($need)
                ->get();
            $related = $sameSeries->concat($cross);
        } else {
            $related = $sameSeries;
        }

        $theme = $product->series?->theme_class ?? 'theme-cream';

        $seo = $this->productSeoBuilder->build($product, app()->getLocale());

        return view('products.show', compact('product', 'related', 'theme', 'seo'));
    }

    private function applySort(Builder $query, string $sort): void
    {
        $tagPredicate = fn (string $slug) => "EXISTS (
            SELECT 1 FROM product_tag pt
            JOIN tags t ON t.id = pt.tag_id
            WHERE pt.product_id = products.id AND t.slug = '{$slug}'
        )";

        match ($sort) {
            'new' => $query
                ->orderByRaw($tagPredicate('new').' DESC')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
            'priceA' => $query
                ->orderBy('price_uah')
                ->orderBy('id'),
            'priceB' => $query
                ->orderByDesc('price_uah')
                ->orderBy('id'),
            default => $query
                ->orderByRaw($tagPredicate('bestseller').' DESC')
                ->orderByDesc('published_at')
                ->orderByDesc('id'),
        };
    }
}
```

- [ ] **Step 2: Run existing public/catalog tests**

```bash
php artisan test --filter='ProductCatalogTest|ProductShowTest|LayoutThemeTest'
```

Expected: pass.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/ProductCatalogController.php
git commit -m "feat(seo): wire Catalog/ProductSeoBuilders into ProductCatalogController"
```

---

## Task 20: Wire `ArticleSeoBuilder` / `ArticleIndexSeoBuilder` into `ArticleController`

**Files:**
- Modify: `app/Http/Controllers/ArticleController.php`

- [ ] **Step 1: Update the controller**

Replace `app/Http/Controllers/ArticleController.php` with:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Content\Article;
use App\Seo\Builders\ArticleIndexSeoBuilder;
use App\Seo\Builders\ArticleSeoBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleIndexSeoBuilder $indexSeoBuilder,
        private readonly ArticleSeoBuilder $showSeoBuilder,
    ) {}

    public function index(Request $request)
    {
        $articles = Article::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->paginate(12);

        $seo = $this->indexSeoBuilder->build(app()->getLocale(), max(1, $request->integer('page', 1)));

        return view('articles.index', compact('articles', 'seo'));
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $article = Article::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        $products = $article->products()
            ->with(['media', 'tags', 'series', 'perfumeFamily'])
            ->get();

        $related = Article::query()
            ->published()
            ->with('media')
            ->where('id', '!=', $article->id)
            ->latest('published_at')
            ->take(3)
            ->get();

        View::share('alternateSlugs', $article->getTranslations('slug'));

        $seo = $this->showSeoBuilder->build($article, $locale);

        return view('articles.show', compact('article', 'products', 'related', 'seo'));
    }
}
```

- [ ] **Step 2: Run existing article tests**

```bash
php artisan test --filter='ArticleListPageTest|ArticleShowPageTest|ArticleTest'
```

Expected: pass.

- [ ] **Step 3: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/ArticleController.php
git commit -m "feat(seo): wire ArticleSeoBuilders into ArticleController"
```

---

## Task 21: Build the RobotsController and route

**Files:**
- Create: `app/Http/Controllers/RobotsController.php`
- Modify: `routes/web.php`
- Delete: `public/robots.txt`
- Test: `tests/Feature/Seo/RobotsTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/RobotsTest.php`:

```php
<?php

beforeEach(fn () => config(['app.url' => 'https://example.test']));

it('serves robots.txt with admin disallow and sitemap reference', function () {
    $response = $this->get('/robots.txt');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('text/plain');

    $body = $response->getContent();
    expect($body)
        ->toContain('User-agent: *')
        ->toContain('Disallow: /admin')
        ->toContain('Sitemap: https://example.test/sitemap.xml');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=RobotsTest
```

- [ ] **Step 3: Delete the static `robots.txt` so the route takes over**

```bash
rm public/robots.txt
```

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/RobotsController.php`:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class RobotsController extends Controller
{
    public function __invoke(): Response
    {
        $sitemapUrl = rtrim((string) config('app.url'), '/').'/sitemap.xml';

        $body = <<<TXT
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/*

Sitemap: {$sitemapUrl}
TXT;

        return response($body, 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
```

- [ ] **Step 5: Register the route — outside the localised group**

In `routes/web.php`, before the existing `Route::group([...])` block, add:

```php
use App\Http\Controllers\RobotsController;

Route::get('/robots.txt', RobotsController::class)->name('robots');
```

Keep the import grouped with the other controller imports at the top of the file.

- [ ] **Step 6: Run the test and verify it passes**

```bash
php artisan test --filter=RobotsTest
```

- [ ] **Step 7: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/RobotsController.php routes/web.php tests/Feature/Seo/RobotsTest.php
git rm public/robots.txt
git commit -m "feat(seo): serve robots.txt via controller with sitemap reference"
```

---

## Task 22: Build the SitemapController and view

**Files:**
- Create: `app/Http/Controllers/SitemapController.php`
- Create: `resources/views/sitemap/index.blade.php`
- Modify: `routes/web.php`
- Test: `tests/Feature/Seo/SitemapTest.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Seo/SitemapTest.php`:

```php
<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    Cache::forget('sitemap.xml');
});

it('serves /sitemap.xml as application/xml with all seeded entities', function () {
    Page::factory()->create([
        'is_published' => true,
        'is_homepage'  => true,
        'title'        => ['uk' => 'Головна', 'en' => 'Home'],
        'slug'         => ['uk' => 'home', 'en' => 'home'],
    ]);
    $about = Page::factory()->create([
        'is_published' => true,
        'slug'         => ['uk' => 'pro-nas', 'en' => 'about'],
    ]);
    $product = Product::factory()
        ->for(Series::factory(), 'series')
        ->for(PerfumeFamily::factory(), 'perfumeFamily')
        ->create(['is_published' => true, 'slug' => 'parfum-noir']);
    $article = Article::factory()->create([
        'is_published' => true,
        'slug'         => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/xml');

    $body = $response->getContent();
    expect($body)
        ->toContain('<?xml')
        ->toContain('https://example.test/')
        ->toContain('https://example.test/en')
        ->toContain('https://example.test/products')
        ->toContain('https://example.test/articles')
        ->toContain('https://example.test/pro-nas')
        ->toContain('https://example.test/en/about')
        ->toContain('https://example.test/products/parfum-noir')
        ->toContain('https://example.test/en/products/parfum-noir')
        ->toContain('https://example.test/articles/novyna')
        ->toContain('https://example.test/en/articles/news')
        ->toContain('xhtml:link')
        ->toContain('hreflang="x-default"');
});

it('omits hreflang for missing translations on a single-locale page', function () {
    Page::factory()->create([
        'is_published' => true,
        'slug'         => ['uk' => 'tilki-uk', 'en' => null],
    ]);

    $body = $this->get('/sitemap.xml')->getContent();

    // The uk URL appears, with x-default sibling; no /en/tilki-uk link.
    expect($body)
        ->toContain('https://example.test/tilki-uk')
        ->not->toContain('https://example.test/en/tilki-uk');
});
```

- [ ] **Step 2: Run the test and verify it fails**

```bash
php artisan test --filter=SitemapTest
```

- [ ] **Step 3: Create the view**

Create `resources/views/sitemap/index.blade.php`:

```blade
<?php echo '<?xml version="1.0" encoding="UTF-8"?>'."\n"; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
@foreach($entries as $entry)
    <url>
        <loc>{{ $entry['loc'] }}</loc>
        @if(! empty($entry['lastmod']))
            <lastmod>{{ $entry['lastmod'] }}</lastmod>
        @endif
        @foreach($entry['alternates'] as $hreflang => $href)
            <xhtml:link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $href }}" />
        @endforeach
    </url>
@endforeach
</urlset>
```

- [ ] **Step 4: Create the controller**

Create `app/Http/Controllers/SitemapController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use App\Models\Content\Page;
use App\Seo\AlternateUrlResolver;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    public function __construct(private readonly AlternateUrlResolver $resolver) {}

    public function __invoke(): Response
    {
        $xml = Cache::remember('sitemap.xml', 3600, function () {
            $entries = $this->buildEntries();

            return view('sitemap.index', ['entries' => $entries])->render();
        });

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }

    /**
     * @return list<array{loc:string,lastmod:?string,alternates:array<string,string>}>
     */
    private function buildEntries(): array
    {
        $now = Carbon::now()->toIso8601String();
        $entries = [];

        // Home and section index pages: static routes, both locales, x-default = uk.
        foreach (['/', '/products', '/articles'] as $path) {
            $alts = $this->resolver->forStaticRoute($path);
            $entries[] = ['loc' => $alts['uk'], 'lastmod' => $now, 'alternates' => $alts];
        }

        // CMS pages: translated slug.
        Page::query()
            ->where('is_published', true)
            ->get(['id', 'is_homepage', 'slug', 'updated_at'])
            ->each(function (Page $page) use (&$entries) {
                if ($page->is_homepage) {
                    return; // home is already covered by the static-route block above
                }
                $alts = $this->resolver->forTranslatedSlug('/', $page->getTranslations('slug'));
                if ($alts === []) {
                    return;
                }
                $loc = $alts['x-default'] ?? reset($alts);
                $entries[] = ['loc' => $loc, 'lastmod' => $page->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        // Products: shared slug, both locales always present.
        Product::query()
            ->where('is_published', true)
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Product $product) use (&$entries) {
                $alts = $this->resolver->forSharedSlug('/products/'.$product->slug);
                $entries[] = ['loc' => $alts['x-default'], 'lastmod' => $product->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        // Articles: translated slug.
        Article::query()
            ->published()
            ->get(['id', 'slug', 'updated_at'])
            ->each(function (Article $article) use (&$entries) {
                $alts = $this->resolver->forTranslatedSlug('/articles/', $article->getTranslations('slug'));
                if ($alts === []) {
                    return;
                }
                $loc = $alts['x-default'] ?? reset($alts);
                $entries[] = ['loc' => $loc, 'lastmod' => $article->updated_at?->toIso8601String(), 'alternates' => $alts];
            });

        return $entries;
    }
}
```

- [ ] **Step 5: Register the route — outside the localised group**

In `routes/web.php`, alongside the `robots` route added in Task 21, add:

```php
use App\Http\Controllers\SitemapController;

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
```

- [ ] **Step 6: Run the test and verify it passes**

```bash
php artisan test --filter=SitemapTest
```

Expected: 2 passes.

- [ ] **Step 7: Commit**

```bash
./vendor/bin/pint --dirty
git add app/Http/Controllers/SitemapController.php resources/views/sitemap/ routes/web.php tests/Feature/Seo/SitemapTest.php
git commit -m "feat(seo): serve dynamic sitemap.xml with hreflang alternates"
```

---

## Task 23: End-to-end layout SEO feature test

**Files:**
- Test: `tests/Feature/Seo/LayoutSeoTest.php`

This is a high-level smoke test that asserts the head includes everything expected on each major route. No new production code — purely verification that the integration works end-to-end.

- [ ] **Step 1: Write the test**

Create `tests/Feature/Seo/LayoutSeoTest.php`:

```php
<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->withSession(['locale' => 'uk']);
});

function assertSeoBaseline(\Illuminate\Testing\TestResponse $response): void
{
    $response->assertOk();
    $body = $response->getContent();
    expect($body)
        ->toContain('<title>')
        ->toContain('<link rel="canonical"')
        ->toContain('rel="alternate" hreflang="uk"')
        ->toContain('rel="alternate" hreflang="x-default"')
        ->toContain('property="og:type"')
        ->toContain('property="og:title"')
        ->toContain('property="og:image"')
        ->toContain('name="twitter:card"')
        ->toContain('"@type":"Organization"')
        ->toContain('"@type":"WebSite"');
}

it('emits SEO baseline on the homepage', function () {
    Page::factory()->create([
        'is_published' => true,
        'is_homepage'  => true,
        'title'        => ['uk' => 'Головна', 'en' => 'Home'],
        'slug'         => ['uk' => 'home', 'en' => 'home'],
    ]);

    assertSeoBaseline($this->get('/'));
});

it('emits SEO baseline on /products and is index,follow', function () {
    $response = $this->get('/products');
    assertSeoBaseline($response);
    expect($response->getContent())->toContain('content="index,follow"');
});

it('emits SEO baseline plus Product json-ld on a product page', function () {
    $product = Product::factory()
        ->for(Series::factory(), 'series')
        ->for(PerfumeFamily::factory(), 'perfumeFamily')
        ->create(['is_published' => true, 'slug' => 'parfum-noir']);

    $response = $this->get('/products/'.$product->slug);
    assertSeoBaseline($response);
    expect($response->getContent())
        ->toContain('"@type":"Product"')
        ->toContain('property="og:type" content="product"');
});

it('emits SEO baseline plus Article json-ld on an article page', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'slug'         => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/articles/'.$article->getTranslation('slug', 'uk'));
    assertSeoBaseline($response);
    expect($response->getContent())
        ->toContain('"@type":"Article"')
        ->toContain('property="og:type" content="article"');
});

it('emits SEO baseline on a CMS page', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'slug'         => ['uk' => 'pro-nas', 'en' => 'about'],
        'title'        => ['uk' => 'Про нас', 'en' => 'About'],
    ]);

    assertSeoBaseline($this->get('/'.$page->getTranslation('slug', 'uk')));
});
```

- [ ] **Step 2: Run the test**

```bash
php artisan test --filter=LayoutSeoTest
```

Expected: 5 passes.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Seo/LayoutSeoTest.php
git commit -m "test(seo): add end-to-end LayoutSeoTest covering every public surface"
```

---

## Task 24: Catalog indexing matrix feature test

**Files:**
- Test: `tests/Feature/Seo/CatalogIndexingTest.php`

- [ ] **Step 1: Write the test**

Create `tests/Feature/Seo/CatalogIndexingTest.php`:

```php
<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->withSession(['locale' => 'uk']);
    $this->series = Series::factory()->create(['slug' => 'onyx']);
    $this->family = PerfumeFamily::factory()->create();
    Product::factory()
        ->count(12)
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['is_published' => true]);
});

it('clean /products is index,follow with canonical /products', function () {
    $body = $this->get('/products')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?page=2 is index,follow with self-canonical', function () {
    $body = $this->get('/products?page=2')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products?page=2">');
});

it('?sort=priceA is noindex with canonical /products', function () {
    $body = $this->get('/products?sort=priceA')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?sort=pop (explicit default) is still noindex', function () {
    $body = $this->get('/products?sort=pop')->getContent();

    expect($body)->toContain('<meta name="robots" content="noindex,follow">');
});

it('?sort=bad (invalid value) is still noindex', function () {
    $body = $this->get('/products?sort=bad')->getContent();

    expect($body)->toContain('<meta name="robots" content="noindex,follow">');
});

it('?series=onyx is noindex with canonical /products', function () {
    $body = $this->get('/products?series=onyx')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?page=2&sort=priceA is noindex with canonical /products?page=2', function () {
    $body = $this->get('/products?page=2&sort=priceA')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products?page=2">');
});
```

- [ ] **Step 2: Run the test**

```bash
php artisan test --filter=CatalogIndexingTest
```

Expected: 7 passes.

- [ ] **Step 3: Commit**

```bash
git add tests/Feature/Seo/CatalogIndexingTest.php
git commit -m "test(seo): cover catalog indexing matrix (clean/page/sort/series combos)"
```

---

## Task 25: Final full-suite green and cleanup commit

**Files:** (none — verification only)

- [ ] **Step 1: Run the entire test suite**

```bash
composer test
```

Expected: all tests pass, including the existing public/catalogue/content suites that were untouched.

- [ ] **Step 2: Format everything**

```bash
./vendor/bin/pint
```

If Pint changes any files, commit them:

```bash
git diff --stat
git add -u
git commit -m "chore(seo): pint pass after SEO module"
```

If there's nothing to commit, skip.

- [ ] **Step 3: Sanity-check a live render**

```bash
php artisan serve --port=8123 >/tmp/serve.log 2>&1 &
SERVER_PID=$!
sleep 2
echo "--- /products ---"
curl -s http://127.0.0.1:8123/products | grep -E '<title>|canonical|og:type|hreflang|@type' | head -20
echo "--- /sitemap.xml ---"
curl -s -o /dev/null -w "%{http_code} %{content_type}\n" http://127.0.0.1:8123/sitemap.xml
echo "--- /robots.txt ---"
curl -s http://127.0.0.1:8123/robots.txt
kill $SERVER_PID 2>/dev/null
```

Expected: meta tags rendered on `/products`, `sitemap.xml` returns `200 application/xml`, `robots.txt` contains a `Sitemap:` line.

---

## Done

Once Task 25 passes, every public page emits a complete SEO head, the catalog correctly avoids duplicate-content indexing, structured data covers Organization/WebSite/Product/Article/Breadcrumb, and `sitemap.xml` + `robots.txt` are wired up. Follow-up (out of scope for this plan):

- Remove the now-unused `View::share('alternateSlugs', …)` in `PageController` and `ArticleController` after rewiring the `lang-switch` / `mobile-menu` Blade components to read alternates from `$seo` instead.
- Replace placeholder OG images in `public/images/og/` with designer artefacts.
- Add `SEO_ORG_*` values to the real `.env` (production deploy).
