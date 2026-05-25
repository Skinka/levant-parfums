# SEO Optimization — Design Spec

**Date:** 2026-05-25
**Status:** Approved (brainstorm phase)

## Goal

Make every public page indexable correctly: emit accurate `<title>`, `<meta description>`, canonical URL, hreflang alternates, Open Graph + Twitter Card tags, and JSON-LD structured data. Add a dynamic `sitemap.xml` and a Laravel-served `robots.txt`. Eliminate duplicate-content issues on the filtered/sorted catalog.

## Non-goals

- No SearchAction in WebSite JSON-LD (no on-site search exists).
- No AggregateRating/Review on Product (no review subsystem).
- No FAQ schema (no FAQ content type).
- No multi-currency Offer in Product JSON-LD — one currency per locale (UAH for `uk`, EUR for `en`), mirroring the visible UI price.
- No Spatie LaravelSettings package — Organization data lives in `config/site.php` + `.env`.
- No hreflang in HTTP headers — `<head>` only.

## Decisions (from brainstorm)

| Question | Decision |
| --- | --- |
| Canonical host | Read from `APP_URL` — no hard-coded domain. |
| OG image source | `primary` media of Product/Article/Page with new `og` conversion (1200×630 JPG). Global fallback at `/images/og/default.jpg`. |
| JSON-LD scope | Full set: Organization + WebSite globally; Product, Article, BreadcrumbList per page. |
| Product price in JSON-LD | Locale-driven: `uk` → UAH, `en` → EUR. One Offer per page. |
| Catalog indexing | `/products` clean → index; `?sort=*` and `?series=*` → `noindex,follow` with canonical → `/products`; `?page=N` → self-canonical, index. |
| Sitemap | Dynamic `GET /sitemap.xml` route, cached 1 hour. |
| Brand data source | `config/site.php` + `.env` keys (`SEO_ORG_*`). |

## Architecture

New domain `App\Seo`, modelled on the existing `App\Forms` layout:

```
app/Seo/
├── SeoData.php                    Immutable readonly DTO
├── AlternateUrlResolver.php       Builds hreflang URLs for the 3 routing cases
├── Builders/
│   ├── PageSeoBuilder.php
│   ├── ArticleSeoBuilder.php
│   ├── ArticleIndexSeoBuilder.php
│   ├── ProductSeoBuilder.php
│   └── CatalogSeoBuilder.php
└── StructuredData/
    ├── OrganizationSchema.php     Static factory: returns array
    ├── WebSiteSchema.php
    ├── ProductSchema.php
    ├── ArticleSchema.php
    └── BreadcrumbSchema.php
```

View layer:

```
resources/views/components/site/
├── seo-meta.blade.php             Renders SeoData (title, description, canonical,
│                                  hreflang+x-default, OG, Twitter, robots, per-page JSON-LD)
└── json-ld.blade.php              Wraps an array in <script type="application/ld+json">
```

## `SeoData` DTO

```php
final readonly class SeoData
{
    public function __construct(
        public string $title,
        public ?string $description,
        public string $canonical,                  // absolute URL
        public string $ogType = 'website',         // website | article | product
        public ?string $ogImage = null,            // absolute URL
        public ?int $ogImageWidth = null,
        public ?int $ogImageHeight = null,
        public array $alternates = [],             // ['uk' => '…', 'en' => '…', 'x-default' => '…']
        public string $robots = 'index,follow',
        public array $jsonLd = [],                 // list of structured-data graphs
        public ?string $publishedTime = null,      // ISO 8601, articles only
        public ?string $modifiedTime = null,
    ) {}
}
```

Invariants for `alternates`:

- **Always** includes the current locale's URL (we just served it, so it exists).
- Includes the other locale's URL **only if** the translation exists. For Page/Article this means `$model->getTranslation('slug', $otherLocale, false)` is non-null; for Product/static routes both locales always exist.
- `x-default` equals the `uk` URL when present, otherwise it is omitted. (In practice uk is the default locale and always populated; an uk-less translation is an edge case.)

This deliberately violates a "symmetric pair" invariant: emitting a hreflang to a non-existent translation would point crawlers at a 404, which is worse than asymmetry. The sitemap follows the same rule (see Sitemap section).

`canonical` equals the alternate for the *current* locale, even when `robots = noindex,follow` — except for filtered/sorted catalog pages, where `canonical` collapses to the clean `/products` (or `/products?page=N`) URL by design.

## `AlternateUrlResolver`

```php
final class AlternateUrlResolver
{
    public function forTranslatedSlug(string $routeName, array $slugTranslations): array;
    public function forSharedSlug(string $routeName, array $routeParams): array;
    public function forStaticRoute(string $routeName, array $queryParams = []): array;
}
```

All three return `['uk' => '<abs>', 'en' => '<abs>', 'x-default' => '<abs>']`. URLs are built off `config('app.url')`, **not** via `LaravelLocalization::getLocalizedURL`, because:
1. canonical/OG/sitemap require absolute URLs;
2. for `uk` (default locale) the package omits the prefix when `hideDefaultLocaleInURL=true`, and we want a stable absolute form.

Behaviour per case:

- **forTranslatedSlug** (Page/Article): emits an entry for each locale where `$slugTranslations[$locale]` is non-null. Skips locales with missing translations. `x-default` is emitted only if the uk slug is present (default-locale URL acts as the language-neutral fallback).
- **forSharedSlug** (Product): both locales always emitted — slug is the same, only the `/en/` prefix differs. `x-default` = uk URL.
- **forStaticRoute** (`/products`, `/articles`, home): both locales always emitted, query params appended verbatim. `x-default` = uk URL.

## Builders

### Common rules

- `title`: `seo_title->{$locale}` ?: `title->{$locale}`. Append ` · ` + `config('site.seo.title_suffix')` unless already present.
- `description`: `seo_description->{$locale}` ?: derived from intro/content via `Str::limit(strip_tags(...), 160)`.
- `ogImage`: `getFirstMedia('primary')?->getUrl('og')`, normalised to absolute; fallback to `url(config('site.seo.default_og_image'))`.
- `robots`: defaults to `index,follow`.

### `PageSeoBuilder::build(Page $page, string $locale): SeoData`

- `ogType` = `website`
- `alternates`: `forTranslatedSlug('page.show', $page->getTranslations('slug'))`, or `forStaticRoute('home')` when `$page->is_homepage`.
- `jsonLd` = `[BreadcrumbSchema::generate($crumbs)]` (homepage skips breadcrumbs).

### `ArticleSeoBuilder::build(Article $article, string $locale): SeoData`

- `ogType` = `article`
- `publishedTime` = `$article->published_at?->toIso8601String()`, `modifiedTime` = `$article->updated_at->toIso8601String()`.
- `alternates`: `forTranslatedSlug('articles.show', $article->getTranslations('slug'))`.
- `jsonLd` = `[ArticleSchema::generate($article, $locale), BreadcrumbSchema::generate($crumbs)]`.

### `ArticleIndexSeoBuilder::build(string $locale, int $page = 1): SeoData`

- `title`/`description` from `lang/{locale}/site.php` (existing `articles.meta_*` keys).
- `alternates`: `forStaticRoute('articles.index', $page > 1 ? ['page' => $page] : [])`.
- `canonical` = alternates[currentLocale] (self-canonical, includes `?page=` if present).
- `jsonLd` = `[BreadcrumbSchema::generate($crumbs)]`.

### `ProductSeoBuilder::build(Product $product, string $locale): SeoData`

- `ogType` = `product`
- `alternates`: `forSharedSlug('products.show', ['product' => $product->slug])`.
- `jsonLd` = `[ProductSchema::generate($product, $locale), BreadcrumbSchema::generate($crumbs)]`.

### `CatalogSeoBuilder::build(CatalogSeoInput $input, string $locale): SeoData`

Takes a small input DTO carrying **raw URL state**, not normalized values, because indexing decisions depend on whether the user *typed* a query param — independent of whether its value was valid:

```php
final readonly class CatalogSeoInput
{
    public function __construct(
        public bool $hasSortParam,    // true if request had any `sort` query param, valid or not
        public bool $hasSeriesParam,  // true if request had any `series` query param, valid or not
        public int $page,             // 1-based; 1 means "no ?page= or ?page=1"
    ) {}
}
```

The controller builds this DTO from `$request->has('sort')`, `$request->has('series')`, `$request->integer('page', 1)` — before normalization.

Indexing matrix:

| URL shape | `canonical` | `robots` |
| --- | --- | --- |
| `/products` (no `sort`, no `series`, no `page` or `page=1`) | `/products` | `index,follow` |
| `/products?page=N` (N > 1, no `sort`, no `series`) | `/products?page=N` | `index,follow` |
| `/products?sort=*` (any value, including `pop`) | `/products` | `noindex,follow` |
| `/products?series=*` (any value, including invalid) | `/products` | `noindex,follow` |
| `/products?page=N&sort=*` and/or `&series=*` | `/products?page=N` (or `/products` if `N=1`) | `noindex,follow` |

Rule of thumb: **presence** of `sort` or `series` in the URL → `noindex,follow` + canonical strips those params (but keeps `page=N` if N > 1, so deep pagination still has a reachable canonical).

`alternates` = `forStaticRoute('products.index', $queryParams)` where `$queryParams` matches the canonical (no `sort`/`series`, includes `page=N` only if N > 1).

## JSON-LD generators

All return `array` (encoded to JSON by `<x-site.json-ld>`). Optional fields are omitted when their source value is empty — never emit `null` or empty string.

### `OrganizationSchema::generate(): array`

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "name": "<config('site.organization.name')>",
  "url": "<APP_URL>/",
  "logo": "<absolute(config('site.organization.logo'))>",
  "email": "<…>",
  "telephone": "<…>",
  "address": {"@type": "PostalAddress", "addressCountry": "UA", "addressLocality": "…", "streetAddress": "…"},
  "sameAs": ["<social url>", "…"]
}
```

### `WebSiteSchema::generate(string $locale): array`

```json
{
  "@context": "https://schema.org",
  "@type": "WebSite",
  "url": "<APP_URL>/",
  "name": "<config('site.organization.name')>",
  "inLanguage": "<uk-UA | en-GB based on $locale>"
}
```

### `ProductSchema::generate(Product, string $locale): array`

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "<localized name>",
  "description": "<localized description, stripped>",
  "image": ["<absolute og image>"],
  "sku": "<id>",
  "brand": {"@type": "Brand", "name": "<config('site.organization.name')>"},
  "category": "<perfumeFamily->name (localised)>",
  "offers": {
    "@type": "Offer",
    "url": "<canonical>",
    "priceCurrency": "UAH | EUR",
    "price": "<price_uah | price_eur as string>",
    "availability": "https://schema.org/InStock | OutOfStock",
    "itemCondition": "https://schema.org/NewCondition"
  }
}
```

`brand` is **always** the organization name (LEVANT Parfums), never `$product->inspiredBrand?->name`. The `inspiredBrand` relation models "inspired by brand X" (niche-perfumery context — e.g. a fragrance inspired by Tom Ford); using that as schema.org `Brand` would (a) misrepresent the manufacturer to crawlers and (b) carry trademark risk by attributing other brands' names to LEVANT products in structured data. The inspiration is UI-only marketing context, not a schema attribute.

Availability is `InStock` if `$product->in_stock` is truthy, else `OutOfStock`.

### `ArticleSchema::generate(Article, string $locale): array`

```json
{
  "@context": "https://schema.org",
  "@type": "Article",
  "headline": "<localized title>",
  "description": "<localized description>",
  "image": "<absolute og image>",
  "datePublished": "<ISO8601>",
  "dateModified": "<ISO8601>",
  "author": {"@type": "Organization", "name": "<config('site.organization.name')>"},
  "publisher": {"@type": "Organization", "name": "…", "logo": {"@type": "ImageObject", "url": "<absolute logo>"}},
  "mainEntityOfPage": "<canonical>",
  "inLanguage": "uk-UA | en-GB"
}
```

### `BreadcrumbSchema::generate(array $crumbs): array`

Input: `[['name' => '…', 'url' => '<abs>'], …]`. Output: standard `BreadcrumbList` with one `ListItem` per crumb, `position` 1-indexed.

## Config

Extend `config/site.php`:

```php
return [
    'themes' => [...],  // existing

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
        'same_as' => array_filter(explode(',', (string) env('SEO_ORG_SAME_AS', ''))),
    ],

    'seo' => [
        'default_og_image' => '/images/og/default.jpg',
        'title_suffix'     => 'LEVANT Parfums',
        'twitter_card'     => 'summary_large_image',
    ],
];
```

`.env.example` documents the new `SEO_ORG_*` keys without committing values.

## Media

Add an `og` conversion to `Product`, `Article`, and `Page`:

```php
$this->addMediaConversion('og')
    ->fit(Fit::Crop, 1200, 630)
    ->format('jpg')
    ->quality(82)
    ->nonQueued()
    ->performOnCollections('primary');
```

**Deviation from CLAUDE.md "all conversions in webp" rule:** OG conversion uses JPG because Telegram, Slack, and some Facebook crawlers still preview-render webp inconsistently. This deviation is scoped to the `og` conversion only; `thumb`/`card`/`detail` stay webp.

## Sitemap

Route (registered **outside** the localised group, before it):

```php
Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
```

Controller behaviour:

1. `Cache::remember('sitemap.xml', 3600, fn () => $this->build())`.
2. Build URL set:
   - home (uk + en)
   - `/products` (uk + en)
   - `/articles` (uk + en)
   - every published `Page` (per locale, using translated slug)
   - every published `Product` (one slug, two locale variants)
   - every published `Article` (per locale, translated slug)
3. For each URL emit a `<url>` element with `<loc>`, `<lastmod>` (model `updated_at` or `now()` for index pages), and sibling `<xhtml:link rel="alternate" hreflang="…"/>` elements **following the same rule as `AlternateUrlResolver`**: one per locale where the translation exists, plus `x-default` when the uk URL exists. Page/Article rows with only one translation get only the matching `xhtml:link` plus `x-default`; Product and static-route rows always get both locales + `x-default`. Never emit a hreflang pointing at a URL that would 404.
4. Returns XML with `Content-Type: application/xml; charset=UTF-8`.

Template: `resources/views/sitemap/index.blade.php` — pure XML with `@foreach`.

Not included: filtered/sorted catalog variants (they're `noindex`). Pagination beyond page 1 is omitted; crawlers reach deeper pages via the paginator.

## Robots

Delete `public/robots.txt`. Add:

```php
Route::get('/robots.txt', RobotsController::class);
```

Returns `text/plain`:

```
User-agent: *
Allow: /
Disallow: /admin
Disallow: /admin/*

Sitemap: {APP_URL}/sitemap.xml
```

No caching needed — generation is trivial. Nginx production config already has `try_files $uri $uri/ /index.php?$query_string`, so removing the static file is transparent.

`sitemap` and `feed` stay in `config('content.reserved_slugs')`. `sitemap.xml` and `robots.txt` contain a dot, which the existing route regex `[A-Za-z0-9\-_]+` already excludes from the `/{slug}` catch-all — no further protection needed.

## Layout integration

`resources/views/layouts/site.blade.php`:

```blade
@php
    $currentLocale = app()->getLocale();
    $seo = $seo ?? null;
@endphp
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
…
```

Old `@yield('title')` / `@yield('description')` are removed — single source of truth lives in `SeoData`. If a view forgets to pass `$seo`, `seo-meta` falls back to defaults from `config('site.seo')` so nothing breaks.

### `<x-site.seo-meta>` output

```html
<title>{{ $seo->title }}</title>
<meta name="description" content="{{ $seo->description }}">
<meta name="robots" content="{{ $seo->robots }}">
<link rel="canonical" href="{{ $seo->canonical }}">

@foreach($seo->alternates as $hreflang => $url)
    <link rel="alternate" hreflang="{{ $hreflang }}" href="{{ $url }}">
@endforeach

<meta property="og:type" content="{{ $seo->ogType }}">
<meta property="og:title" content="{{ $seo->title }}">
<meta property="og:description" content="{{ $seo->description }}">
<meta property="og:url" content="{{ $seo->canonical }}">
<meta property="og:site_name" content="{{ config('site.seo.title_suffix') }}">
<meta property="og:locale" content="{{ $locale === 'uk' ? 'uk_UA' : 'en_GB' }}">
<meta property="og:locale:alternate" content="{{ $locale === 'uk' ? 'en_GB' : 'uk_UA' }}">
@if($seo->ogImage)
    <meta property="og:image" content="{{ $seo->ogImage }}">
    @if($seo->ogImageWidth)<meta property="og:image:width" content="{{ $seo->ogImageWidth }}">@endif
    @if($seo->ogImageHeight)<meta property="og:image:height" content="{{ $seo->ogImageHeight }}">@endif
@endif
@if($seo->ogType === 'article' && $seo->publishedTime)
    <meta property="article:published_time" content="{{ $seo->publishedTime }}">
    <meta property="article:modified_time" content="{{ $seo->modifiedTime }}">
@endif

<meta name="twitter:card" content="{{ config('site.seo.twitter_card') }}">
<meta name="twitter:title" content="{{ $seo->title }}">
<meta name="twitter:description" content="{{ $seo->description }}">
@if($seo->ogImage)<meta name="twitter:image" content="{{ $seo->ogImage }}">@endif

@foreach($seo->jsonLd as $schema)
    <x-site.json-ld :data="$schema" />
@endforeach
```

## Controller changes

Thin — all logic lives in builders:

```php
// PageController@home / @show
$seo = app(PageSeoBuilder::class)->build($page, app()->getLocale());
return view("pages.templates.{$page->template->value}", compact('page', 'seo'));
```

`ProductCatalogController@index` builds the input DTO from the **raw** request (before normalising `$series`/`$sort`), so SEO sees the actual URL the user landed on:

```php
$catalogSeoInput = new CatalogSeoInput(
    hasSortParam:   $request->has('sort'),
    hasSeriesParam: $request->has('series'),
    page:           $request->integer('page', 1),
);
$seo = app(CatalogSeoBuilder::class)->build($catalogSeoInput, app()->getLocale());
```

`ProductCatalogController@show`, `ArticleController@index/@show` follow the simpler `PageController` pattern. Theme (`$theme`) is unaffected.

The existing `View::share('alternateSlugs', …)` in `PageController` and `ArticleController` becomes obsolete (replaced by `SeoData->alternates`), but stays for now because `lang-switch` and `mobile-menu` Blade components still read it. Removal is a separate follow-up.

## Page-view changes

Remove from these files:

- `resources/views/products/index.blade.php` — `@section('title')`, `@section('description')`
- `resources/views/products/show.blade.php` — same
- `resources/views/articles/index.blade.php` — same
- `resources/views/articles/show.blade.php` — same
- `resources/views/pages/templates/simple.blade.php` — same
- `resources/views/pages/templates/landing.blade.php` — same

No other changes to these views.

## Files affected (summary)

| Action | File |
| --- | --- |
| new | `app/Seo/SeoData.php` |
| new | `app/Seo/AlternateUrlResolver.php` |
| new | `app/Seo/Builders/{Page,Article,ArticleIndex,Product,Catalog}SeoBuilder.php` |
| new | `app/Seo/Builders/CatalogSeoInput.php` (DTO for raw catalog query state) |
| new | `app/Seo/StructuredData/{Organization,WebSite,Product,Article,Breadcrumb}Schema.php` |
| new | `app/Http/Controllers/SitemapController.php` |
| new | `app/Http/Controllers/RobotsController.php` |
| new | `resources/views/components/site/seo-meta.blade.php` |
| new | `resources/views/components/site/json-ld.blade.php` |
| new | `resources/views/sitemap/index.blade.php` |
| modify | `app/Http/Controllers/PageController.php` |
| modify | `app/Http/Controllers/ProductCatalogController.php` |
| modify | `app/Http/Controllers/ArticleController.php` |
| modify | `app/Models/Catalogue/Product.php` (add `og` conversion) |
| modify | `app/Models/Content/Article.php` (add `og` conversion) |
| modify | `app/Models/Content/Page.php` (add `og` conversion) |
| modify | `config/site.php` (organization + seo blocks) |
| modify | `.env.example` (`SEO_ORG_*` keys) |
| modify | `routes/web.php` (sitemap + robots routes, outside the localised group) |
| modify | `resources/views/layouts/site.blade.php` |
| modify | six page-view files (remove `@section('title'/'description')`) |
| delete | `public/robots.txt` |
| new | `public/images/og/default.jpg` (designer artefact — placeholder during dev) |
| new | `public/images/og/logo.png` (placeholder during dev) |

## Testing

### Unit (`tests/Unit/Seo/`)

- `SeoDataTest.php` — constructor accepts all fields; readonly verified by attempt-to-mutate assertion.
- `AlternateUrlResolverTest.php` — all three methods × two locales × edge cases. Explicitly cover: (a) Page with both translations → both locales + `x-default` emitted; (b) Page with uk-only translation → only uk + `x-default`; (c) Page with en-only translation (synthetic) → only en, no `x-default`; (d) Product → always both locales + `x-default`; (e) static route with query params → params appended to both alternates.
- `Builders/{Page,Article,ArticleIndex,Product,Catalog}SeoBuilderTest.php` — for each builder verify: title fallback chain (`seo_title` → `title` → suffix), description fallback (`seo_description` → derived), alternates contain both locales + `x-default`, canonical matches current-locale alternate (or `/products` for noindex catalog variants), robots value, `jsonLd` contains expected `@type`s.
- `StructuredData/{Organization,WebSite,Product,Article,Breadcrumb}SchemaTest.php` — structure assertions; for `ProductSchema` verify `priceCurrency` flips with locale and `availability` flips with `in_stock`; verify optional fields are omitted when empty (no `null`/empty-string output).

### Feature (`tests/Feature/Seo/`)

- `LayoutSeoTest.php` — for each public route (`/`, `/products`, `/products/{slug}`, `/articles`, `/articles/{slug}`, `/{page-slug}`) assert response contains: `<title>`, canonical, hreflang for `uk`, `en`, and `x-default`, OG core tags, Twitter card tag, exactly one Organization JSON-LD, exactly one WebSite JSON-LD.
- `CatalogIndexingTest.php` — covers the full matrix including invalid-value cases: `/products` → `index,follow`, canonical `/products`; `/products?page=2` → `index,follow`, canonical `/products?page=2`; `/products?sort=priceA` → `noindex,follow`, canonical `/products`; `/products?sort=bad` → `noindex,follow`, canonical `/products` (invalid value still counts as "param present"); `/products?sort=pop` (explicit default) → `noindex,follow`, canonical `/products`; `/products?series=onyx` → `noindex,follow`, canonical `/products`; `/products?series=bad` → `noindex,follow`, canonical `/products`; `/products?page=2&sort=priceA` → `noindex,follow`, canonical `/products?page=2`.
- `ProductSchemaTest.php` — `/products/{slug}` (uk locale) emits Product JSON-LD with `priceCurrency: UAH`; `/en/products/{slug}` emits `priceCurrency: EUR`; out-of-stock product emits `availability: OutOfStock`; `brand.name` always equals `config('site.organization.name')` even when `$product->inspired_brand_id` points to a different brand record (regression guard against accidentally leaking inspired brand into structured data).
- `SitemapTest.php` — `GET /sitemap.xml` returns 200, content-type `application/xml`, contains URLs for all seeded entities and all index pages. Asserts: (a) Product/static-route `<url>` blocks always have both-locale `xhtml:link` siblings + `x-default`; (b) a Page with only a uk slug emits only the uk `xhtml:link` + `x-default`, no `en` entry; (c) no `xhtml:link` points at a URL the controller wouldn't actually serve.
- `RobotsTest.php` — `GET /robots.txt` returns 200, content-type `text/plain`, contains `Disallow: /admin` and `Sitemap: {APP_URL}/sitemap.xml`.

Existing tests are not affected — removed `@section('title')` lines have no asserted consumers.

## Order of work

A single implementation plan can ship this in phases:

1. **Config + media** — `config/site.php`, `.env.example`, `og` conversion on three models.
2. **`SeoData` + `AlternateUrlResolver` + unit tests for both.**
3. **JSON-LD generators + unit tests.**
4. **Builders + unit tests.**
5. **Blade components (`seo-meta`, `json-ld`).**
6. **Layout integration; remove old `@section('title'/'description')` from six views.**
7. **Wire builders into four controllers.**
8. **Sitemap + Robots controllers, routes, view; delete `public/robots.txt`.**
9. **Feature tests.**
