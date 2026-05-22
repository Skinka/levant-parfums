# Content: Pages & Articles

**Date:** 2026-05-22
**Status:** Approved
**Scope:** Data model + Filament admin + seeders + translations. No frontend (none exists yet — matches current state of Product).

## Purpose

Add two independent content types to LevantParfums:

- **Page** — static pages (About, Delivery, Payment, Policy, ...). No listing on the site, opened via `/{locale}/{slug}`.
- **Article** — blog articles. Flat feed at `/{locale}/blog`, detail at `/{locale}/blog/{slug}`.

Frontend routing is out of scope for this spec — only data layer and admin UX.

## Decisions (recorded from brainstorming)

| # | Decision | Rationale |
|---|---|---|
| 1 | Two separate models (Page, Article), not one polymorphic | Behaviour diverges (scheduling, product links); cleaner than `type` discriminator |
| 2 | Markdown content via `MarkdownEditor` | Versionable, simple, no HTML cleanup needed |
| 3 | Flat blog feed — no categories or tags on Article | Smallest viable surface; can extend later |
| 4 | Full bilingual (uk/en) via `HasTranslations` | Matches Product pattern across the site |
| 5 | Translatable slug | Better SEO and UX per locale; standard for content |
| 6 | Article ↔ Product many-to-many with `sort_order` | Internal linking from content to purchase; editor-controlled order |
| 7 | Scheduled publishing on Article (`published_at` can be future) | Editor workflow; Page does not need this |
| 8 | No author field, no Page sort_order, no draft state | Out of scope for MVP |

## Architecture

Code lives under a new `Content` namespace, separate from `Catalogue`:

```
app/Models/Content/{Page, Article}.php
app/Filament/Resources/Pages/{PageResource, Schemas/PageForm, Tables/PagesTable, Pages/*}.php
app/Filament/Resources/Articles/{ArticleResource, Schemas/ArticleForm, Tables/ArticlesTable, Pages/*}.php
database/migrations/..._create_pages_table.php
database/migrations/..._create_articles_table.php
database/migrations/..._create_article_product_table.php
database/factories/Content/{PageFactory, ArticleFactory}.php
database/seeders/Content/{PageSeeder, ArticleSeeder}.php
lang/{uk,en}/content.php
tests/Feature/Content/{PageTest, ArticleTest}.php
```

Both models use the established triad: `HasFactory` + `HasTranslations` + `InteractsWithMedia`. No shared base class — duplication is small (~30 lines), keeps each model readable in isolation.

Filament navigation: new group `content`, sort=2 (after the existing `catalogue` group).

## Data model

### `pages`

```php
$table->id();
$table->json('slug');               // translatable
$table->json('title');
$table->json('intro')->nullable();
$table->json('content');            // markdown
$table->json('seo_title')->nullable();
$table->json('seo_description')->nullable();
$table->boolean('is_published')->default(false)->index();
$table->timestamps();
```

### `articles`

```php
$table->id();
$table->json('slug');
$table->json('title');
$table->json('intro')->nullable();
$table->json('content');
$table->json('seo_title')->nullable();
$table->json('seo_description')->nullable();
$table->boolean('is_published')->default(false)->index();
$table->timestamp('published_at')->nullable()->index();
$table->timestamps();
```

### `article_product` pivot

```php
$table->foreignId('article_id')->constrained()->cascadeOnDelete();
$table->foreignId('product_id')->constrained()->cascadeOnDelete();
$table->unsignedSmallInteger('sort_order')->default(0);
$table->primary(['article_id', 'product_id']);
```

### Translatable slug uniqueness

JSON columns cannot use a SQL unique index. Uniqueness is enforced in the Filament form: `Rule::unique('articles', 'slug->uk')` and `Rule::unique('articles', 'slug->en')` as separate validators, both `ignore`-ing the current record. Same approach for `pages`.

### Article visibility scope

```php
public function scopePublished(Builder $q): Builder {
    return $q->where('is_published', true)
        ->where(fn ($q) => $q->whereNull('published_at')
                              ->orWhere('published_at', '<=', now()));
}
```

Page's `published()` scope is just `where('is_published', true)`. Admin always sees everything.

## Media

Both models register a single `primary` collection (singleFile, jpeg/png/webp) — same shape as Product, minus the `gallery` collection. Three conversions:

| Name | Size | Use |
|---|---|---|
| `thumb` | 400×400 crop | admin table |
| `card` | 1200×630 crop | listing card AND `og:image` |
| `detail` | 1920×1080 crop | hero on detail page |

`card` doubles as the OG image — no separate field needed.

## Filament admin

### `ArticleForm` — 3 tabs

**Основне:**
- `TextInput::make('title')` — translatable, required, `live(onBlur)`, auto-fills empty slug via `Str::slug()` per locale.
- `TextInput::make('slug')` — translatable, required, per-locale unique.
- `Textarea::make('intro')` — translatable, rows=3, max=300.
- `MarkdownEditor::make('content')` — translatable, required. Toolbar: bold, italic, link, headings, bulletList, orderedList, blockquote, codeBlock. No `attachFiles` (in-content images out of scope).
- `Toggle::make('is_published')`.
- `DateTimePicker::make('published_at')` — seconds(false), nullable. Helper: "Стаття з'явиться на сайті в цей час. Лишіть порожнім — публікація одразу".

**Зображення:** `SpatieMediaLibraryFileUpload::make('primary')->collection('primary')->image()->imageEditor()->maxSize(4096)` — exact copy of Product.

**SEO:**
- `TextInput::make('seo_title')` — translatable, max=70, character-count helper.
- `Textarea::make('seo_description')` — translatable, max=160, character-count helper.
- `Select::make('products')->relationship('products', 'name')->multiple()->searchable()->preload()->reorderable()` — m2m with `sort_order` via `withPivot`.

### `PageForm` — same shape, minus `published_at` and `products`.

### `ArticlesTable`

Columns:
- `SpatieMediaLibraryImageColumn::make('primary')` — conversion `thumb`, 60×60.
- `TextColumn::make('title')` — searchable (JSON search across both locales), sortable, bold.
- `TextColumn::make('slug')` — toggleable, hidden by default.
- `IconColumn::make('is_published')` — boolean.
- `TextColumn::make('published_at')` — datetime, sortable, "Заплановано" badge for future dates.
- `TextColumn::make('products_count')` — `->counts('products')`, toggleable.
- `TextColumn::make('updated_at')` — sortable, default desc.

Filters: `TernaryFilter::make('is_published')`, custom `Filter` for `published_at > now()`.

Bulk actions: `publish` / `unpublish` — same pattern as `ProductsTable`.

### `PagesTable` — same, minus `published_at` filter and `products_count` column.

### Resource navigation

```php
public static function getNavigationGroup(): ?string {
    return trans('content.navigation.group');
}
protected static ?int $navigationSort = 2;
```

Icons: `heroicon-o-document-text` (Page), `heroicon-o-newspaper` (Article).

## Translations

New file `lang/{uk,en}/content.php`:

```php
return [
    'navigation' => ['group' => 'Контент' /* en: 'Content' */],
    'page'    => ['singular' => 'Сторінка', 'plural' => 'Сторінки'],
    'article' => ['singular' => 'Стаття',   'plural' => 'Статті'],
    'fields'  => [
        'title' => '...', 'slug' => '...', 'intro' => '...',
        'content' => '...', 'seo_title' => '...', 'seo_description' => '...',
        'is_published' => '...', 'published_at' => '...', 'primary' => '...',
        'products' => '...',
    ],
];
```

## Factories

Both factories generate translatable JSON for uk/en (faker `paragraphs(2, true)`), slug via `Str::slug(title)` per locale, `is_published = true`.

## Seeders

- `PageSeeder` — empty placeholder; real copy added later in the admin.
- `ArticleSeeder` — 3 demo articles via factory, each `hasAttached(Product::factory()->count(3), ['sort_order' => $n])`.
- Both registered in `DatabaseSeeder` after the catalogue block.

## Tests (Pest, feature-level only)

```
tests/Feature/Content/ArticleTest.php
  - casts published_at to datetime
  - stores translatable fields per locale
  - published scope hides is_published=false
  - published scope hides future published_at
  - products relation orders by pivot sort_order

tests/Feature/Content/PageTest.php
  - stores translatable fields per locale
  - published scope returns only is_published=true
```

Filament form/table rendering is not tested — low value, expensive to maintain.

## Implementation order

1. Migrations — `pages`, `articles`, `article_product`.
2. Models — Page, Article + factories.
3. Translations — `lang/uk/content.php`, `lang/en/content.php`.
4. Filament `PageResource` — Resource, Schema, Table, Pages.
5. Filament `ArticleResource` — same.
6. Seeders — Page (placeholder), Article (3 demo).
7. Feature tests for models and scopes.
8. Verification — `composer test` + manual admin smoke test (`php artisan serve` → `/admin`).

## Explicit non-goals

- No frontend routes, controllers, or Blade views.
- No author field, no Page sort_order, no draft state, no in-content image uploads.
- No category/tag taxonomy on articles.
- No JSON-LD, hreflang, sitemap, or reading-time computation — these belong to the future frontend work.
