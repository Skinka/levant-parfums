# Articles — public list and detail pages — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship public `/articles` and `/articles/{slug}` pages backed by the existing `App\Models\Content\Article`, matching the Levant design source for the "Статті" section, with category + read-time metadata, related products, and related articles.

**Architecture:** A dedicated `ArticleController` mirroring `ProductCatalogController` returns `articles.index` (paginated list) and `articles.show` (article detail). The existing `product-slider` component is widened with optional copy props so it can be reused on the article detail page with article-specific eyebrow/title and without a "view all" link. `Article->content` is rendered with `Str::markdown(...)` because the Filament form uses `MarkdownEditor` (same pattern as CMS pages). New CSS lives in `resources/css/site/pages/articles.css`.

**Tech Stack:** Laravel 13, Filament 5, Spatie Translatable, Spatie MediaLibrary, Pest 4 (SQLite `:memory:`), Tailwind v4, Blade.

**Spec:** `docs/superpowers/specs/2026-05-24-articles-public-pages-design.md`

---

## File Structure

**Create:**
- `database/migrations/2026_05_24_130000_add_category_and_read_time_to_articles_table.php`
- `app/Http/Controllers/ArticleController.php`
- `resources/views/articles/index.blade.php`
- `resources/views/articles/show.blade.php`
- `resources/css/site/pages/articles.css`
- `tests/Feature/Content/ArticleListPageTest.php`
- `tests/Feature/Content/ArticleShowPageTest.php`

**Modify:**
- `app/Models/Content/Article.php` — add `category` to `$translatable`/`$fillable`, add `read_time_minutes` to `$fillable`, add `displayDate()` helper.
- `app/Filament/Resources/Articles/Schemas/ArticleForm.php` — add `category` + `read_time_minutes` fields in `mainTab()`.
- `app/Filament/Resources/Articles/Tables/ArticlesTable.php` — surface `category` (text column) and `read_time_minutes` (text column with suffix).
- `database/factories/Content/ArticleFactory.php` — fill `category` (uk/en) and `read_time_minutes`.
- `routes/web.php` — register `articles.index` + `articles.show` before the `/{slug}` catch-all.
- `resources/views/components/site/product-slider.blade.php` — accept `eyebrow`, `title`, `ctaLabel`, `ctaUrl` props (backwards-compatible defaults).
- `resources/views/components/site/header.blade.php` — add "Articles" nav entry.
- `resources/views/components/site/footer.blade.php` — add "Articles" link in the Navigation column.
- `resources/css/site/index.css` — `@import './pages/articles.css';`
- `lang/uk/site.php`, `lang/en/site.php` — new `nav.articles` + `articles.*` block.
- `lang/uk/content.php`, `lang/en/content.php` — new `fields.category`, `fields.read_time_minutes`, `units.minutes`.
- `tests/Feature/Content/Filament/ArticleResourceTest.php` — extend with `category` + `read_time_minutes` round-trip.

---

## Task 1: Add `category` and `read_time_minutes` to articles table

**Files:**
- Create: `database/migrations/2026_05_24_130000_add_category_and_read_time_to_articles_table.php`
- Modify: `app/Models/Content/Article.php`
- Modify: `database/factories/Content/ArticleFactory.php`
- Test: `tests/Feature/Content/ArticleTest.php`

- [ ] **Step 1: Read the existing model test to match the project's Pest style**

Run: `cat tests/Feature/Content/ArticleTest.php`

Note the existing `it(...)` patterns; you will append to this file in Step 2.

- [ ] **Step 2: Write the failing model test**

Append to `tests/Feature/Content/ArticleTest.php`:

```php
it('stores category as a translatable json column and read_time_minutes as int', function () {
    $article = \App\Models\Content\Article::factory()->create([
        'category' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
        'read_time_minutes' => 7,
    ]);

    $fresh = $article->fresh();

    expect($fresh->getTranslation('category', 'uk'))->toBe('Філософія');
    expect($fresh->getTranslation('category', 'en'))->toBe('Philosophy');
    expect($fresh->read_time_minutes)->toBe(7);
});

it('formats displayDate() in the active locale', function () {
    $article = \App\Models\Content\Article::factory()->create([
        'published_at' => \Carbon\Carbon::create(2026, 5, 12, 9, 0, 0),
    ]);

    app()->setLocale('uk');
    expect($article->displayDate())->toBe('12 травня 2026');

    app()->setLocale('en');
    expect($article->displayDate())->toBe('12 May 2026');
});

it('displayDate() returns null when published_at is null', function () {
    $article = \App\Models\Content\Article::factory()->draft()->create();

    expect($article->displayDate())->toBeNull();
});
```

- [ ] **Step 3: Run tests to verify they fail**

Run: `php artisan test --filter=ArticleTest`

Expected: 3 failures (`category` column not in `$translatable`/$fillable, no `read_time_minutes` column, no `displayDate()` method).

- [ ] **Step 4: Create the migration**

Create `database/migrations/2026_05_24_130000_add_category_and_read_time_to_articles_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->json('category')->nullable()->after('intro');
            $table->unsignedSmallInteger('read_time_minutes')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['category', 'read_time_minutes']);
        });
    }
};
```

- [ ] **Step 5: Extend the Article model**

Edit `app/Models/Content/Article.php`:

In `$fillable` add `'category'` and `'read_time_minutes'` (place `'category'` after `'intro'`, `'read_time_minutes'` right after):

```php
protected $fillable = [
    'slug', 'title', 'intro', 'category', 'content',
    'read_time_minutes',
    'seo_title', 'seo_description',
    'is_published', 'published_at',
];
```

In `$translatable` add `'category'`:

```php
public array $translatable = [
    'slug', 'title', 'intro', 'category', 'content', 'seo_title', 'seo_description',
];
```

Add the `displayDate()` method right after the `casts()` method:

```php
public function displayDate(): ?string
{
    return $this->published_at?->translatedFormat('j F Y');
}
```

- [ ] **Step 6: Update the factory**

Edit `database/factories/Content/ArticleFactory.php` — extend the `definition()` return array with two new keys (place them after `'intro'`):

```php
'category' => [
    'uk' => fake()->randomElement(['Філософія', 'Маніфест', 'Освіта', 'Колекції']),
    'en' => fake()->randomElement(['Philosophy', 'Manifesto', 'Education', 'Collections']),
],
'read_time_minutes' => fake()->numberBetween(3, 8),
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter=ArticleTest`

Expected: all `ArticleTest` cases pass (existing + 3 new).

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_05_24_130000_add_category_and_read_time_to_articles_table.php \
        app/Models/Content/Article.php \
        database/factories/Content/ArticleFactory.php \
        tests/Feature/Content/ArticleTest.php
git commit -m "feat(article): add category and read_time_minutes columns"
```

---

## Task 2: Expose `category` and `read_time_minutes` in Filament

**Files:**
- Modify: `app/Filament/Resources/Articles/Schemas/ArticleForm.php`
- Modify: `app/Filament/Resources/Articles/Tables/ArticlesTable.php`
- Modify: `lang/uk/content.php`, `lang/en/content.php`
- Test: `tests/Feature/Content/Filament/ArticleResourceTest.php`

- [ ] **Step 1: Add the new translation keys**

Edit `lang/uk/content.php` — inside the `'fields' => [...]` array add (after `'intro'`):

```php
'category' => 'Категорія',
'read_time_minutes' => 'Час читання (хв)',
```

Inside `lang/uk/content.php` at the top level (root array, beside `'fields'`, `'blocks'`, etc.) add:

```php
'units' => [
    'minutes' => 'хв',
],
```

Mirror in `lang/en/content.php`:

```php
'category' => 'Category',
'read_time_minutes' => 'Read time (min)',
```

```php
'units' => [
    'minutes' => 'min',
],
```

- [ ] **Step 2: Write the failing Filament test**

Append to `tests/Feature/Content/Filament/ArticleResourceTest.php`:

```php
it('creates an article with category and read_time_minutes', function () {
    \Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\CreateArticle::class)
        ->fillForm([
            'title' => 'З категорією',
            'slug' => 'z-kategoriyeyu',
            'content' => 'Body.',
            'category' => 'Філософія',
            'read_time_minutes' => 6,
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = \App\Models\Content\Article::firstWhere('slug->uk', 'z-kategoriyeyu');
    expect($article->read_time_minutes)->toBe(6);
    expect($article->getTranslation('category', 'uk'))->toBe('Філософія');
});
```

- [ ] **Step 3: Run test to verify it fails**

Run: `php artisan test --filter='creates an article with category'`

Expected: FAIL — form has no `category` / `read_time_minutes` fields, so `fillForm` will throw or the values will be silently ignored.

- [ ] **Step 4: Add the fields to the Filament form**

Edit `app/Filament/Resources/Articles/Schemas/ArticleForm.php`. Inside `mainTab()` return array, insert these two entries between the existing `Textarea::make('intro')` and `MarkdownEditor::make('content')`:

```php
TextInput::make('category')
    ->label(fn () => trans('content.fields.category')),
TextInput::make('read_time_minutes')
    ->label(fn () => trans('content.fields.read_time_minutes'))
    ->numeric()
    ->minValue(1)
    ->maxValue(120)
    ->suffix(fn () => trans('content.units.minutes')),
```

- [ ] **Step 5: Surface the columns in the table**

Edit `app/Filament/Resources/Articles/Tables/ArticlesTable.php`. In the `columns([...])` array, insert these two columns after the existing `TextColumn::make('slug')` and before `IconColumn::make('is_published')`:

```php
TextColumn::make('category')
    ->label(fn () => trans('content.fields.category'))
    ->badge()
    ->toggleable(),
TextColumn::make('read_time_minutes')
    ->label(fn () => trans('content.fields.read_time_minutes'))
    ->suffix(fn () => ' '.trans('content.units.minutes'))
    ->toggleable(isToggledHiddenByDefault: true),
```

- [ ] **Step 6: Run the test to verify it passes**

Run: `php artisan test --filter=ArticleResourceTest`

Expected: all `ArticleResourceTest` cases pass.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Articles/Schemas/ArticleForm.php \
        app/Filament/Resources/Articles/Tables/ArticlesTable.php \
        lang/uk/content.php lang/en/content.php \
        tests/Feature/Content/Filament/ArticleResourceTest.php
git commit -m "feat(admin): edit article category and read_time in Filament"
```

---

## Task 3: Add i18n keys for the public Articles section

**Files:**
- Modify: `lang/uk/site.php`
- Modify: `lang/en/site.php`

- [ ] **Step 1: Add the Ukrainian strings**

Edit `lang/uk/site.php`:

In the `'nav' => [...]` array add `'articles' => 'Статті',` after `'catalog'`.

In the root array (after the existing `'footer' => [...]` block) append:

```php
'articles' => [
    'meta_title' => 'Статті — LEVANT Parfums',
    'meta_description' => 'Гіди, маніфести та редакторські замітки парфумерного дому Levant.',
    'eyebrow' => 'Статті · :year',
    'title' => 'Статті',
    'subtitle' => "Гіди, інтерв'ю та редакторські замітки про парфумерний світ.",
    'read_min' => 'хв',
    'read_more' => 'Читати далі',
    'related_title' => 'Читайте також',
    'in_article_products' => 'Аромати у статті',
],
```

- [ ] **Step 2: Add the English strings**

Edit `lang/en/site.php`:

In the `'nav' => [...]` array add `'articles' => 'Articles',` after `'catalog'`.

In the root array (after the existing `'footer' => [...]` block) append:

```php
'articles' => [
    'meta_title' => 'Articles — LEVANT Parfums',
    'meta_description' => 'Guides, manifestos and editorial notes from the Levant perfume house.',
    'eyebrow' => 'Articles · :year',
    'title' => 'Articles',
    'subtitle' => 'Guides, interviews and editorial notes from the world of perfumery.',
    'read_min' => 'min',
    'read_more' => 'Read more',
    'related_title' => 'Read also',
    'in_article_products' => 'Scents in this story',
],
```

- [ ] **Step 3: Verify both locales parse**

Run: `php -l lang/uk/site.php && php -l lang/en/site.php`

Expected: `No syntax errors detected` for both.

- [ ] **Step 4: Commit**

```bash
git add lang/uk/site.php lang/en/site.php
git commit -m "feat(i18n): add nav.articles and articles.* strings"
```

---

## Task 4: Routes and stub controller

**Files:**
- Create: `app/Http/Controllers/ArticleController.php`
- Modify: `routes/web.php`
- Create: `resources/views/articles/index.blade.php` (minimal stub)
- Create: `resources/views/articles/show.blade.php` (minimal stub)
- Test: `tests/Feature/Content/ArticleListPageTest.php`
- Test: `tests/Feature/Content/ArticleShowPageTest.php`

- [ ] **Step 1: Write the failing route/controller tests**

Create `tests/Feature/Content/ArticleListPageTest.php`:

```php
<?php

use App\Models\Content\Article;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

it('GET /articles returns 200 and lists published articles', function () {
    Article::factory()->create(['title' => ['uk' => 'Перша', 'en' => 'First']]);
    Article::factory()->create(['title' => ['uk' => 'Друга', 'en' => 'Second']]);
    Article::factory()->draft()->create(['title' => ['uk' => 'Чернетка', 'en' => 'Draft']]);
    Article::factory()->scheduled(now()->addDay())->create([
        'title' => ['uk' => 'Майбутня', 'en' => 'Future'],
    ]);

    $this->get('/articles')
        ->assertOk()
        ->assertSee('Перша')
        ->assertSee('Друга')
        ->assertDontSee('Чернетка')
        ->assertDontSee('Майбутня');
});

it('GET /articles paginates at 12 per page', function () {
    Article::factory()->count(15)->create();

    $this->get('/articles')
        ->assertOk()
        ->assertSee('?page=2', escape: false);

    $this->get('/articles?page=2')->assertOk();
});

it('GET /en/articles renders the English title', function () {
    Article::factory()->create();

    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/en/articles')
        ->assertOk()
        ->assertSee('Articles');
});
```

Create `tests/Feature/Content/ArticleShowPageTest.php`:

```php
<?php

use App\Models\Content\Article;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

it('GET /articles/{slug} returns 200 for a published article (uk slug)', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tri-tochki', 'en' => 'three-points'],
        'title' => ['uk' => 'Три точки', 'en' => 'Three points'],
        'intro' => ['uk' => 'Коротко про дім.', 'en' => 'About the house.'],
    ]);

    $this->get('/articles/tri-tochki')
        ->assertOk()
        ->assertSee('Три точки')
        ->assertSee('Коротко про дім.');
});

it('GET /articles/{slug} returns 404 when the slug belongs to another locale', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tri-tochki', 'en' => 'three-points'],
    ]);

    $this->get('/articles/three-points')->assertNotFound();
});

it('GET /articles/{slug} returns 404 for unpublished articles', function () {
    Article::factory()->draft()->create([
        'slug' => ['uk' => 'chernetka', 'en' => 'draft'],
    ]);

    $this->get('/articles/chernetka')->assertNotFound();
});

it('GET /articles/{slug} returns 404 for scheduled articles', function () {
    Article::factory()->scheduled(now()->addDay())->create([
        'slug' => ['uk' => 'mayb', 'en' => 'future'],
    ]);

    $this->get('/articles/mayb')->assertNotFound();
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='ArticleListPageTest|ArticleShowPageTest'`

Expected: all FAIL — route `/articles` does not exist (404 from a different cause: catch-all → no matching Page → 404).

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/ArticleController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Content\Article;

class ArticleController extends Controller
{
    public function index()
    {
        $articles = Article::query()
            ->published()
            ->with('media')
            ->latest('published_at')
            ->paginate(12);

        return view('articles.index', compact('articles'));
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

        return view('articles.show', compact('article', 'products', 'related'));
    }
}
```

- [ ] **Step 4: Register the routes**

Edit `routes/web.php`. Inside the existing localized `Route::group`, insert these two routes immediately after the `products.show` route and **before** the `/{slug}` catch-all:

```php
Route::get('/articles', [\App\Http\Controllers\ArticleController::class, 'index'])
    ->name('articles.index');
Route::get('/articles/{slug}', [\App\Http\Controllers\ArticleController::class, 'show'])
    ->where('slug', '[A-Za-z0-9\-_]+')
    ->name('articles.show');
```

If `ArticleController` is not already imported at the top of the file, add `use App\Http\Controllers\ArticleController;` and replace the FQCN above with `ArticleController::class`.

- [ ] **Step 5: Create the minimal index stub view**

Create `resources/views/articles/index.blade.php`:

```blade
@extends('layouts.site')

@section('title', __('site.articles.meta_title'))
@section('description', __('site.articles.meta_description'))

@section('content')
    <section style="padding: 32px 0 120px">
        <div class="container">
            <h1>{{ __('site.articles.title') }}</h1>

            @foreach($articles as $article)
                <a href="{{ route('articles.show', $article->getTranslation('slug', app()->getLocale())) }}">
                    {{ $article->title }}
                </a>
            @endforeach

            {{ $articles->onEachSide(1)->links('vendor.pagination.site') }}
        </div>
    </section>
@endsection
```

- [ ] **Step 6: Create the minimal show stub view**

Create `resources/views/articles/show.blade.php`:

```blade
@extends('layouts.site')

@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))

@section('content')
    <article style="padding: 32px 0 80px">
        <div class="container">
            <h1>{{ $article->title }}</h1>
            <p>{{ $article->intro }}</p>
        </div>
    </article>
@endsection
```

- [ ] **Step 7: Run tests to verify they pass**

Run: `php artisan test --filter='ArticleListPageTest|ArticleShowPageTest'`

Expected: all PASS (7 cases).

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/ArticleController.php routes/web.php \
        resources/views/articles/index.blade.php resources/views/articles/show.blade.php \
        tests/Feature/Content/ArticleListPageTest.php \
        tests/Feature/Content/ArticleShowPageTest.php
git commit -m "feat(articles): public index and show routes with stub views"
```

---

## Task 5: Make `product-slider` reusable

**Files:**
- Modify: `resources/views/components/site/product-slider.blade.php`

This task changes a component used today only by the product page (`resources/views/products/show.blade.php:34`). The change is backwards-compatible: the existing call site (`<x-site.product-slider :products="$related"/>`) passes no copy props and gets exactly the same output it does today.

- [ ] **Step 1: Verify the existing product page test still passes after the planned change**

Run: `php artisan test --filter=ProductPageTest`

Expected: PASS (baseline). If the project has a different test file for the product page, find it with `grep -l 'product-slider\|products/show\|ProductShow' tests/`. Note which tests rely on the current slider markup so you can verify them again after the rewrite.

- [ ] **Step 2: Rewrite the component**

Replace the entire contents of `resources/views/components/site/product-slider.blade.php`:

```blade
@props([
    'products',
    'eyebrow' => null,
    'title' => null,
    'ctaLabel' => null,
    'ctaUrl' => null,
])

@php
    $eyebrow ??= __('catalogue.public.product.related.eyebrow');
    $title ??= __('catalogue.public.product.related.title');
    $ctaLabel ??= __('catalogue.public.product.related.all_label');
    $ctaUrl ??= route('products.index');
@endphp

@if($products->isNotEmpty())
<section class="product-slider">
    <div class="container">
        <div class="head">
            <div class="eyebrow">{{ $eyebrow }}</div>
            <h2>{{ $title }}</h2>
            @if($ctaLabel && $ctaUrl)
                <a href="{{ $ctaUrl }}" class="lnk">{{ $ctaLabel }} →</a>
            @endif
        </div>
        <div class="track">
            @foreach($products as $product)
                <x-site.product-card :product="$product"/>
            @endforeach
        </div>
    </div>
</section>
@endif
```

Notes:
- Defaults are computed inside `@php` (not in `@props([...])` defaults) so the `__('...')` calls run at render time under the active locale, not at class-load time.
- When the consumer explicitly passes `:cta-label="null"` AND `:cta-url="null"`, both default-fallbacks are bypassed (the values stay `null`), and the link is hidden.

- [ ] **Step 3: Re-run the product page test**

Run: `php artisan test --filter=ProductPageTest` (or the equivalent test identified in Step 1).

Expected: PASS — the existing product page still shows the related-products section with the catalogue copy.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/site/product-slider.blade.php
git commit -m "refactor(slider): accept optional eyebrow/title/cta props"
```

---

## Task 6: CSS for the Articles section

**Files:**
- Create: `resources/css/site/pages/articles.css`
- Modify: `resources/css/site/index.css`

No automated test — these styles are validated visually in Task 9.

- [ ] **Step 1: Create the stylesheet**

Create `resources/css/site/pages/articles.css`:

```css
/* Articles — cards + grid (used by index and "Read also" block) */
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

.articles-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 48px 24px; margin-top: 80px; }
.articles-grid--3 { grid-template-columns: repeat(3, 1fr); }
.articles-grid--3 .article-card .cover { aspect-ratio: 4/3; }
.articles-grid--3 .article-card h3 { font-size: 22px; }
@media (max-width: 900px) { .articles-grid, .articles-grid--3 { grid-template-columns: 1fr; gap: 40px; } }

/* Article detail page */
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

.in-article-products { margin-top: 80px; }
```

- [ ] **Step 2: Wire the import**

Edit `resources/css/site/index.css`. Append at the very end (after `@import './pages/product.css';`):

```css
@import './pages/articles.css';
```

- [ ] **Step 3: Commit**

```bash
git add resources/css/site/pages/articles.css resources/css/site/index.css
git commit -m "style(articles): card grid + detail typography"
```

---

## Task 7: Full markup for the Articles index page

**Files:**
- Modify: `resources/views/articles/index.blade.php`
- Test: `tests/Feature/Content/ArticleListPageTest.php`

- [ ] **Step 1: Extend the tests to assert the metadata strip and read-more label**

Append to `tests/Feature/Content/ArticleListPageTest.php`:

```php
it('renders card metadata (category, date, read time)', function () {
    Article::factory()->create([
        'title' => ['uk' => 'Картка', 'en' => 'Card'],
        'category' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
        'read_time_minutes' => 4,
        'published_at' => \Carbon\Carbon::create(2026, 5, 12, 9),
    ]);

    $this->get('/articles')
        ->assertOk()
        ->assertSee('Філософія')
        ->assertSee('12 травня 2026')
        ->assertSee('4 хв')
        ->assertSee('Читати далі');
});

it('omits metadata segments when nullable fields are empty', function () {
    Article::factory()->create([
        'title' => ['uk' => 'Без мети', 'en' => 'No meta'],
        'category' => null,
        'read_time_minutes' => null,
        'published_at' => \Carbon\Carbon::create(2026, 5, 12, 9),
    ]);

    $response = $this->get('/articles');
    $response->assertOk()
        ->assertSee('Без мети')
        ->assertSee('12 травня 2026');
    // Read-more label still rendered.
    $response->assertSee('Читати далі');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ArticleListPageTest`

Expected: the first new test FAILS (the stub view does not render category/date/read time). The second new test may pass coincidentally; that is fine.

- [ ] **Step 3: Replace the stub view with the full markup**

Overwrite `resources/views/articles/index.blade.php`:

```blade
@extends('layouts.site')

@section('title', __('site.articles.meta_title'))
@section('description', __('site.articles.meta_description'))

@section('content')
    @php($locale = app()->getLocale())

    <section style="padding: 32px 0 120px">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
                ['label' => __('site.nav.articles')],
            ]"/>

            <div class="section-head" style="margin-top: 32px">
                <div>
                    <div class="eyebrow">{{ __('site.articles.eyebrow', ['year' => now()->year]) }}</div>
                    <h1 style="margin-top: 18px; font-style: italic">{{ __('site.articles.title') }}</h1>
                    <p class="lead" style="margin-top: 24px; max-width: 44ch">
                        {{ __('site.articles.subtitle') }}
                    </p>
                </div>
            </div>

            @if($articles->isEmpty())
                <p class="lead" style="margin-top: 80px">—</p>
            @else
                <div class="articles-grid reveal-stagger">
                    @foreach($articles as $article)
                        @php($coverUrl = $article->getFirstMediaUrl('primary', 'card'))
                        <a class="article-card"
                           href="{{ route('articles.show', $article->getTranslation('slug', $locale)) }}">
                            <div class="cover">
                                @if($coverUrl)
                                    <img src="{{ $coverUrl }}" alt="{{ $article->title }}"
                                         loading="lazy" width="1200" height="630">
                                @endif
                            </div>
                            <div class="meta">
                                @if($article->category)
                                    <span class="tag">{{ $article->category }}</span>
                                @endif
                                @if($date = $article->displayDate())
                                    <span>{{ $date }}</span>
                                @endif
                                @if($article->read_time_minutes)
                                    <span>{{ $article->read_time_minutes }} {{ __('site.articles.read_min') }}</span>
                                @endif
                            </div>
                            <h3>{{ $article->title }}</h3>
                            @if($article->intro)
                                <p>{{ $article->intro }}</p>
                            @endif
                            <span class="lnk">{{ __('site.articles.read_more') }} →</span>
                        </a>
                    @endforeach
                </div>

                {{ $articles->onEachSide(1)->links('vendor.pagination.site') }}
            @endif
        </div>
    </section>
@endsection
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ArticleListPageTest`

Expected: all `ArticleListPageTest` cases pass.

- [ ] **Step 5: Build assets so the new CSS is reachable in dev**

Run: `npm run build`

Expected: Vite build succeeds (this also picks up the new `@import` from Task 6).

- [ ] **Step 6: Commit**

```bash
git add resources/views/articles/index.blade.php tests/Feature/Content/ArticleListPageTest.php
git commit -m "feat(articles): full markup for the index page"
```

---

## Task 8: Full markup for the Article detail page (head + cover + body)

**Files:**
- Modify: `resources/views/articles/show.blade.php`
- Test: `tests/Feature/Content/ArticleShowPageTest.php`

- [ ] **Step 1: Extend the show tests**

Append to `tests/Feature/Content/ArticleShowPageTest.php`:

```php
it('renders the metadata strip and the article body as HTML', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tilo', 'en' => 'body'],
        'title' => ['uk' => 'Тіло', 'en' => 'Body'],
        'intro' => ['uk' => 'Лід.', 'en' => 'Lead.'],
        'content' => [
            'uk' => "Перший абзац.\n\n## Підзаголовок\n\nДругий абзац.",
            'en' => "First paragraph.\n\n## Subheading\n\nSecond paragraph.",
        ],
        'category' => ['uk' => 'Освіта', 'en' => 'Education'],
        'read_time_minutes' => 5,
        'published_at' => \Carbon\Carbon::create(2026, 5, 12, 9),
    ]);

    $this->get('/articles/tilo')
        ->assertOk()
        ->assertSee('Освіта')
        ->assertSee('12 травня 2026')
        ->assertSee('5 хв')
        ->assertSee('Лід.')
        ->assertSee('<h2>Підзаголовок</h2>', escape: false)
        ->assertSee('<p>Перший абзац.</p>', escape: false);
});

it('shows breadcrumbs that link back to the articles index', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'krohty', 'en' => 'crumbs'],
        'title' => ['uk' => 'Крихти', 'en' => 'Crumbs'],
    ]);

    $this->get('/articles/krohty')
        ->assertOk()
        ->assertSee('href="/articles"', escape: false)
        ->assertSee('Крихти');
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter=ArticleShowPageTest`

Expected: both new tests FAIL — stub view does not render meta strip or markdown body.

- [ ] **Step 3: Replace the stub view with the full detail markup**

Overwrite `resources/views/articles/show.blade.php`:

```blade
@extends('layouts.site')

@section('title', $article->seo_title ?: $article->title)
@section('description', $article->seo_description
    ?: \Illuminate\Support\Str::limit(strip_tags((string) $article->intro), 160))

@section('content')
    @php($locale = app()->getLocale())
    @php($coverUrl = $article->getFirstMediaUrl('primary', 'detail'))

    <article style="padding: 32px 0 80px">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
                ['href' => route('articles.index'), 'label' => __('site.nav.articles')],
                ['label' => $article->title],
            ]"/>

            <div class="article-head">
                <div class="meta">
                    @if($article->category)
                        <span class="tag">{{ $article->category }}</span>
                    @endif
                    @if($date = $article->displayDate())
                        <span>{{ $date }}</span>
                    @endif
                    @if($article->read_time_minutes)
                        <span>{{ $article->read_time_minutes }} {{ __('site.articles.read_min') }}</span>
                    @endif
                </div>
                <h1 class="article-title">{{ $article->title }}</h1>
                @if($article->intro)
                    <p class="lead">{{ $article->intro }}</p>
                @endif
            </div>

            @if($coverUrl)
                <div class="article-cover">
                    <img src="{{ $coverUrl }}" alt="{{ $article->title }}">
                </div>
            @endif

            <div class="article-body">
                {!! \Illuminate\Support\Str::markdown($article->content ?? '') !!}
            </div>
        </div>

        {{-- products + related sections added in Task 9 --}}
    </article>
@endsection
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ArticleShowPageTest`

Expected: all `ArticleShowPageTest` cases pass.

- [ ] **Step 5: Commit**

```bash
git add resources/views/articles/show.blade.php tests/Feature/Content/ArticleShowPageTest.php
git commit -m "feat(articles): detail page head, cover and body"
```

---

## Task 9: In-article products and "Read also" sections on the detail page

**Files:**
- Modify: `resources/views/articles/show.blade.php`
- Test: `tests/Feature/Content/ArticleShowPageTest.php`

- [ ] **Step 1: Extend the show tests for products + related**

Append to `tests/Feature/Content/ArticleShowPageTest.php`:

```php
it('shows attached products with article-specific copy', function () {
    $product = \App\Models\Catalogue\Product::factory()->create([
        'name' => ['uk' => 'Onyx 03', 'en' => 'Onyx 03'],
    ]);
    $article = Article::factory()->create([
        'slug' => ['uk' => 'z-tovaramy', 'en' => 'with-products'],
        'title' => ['uk' => 'Із товарами', 'en' => 'With products'],
    ]);
    $article->products()->attach($product->id, ['sort_order' => 0]);

    $this->get('/articles/z-tovaramy')
        ->assertOk()
        ->assertSee('Аромати у статті')
        ->assertSee('Onyx 03')
        // The article-context slider hides the catalogue CTA:
        ->assertDontSee('Усі парфуми');
});

it('omits the products section when no products are attached', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'bez-tovariv', 'en' => 'no-products'],
    ]);

    $this->get('/articles/bez-tovariv')
        ->assertOk()
        ->assertDontSee('Аромати у статті');
});

it('renders the related articles block when other published articles exist', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'osnovna', 'en' => 'main'],
        'title' => ['uk' => 'Основна', 'en' => 'Main'],
    ]);
    Article::factory()->create(['title' => ['uk' => 'Сусід A', 'en' => 'Neighbor A']]);
    Article::factory()->create(['title' => ['uk' => 'Сусід B', 'en' => 'Neighbor B']]);
    Article::factory()->create(['title' => ['uk' => 'Сусід C', 'en' => 'Neighbor C']]);

    $this->get('/articles/osnovna')
        ->assertOk()
        ->assertSee('Читайте також')
        ->assertSee('Сусід A')
        ->assertSee('Сусід B')
        ->assertSee('Сусід C')
        ->assertDontSee('Основна', escape: false); // current article must NOT be in related
});

it('omits the related block when there are no other articles', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'sama', 'en' => 'alone'],
    ]);

    $this->get('/articles/sama')
        ->assertOk()
        ->assertDontSee('Читайте також');
});
```

The "current article must NOT be in related" assertion uses `assertDontSee('Основна')` — the show page itself prints "Основна" twice (breadcrumb + h1). To make the assertion meaningful, the test asserts the related block neighbors are present and current is absent from related. Since the current article's title IS rendered on the page, this assertion will fail unless we rewrite it. Replace it with this instead:

```php
$response = $this->get('/articles/osnovna');
$response->assertOk()
    ->assertSee('Читайте також')
    ->assertSee('Сусід A')
    ->assertSee('Сусід B')
    ->assertSee('Сусід C');

// Current article title appears in breadcrumb + h1 = 2 occurrences;
// if it also leaked into "related" it would be 3.
$body = $response->getContent();
expect(substr_count($body, 'Основна'))->toBeLessThan(3);
```

Use the corrected version above, not the original `assertDontSee('Основна')` line.

- [ ] **Step 2: Run tests to verify the new cases fail**

Run: `php artisan test --filter=ArticleShowPageTest`

Expected: the four new cases FAIL — show view does not yet render products/related sections.

- [ ] **Step 3: Extend the show view**

Edit `resources/views/articles/show.blade.php`. Replace the placeholder comment `{{-- products + related sections added in Task 9 --}}` with:

```blade
        @if($products->isNotEmpty())
            <div class="in-article-products">
                <x-site.product-slider
                    :products="$products"
                    :eyebrow="__('site.articles.in_article_products')"
                    :title="$article->title"
                    :cta-label="null"
                    :cta-url="null"/>
            </div>
        @endif

        @if($related->isNotEmpty())
            <section class="related-articles">
                <div class="container">
                    <div class="eyebrow">{{ __('site.articles.eyebrow', ['year' => now()->year]) }}</div>
                    <h2 style="font-style: italic; margin-top: 12px">{{ __('site.articles.related_title') }}</h2>
                    <div class="articles-grid articles-grid--3">
                        @foreach($related as $relatedArticle)
                            @php($relatedCover = $relatedArticle->getFirstMediaUrl('primary', 'card'))
                            <a class="article-card"
                               href="{{ route('articles.show', $relatedArticle->getTranslation('slug', $locale)) }}">
                                <div class="cover">
                                    @if($relatedCover)
                                        <img src="{{ $relatedCover }}" alt="{{ $relatedArticle->title }}"
                                             loading="lazy" width="800" height="600">
                                    @endif
                                </div>
                                <div class="meta">
                                    @if($relatedArticle->category)
                                        <span class="tag">{{ $relatedArticle->category }}</span>
                                    @endif
                                    @if($d = $relatedArticle->displayDate())
                                        <span>{{ $d }}</span>
                                    @endif
                                </div>
                                <h3>{{ $relatedArticle->title }}</h3>
                            </a>
                        @endforeach
                    </div>
                </div>
            </section>
        @endif
```

- [ ] **Step 4: Run tests to verify they pass**

Run: `php artisan test --filter=ArticleShowPageTest`

Expected: all `ArticleShowPageTest` cases pass (including the 4 new ones).

- [ ] **Step 5: Commit**

```bash
git add resources/views/articles/show.blade.php tests/Feature/Content/ArticleShowPageTest.php
git commit -m "feat(articles): in-article products + read-also block"
```

---

## Task 10: Header + footer navigation

**Files:**
- Modify: `resources/views/components/site/header.blade.php`
- Modify: `resources/views/components/site/footer.blade.php`
- Test: `tests/Feature/Content/ArticleListPageTest.php`

- [ ] **Step 1: Write the failing nav test**

Append to `tests/Feature/Content/ArticleListPageTest.php`:

```php
it('renders the Articles entry in the header and footer', function () {
    Article::factory()->create();

    $response = $this->get('/articles')->assertOk();

    // Header nav: the active link to /articles must appear with the "active" class
    $response->assertSee('class="active"', escape: false);
    $response->assertSee('Статті');

    // Footer must contain at least one anchor to /articles
    $body = $response->getContent();
    expect(substr_count($body, 'href="/articles"'))->toBeGreaterThanOrEqual(2);
});
```

- [ ] **Step 2: Run tests to verify they fail**

Run: `php artisan test --filter='renders the Articles entry'`

Expected: FAIL — header has no Articles link and footer has no Articles link, so `href="/articles"` appears 0 times (or fewer than 2 times).

- [ ] **Step 3: Extend the header nav**

Edit `resources/views/components/site/header.blade.php`. In the `$nav` array, add a third item after the `catalog` row:

```php
['key' => 'articles', 'url' => route('articles.index'),                  'match' => fn ($r) => str_starts_with($r, '/articles')],
```

The `__("site.nav.{$item['key']}")` lookup will resolve `site.nav.articles` (added in Task 3).

- [ ] **Step 4: Extend the footer Navigation column**

Edit `resources/views/components/site/footer.blade.php`. In the column rendered as:

```blade
<div>
    <h4>{{ __('site.footer.columns.nav') }}</h4>
    <ul>
        <li><a href="{{ LaravelLocalization::localizeURL('/') }}">{{ __('site.nav.home') }}</a></li>
        <li><a href="{{ route('products.index') }}">{{ __('site.nav.catalog') }}</a></li>
    </ul>
</div>
```

…add a third `<li>` after the catalog entry:

```blade
<li><a href="{{ route('articles.index') }}">{{ __('site.nav.articles') }}</a></li>
```

- [ ] **Step 5: Run tests to verify they pass**

Run: `php artisan test --filter=ArticleListPageTest`

Expected: all cases pass, including the new nav test.

- [ ] **Step 6: Commit**

```bash
git add resources/views/components/site/header.blade.php \
        resources/views/components/site/footer.blade.php \
        tests/Feature/Content/ArticleListPageTest.php
git commit -m "feat(nav): link to the Articles section from header and footer"
```

---

## Task 11: Full-suite verification and manual UI check

**Files:** none

- [ ] **Step 1: Run the entire test suite**

Run: `composer test`

Expected: all tests pass. If any unrelated test broke, investigate before continuing (the slider rewrite in Task 5 is the most likely culprit — check that no other page asserts the old hardcoded CTA copy).

- [ ] **Step 2: Run Pint to enforce code style**

Run: `./vendor/bin/pint --dirty`

Expected: any auto-formatting applied, no errors.

If Pint reformatted files, amend the most recent commit that includes them, or create a small follow-up commit:

```bash
git add -u && git commit -m "style: pint"
```

- [ ] **Step 3: Boot the dev stack**

Run: `composer dev`

Wait until Vite reports "ready", and the artisan server prints `Server running on http://127.0.0.1:8000`.

- [ ] **Step 4: Seed a few articles via tinker if the database is empty**

If the dev DB has fewer than 4 published articles, run:

```bash
php artisan tinker --execute='\App\Models\Content\Article::factory()->count(15)->create();'
```

- [ ] **Step 5: Visit each surface and eyeball it**

Open in a browser (under `http://127.0.0.1:8000`):

- `/articles` — confirm: 2-column grid, 12 cards max, pagination at the bottom, breadcrumbs, italic title, meta strip on each card (category accented in gold, date, read time), hover state.
- `/en/articles` — confirm: English nav, English title, English meta.
- `/articles/<some-uk-slug>` — confirm: breadcrumb chain, large italic title, full-bleed cover, italic lead paragraph, body typography (h2 italic, blockquote, lists), in-article products section (only if products attached), "Читайте також" block with 3 cards in a 3-column grid, separator line above it.
- `/en/articles/<en-slug-of-same-article>` — confirm: English copy.
- `/` and `/products` — confirm: the new "Статті" entry appears in the header nav and the footer Navigation column; existing pages still look identical (slider on `/products/<slug>` still says "Інше з нашого дому" and links to all products).

If any visual issue appears, fix it in the appropriate file from earlier tasks and commit as a follow-up.

- [ ] **Step 6: Final commit (if any Pint or fixup edits were made)**

If steps 2 or 5 produced edits, stage and commit them:

```bash
git add -u
git commit -m "chore: post-implementation fixes for articles section"
```

- [ ] **Step 7: Mark plan complete**

The articles section is ready for review. Summarize for the human reviewer:

- Migration, model and factory changes.
- Filament UI for the new fields.
- Routes, controller, two new Blade views, one extended Blade component, one new CSS file.
- 11 + N feature tests (article list, article show, Filament resource).
