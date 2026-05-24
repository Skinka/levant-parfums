# Articles — public list and detail pages (design spec)

**Date:** 2026-05-24
**Scope:** Storefront pages for the editorial section ("Статті / Articles"): index list and per-article detail. Includes the model changes (category, read time), routing, navigation, templates, CSS and tests required to ship them. Admin Filament resource already exists and is extended, not rewritten.

**Design source:** `docs/superpowers/design-sources/levant-parfums/project/pages-other.jsx` — components `ArticlesPage` (lines 195–229) and `ArticlePage` (lines 232–286), plus styles in `project/styles.css` `.article-card`, `.articles-grid`.

## Goals

- Public access to the existing `App\Models\Content\Article` records under `/{locale}/articles` and `/{locale}/articles/{slug}`.
- Visual match to the Levant design (italic display type, accent meta line, full-bleed cover on detail).
- Reuse existing storefront infrastructure: `layouts.site`, breadcrumbs component, product slider component, Spatie translations, MediaLibrary conversions, pagination styling.

## Non-goals

- Category filter UI on the list.
- RSS feed, sitemap entry, share buttons, comments.
- Author field on Article (decision recorded in the original content spec — kept).
- Adding `App\Models\Catalogue\Tag` to articles (tag here is a free text label per locale, not the catalogue's M2M tags).

## Data model changes

### Migration: `add_category_and_read_time_to_articles_table`

| Column | Type | Notes |
| --- | --- | --- |
| `category` | `json NULL` | Translatable display label ("Філософія" / "Philosophy"). Optional. |
| `read_time_minutes` | `unsignedSmallInteger NULL` | Editor-provided minutes; no auto-derivation from `content`. Optional. |

Both columns are nullable so existing rows do not require backfill.

### Model: `App\Models\Content\Article`

- `$fillable` += `'category'`, `'read_time_minutes'`.
- `$translatable` += `'category'`.
- New method `displayDate(): ?string` returning `$this->published_at?->translatedFormat('j F Y')`. Carbon picks the locale set by `mcamara/laravel-localization` — uk yields "12 травня 2026", en yields "12 May 2026". Returns `null` when `published_at` is null so the view can omit the meta segment.

### Filament resource

- `app/Filament/Resources/Articles/Schemas/ArticleForm.php` (or the equivalent file currently holding the form): add to the main tab a `TextInput::make('category')` and `TextInput::make('read_time_minutes')->numeric()->minValue(1)->suffix(__('content.units.minutes'))`. Field labels come from `content.fields.category` / `content.fields.read_time_minutes`.
- `app/Filament/Resources/Articles/Tables/ArticlesTable.php`: surface category as a `TextColumn` (badge-style) and read time as a small column next to date.
- Existing Translatable trait wiring already covers JSON columns — `category` works as soon as it is listed in `$translatable`.

### Factory

`database/factories/Content/ArticleFactory.php` — add to the definition:

```php
'category' => ['uk' => fake()->randomElement(['Філософія','Маніфест','Освіта','Колекції']),
                'en' => fake()->randomElement(['Philosophy','Manifesto','Education','Collections'])],
'read_time_minutes' => fake()->numberBetween(3, 8),
```

Keep the existing translatable fields untouched.

## Routing

Edit `routes/web.php`, inside the localized group, **before** the `/{slug}` catch-all:

```php
Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
Route::get('/articles/{slug}', [ArticleController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-_]+')
    ->name('articles.show');
```

`articles` is already listed in `config('content.reserved_slugs')`, so a CMS `Page` cannot collide.

## Controller

New `App\Http\Controllers\ArticleController`:

- `index()` — `Article::published()->with('media')->latest('published_at')->paginate(12)` → `view('articles.index', compact('articles'))`. No `$theme` is passed — layout falls back to `theme-cream`. `with('media')` avoids an N+1 across the paginated `.cover` `<img>` lookups (each card calls `getFirstMediaUrl('primary', 'card')`).
- `show(string $slug)` — resolves the article with `whereJsonContains("slug->{$locale}", $slug)->published()->firstOrFail()`. Loads:
  - `$products = $article->products()->with(['media', 'tags', 'series', 'perfumeFamily'])->get()` — the eager loads cover everything `<x-site.product-card>` accesses (media for `getFirstMediaUrl`, `tags` for new/best badges, `series`/`perfumeFamily` for labels);
  - `$related = Article::published()->with('media')->where('id', '!=', $article->id)->latest('published_at')->take(3)->get()`.
  - Returns `view('articles.show', compact('article', 'products', 'related'))`.

The controller does not pass `$theme`; articles always render under the default theme.

## Views

### `resources/views/articles/index.blade.php`

Extends `layouts.site`. Sections:

1. `@section('title', __('site.articles.meta_title'))` and `@section('description', __('site.articles.meta_description'))`.
2. `<section style="padding: 32px 0 120px">` + `.container`.
3. `<x-site.breadcrumbs :items="[['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')], ['label' => __('site.nav.articles')]]" />`.
4. `.section-head` containing:
   - `.eyebrow` rendering `__('site.articles.eyebrow', ['year' => now()->year])`;
   - `<h1>` (italic, via existing typography) with `__('site.articles.title')`;
   - `.lead` paragraph with `__('site.articles.subtitle')`.
5. `.articles-grid` populated by `@foreach($articles as $article)` rendering an `<a class="article-card" href="{{ route('articles.show', $article->getTranslation('slug', app()->getLocale())) }}">` containing:
   - `.cover` → `<img src="{{ $article->getFirstMediaUrl('primary', 'card') }}" alt="" loading="lazy" width="1200" height="630">`;
   - `.meta` with three `@if`-gated spans: `.tag` for `category`, plain span for `displayDate()`, span for `"{{ $article->read_time_minutes }} {{ __('site.articles.read_min') }}"` when read time is set;
   - `<h3>{{ $article->title }}</h3>`;
   - `<p>{{ $article->intro }}</p>` (gated on non-empty);
   - `<span class="lnk">{{ __('site.articles.read_more') }} →</span>`.
6. `{{ $articles->onEachSide(1)->links('vendor.pagination.site') }}` — uses the project's existing custom paginator view at `resources/views/vendor/pagination/site.blade.php`, which the catalog already uses (`resources/views/products/index.blade.php:79`) and which `resources/css/site/components/pagination.css` is styled for.

### `resources/views/articles/show.blade.php`

Extends `layouts.site`. Sections:

1. `@section('title', $article->seo_title ?: $article->title)` and `@section('description', $article->seo_description ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))`.
2. `<article style="padding: 32px 0 80px">` + `.container`.
3. Breadcrumbs: Home → Статті (link) → `{{ $article->title }}` (current).
4. `.article-head` block:
   - `.meta` with `.tag` (category), `displayDate()`, read time;
   - `<h1 class="article-title">{{ $article->title }}</h1>`;
   - `.lead`: `{{ $article->intro }}`.
5. `.article-cover`: `<img src="{{ $article->getFirstMediaUrl('primary', 'detail') }}" alt="{{ $article->title }}">`.
6. `.article-body`: `{!! \Illuminate\Support\Str::markdown($article->content ?? '') !!}`. `Article->content` is produced by Filament's `MarkdownEditor` (see `app/Filament/Resources/Articles/Schemas/ArticleForm.php`), and the existing CMS pages render it the same way (`resources/views/pages/templates/simple.blade.php:13`). Same trust boundary — editor input is trusted, no extra escaping. `Str::markdown` emits the heading/list/blockquote/link HTML targeted by the `.article-body` selectors in section 4.
7. Optional products section (`@if($products->isNotEmpty())`): `<x-site.product-slider :products="$products" :eyebrow="__('site.articles.in_article_products')" :title="$article->title" :cta-label="null" :cta-url="null" />`. **Requires extending `resources/views/components/site/product-slider.blade.php`** to accept `eyebrow`, `title`, `ctaLabel`, `ctaUrl` props with defaults equal to the current catalogue copy (`catalogue.public.product.related.{eyebrow,title,all_label}` and `route('products.index')`). When `ctaLabel`/`ctaUrl` are `null`, suppress the link. This keeps the existing product page call site (no props) working unchanged while letting the article page override copy and drop the "all products" CTA that does not belong on an article.
8. Optional related section (`@if($related->isNotEmpty())`): `<section class="related-articles">` with `.eyebrow` + `<h2>{{ __('site.articles.related_title') }}</h2>` + a `.articles-grid.articles-grid--3` rendering three cards in the same shape as the index card.

## Styles

New file: `resources/css/site/pages/articles.css`. Imported by adding `@import './pages/articles.css';` to `resources/css/site/index.css`.

Contents:

```css
/* Card — shared by index and "Read also" */
.article-card { display: flex; flex-direction: column; gap: 16px; text-decoration: none; color: inherit; }
.article-card .cover { aspect-ratio: 16/10; overflow: hidden; background: var(--bg-2); }
.article-card .cover img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.9s var(--ease-out); }
.article-card:hover .cover img { transform: scale(1.04); }
.article-card .meta { font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--ink-mute); display: flex; gap: 16px; flex-wrap: wrap; }
.article-card .meta .tag { color: var(--accent); }
.article-card h3 { font-family: var(--font-serif); font-size: 28px; line-height: 1.25; font-weight: 400; margin-top: 4px; transition: color var(--t-fast) var(--ease-out); }
.article-card:hover h3 { color: var(--accent); }
.article-card p { color: var(--ink-soft); font-size: 14px; line-height: 1.6; }
.article-card .lnk { margin-top: 12px; width: max-content; }

/* Grid — 2-up default, 3-up override for related */
.articles-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 48px 24px; margin-top: 80px; }
.articles-grid--3 { grid-template-columns: repeat(3, 1fr); }
.articles-grid--3 .article-card .cover { aspect-ratio: 4/3; }
.articles-grid--3 .article-card h3 { font-size: 22px; }
@media (max-width: 900px) { .articles-grid, .articles-grid--3 { grid-template-columns: 1fr; gap: 40px; } }

/* Detail page */
.article-head { max-width: 780px; margin: 60px auto 0; }
.article-head .meta { display: flex; gap: 18px; font-size: 11px; letter-spacing: 0.18em; text-transform: uppercase; color: var(--ink-mute); flex-wrap: wrap; }
.article-head .meta .tag { color: var(--accent); }
.article-head .article-title { margin-top: 28px; font-style: italic; font-size: clamp(40px, 5vw, 80px); line-height: 1.05; }
.article-head .lead { margin-top: 28px; }

.article-cover { max-width: 1200px; margin: 60px auto 0; aspect-ratio: 16/9; overflow: hidden; }
.article-cover img { width: 100%; height: 100%; object-fit: cover; }

.article-body { max-width: 720px; margin: 60px auto 0; color: var(--ink-soft); font-size: 18px; line-height: 1.8; }
.article-body > * + * { margin-top: 20px; }
.article-body > p:first-child { font-family: var(--font-serif); font-size: 28px; line-height: 1.4; color: var(--ink); font-style: italic; }
.article-body h2 { margin-top: 56px; font-family: var(--font-serif); font-size: 36px; font-style: italic; color: var(--ink); line-height: 1.2; }
.article-body h3 { margin-top: 40px; font-family: var(--font-serif); font-size: 26px; color: var(--ink); }
.article-body a { color: var(--accent); text-decoration: underline; text-underline-offset: 4px; }
.article-body blockquote { border-left: 2px solid var(--accent); padding-left: 24px; font-style: italic; font-family: var(--font-serif); font-size: 22px; color: var(--ink); }
.article-body ul, .article-body ol { padding-left: 24px; }
.article-body li + li { margin-top: 8px; }
.article-body img { width: 100%; height: auto; margin: 32px 0; }

.related-articles { margin-top: 120px; padding-top: 80px; border-top: 1px solid var(--line); }
.related-articles .articles-grid { margin-top: 48px; }
```

## Navigation

`resources/views/components/site/header.blade.php`:
- Extend `$nav` array with `['key' => 'articles', 'url' => route('articles.index'), 'match' => fn ($r) => str_starts_with($r, '/articles')]` after the `catalog` item.

`resources/views/components/site/footer.blade.php`:
- Add `<li><a href="{{ route('articles.index') }}">{{ __('site.nav.articles') }}</a></li>` under the "Навігація" column.

## i18n

`lang/uk/site.php` (mirror keys in `lang/en/site.php` with English copy):

- `nav.articles` — "Статті" / "Articles"
- `articles.meta_title` — "Статті — LEVANT Parfums" / "Articles — LEVANT Parfums"
- `articles.meta_description` — short SEO blurb in each locale
- `articles.eyebrow` — "Статті · :year" / "Articles · :year"
- `articles.title` — "Статті" / "Articles"
- `articles.subtitle` — "Гіди, інтерв'ю та редакторські замітки про парфумерний світ" / "Guides, interviews and editorial notes from the world of perfumery"
- `articles.read_min` — "хв" / "min"
- `articles.read_more` — "Читати далі" / "Read more"
- `articles.related_title` — "Читайте також" / "Read also"
- `articles.in_article_products` — "Аромати у статті" / "Scents in this story"
- `footer.links.articles` — "Статті" / "Articles" (if footer uses a separate key; otherwise reuse `nav.articles`)

`lang/uk/content.php` and `lang/en/content.php`:

- `fields.category` — "Категорія" / "Category"
- `fields.read_time_minutes` — "Час читання (хв)" / "Read time (min)"
- `units.minutes` — "хв" / "min" (used as Filament suffix)

## Testing

All tests run under SQLite in-memory (project default). `published_at` filtering uses Carbon comparisons, which work in both MySQL and SQLite — no DB-specific branches required.

### `tests/Feature/Content/ArticleListPageTest.php`

- `it shows published articles`: factory creates 3 published + 1 draft (`is_published = false`) + 1 scheduled (`published_at` in future). GET `/uk/articles` returns 200 and contains exactly the 3 published titles.
- `it paginates at 12`: factory creates 15 published. Page 1 shows 12 cards, page 2 shows 3. Assert pagination link to `?page=2` is rendered.
- `it renders the localized title and read-more label`: locale `uk` → "Статті", locale `en` → "Articles".

### `tests/Feature/Content/ArticleShowPageTest.php`

- `it shows the article by uk slug`: factory creates a published article with uk slug `tri-tochki`. `GET /uk/articles/tri-tochki` → 200; response contains `title`, `intro`, formatted date, `category`, and any product attached via `article_product`.
- `it 404s on en slug under uk locale`: same record has distinct slugs per locale; requesting one locale's slug under the other locale's prefix → 404.
- `it 404s on unpublished or scheduled`: `is_published = false` or future `published_at` → 404.
- `it shows related articles`: create 4 published. Show one of them — response renders the other 3 in `.related-articles`.
- `it omits the products section when no products are attached`: assert the eyebrow `Аромати у статті` does not appear.

### Existing `tests/Feature/Content/Filament/ArticleResourceTest.php`

Extend to assert that `category` and `read_time_minutes` round-trip through the create/edit forms (both languages for `category`).

## Files touched

Created:
- `database/migrations/2026_05_24_000000_add_category_and_read_time_to_articles_table.php`
- `app/Http/Controllers/ArticleController.php`
- `resources/views/articles/index.blade.php`
- `resources/views/articles/show.blade.php`
- `resources/css/site/pages/articles.css`
- `tests/Feature/Content/ArticleListPageTest.php`
- `tests/Feature/Content/ArticleShowPageTest.php`
- Possibly `resources/views/vendor/pagination/site.blade.php` (decided in plan)

Edited:
- `app/Models/Content/Article.php`
- `app/Filament/Resources/Articles/Schemas/ArticleForm.php` (extend `mainTab()` with `category` + `read_time_minutes` fields)
- `app/Filament/Resources/Articles/Tables/ArticlesTable.php` (surface category and read time)
- `database/factories/Content/ArticleFactory.php`
- `routes/web.php`
- `resources/css/site/index.css`
- `resources/views/components/site/header.blade.php`
- `resources/views/components/site/footer.blade.php`
- `resources/views/components/site/product-slider.blade.php` (add optional `eyebrow`, `title`, `ctaLabel`, `ctaUrl` props with defaults matching today's catalogue copy)
- `lang/uk/site.php`, `lang/en/site.php`
- `lang/uk/content.php`, `lang/en/content.php`
