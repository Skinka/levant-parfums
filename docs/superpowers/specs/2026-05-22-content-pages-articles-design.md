# Content: Pages & Articles

**Date:** 2026-05-22
**Status:** Approved (revised 2026-05-22 after code review)
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

After `Schema::create`, the migration adds DB-level uniqueness per locale via functional unique indexes (see `slug uniqueness — defense in depth` below):

```php
DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_uk_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.uk')) AS CHAR(191))))");
DB::statement("ALTER TABLE pages ADD UNIQUE pages_slug_en_uniq ((CAST(JSON_UNQUOTE(JSON_EXTRACT(slug, '$.en')) AS CHAR(191))))");
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

Same `DB::statement(...)` block applied to `articles` with `articles_slug_uk_uniq` / `articles_slug_en_uniq`.

### `article_product` pivot

```php
$table->foreignId('article_id')->constrained()->cascadeOnDelete();
$table->foreignId('product_id')->constrained()->cascadeOnDelete();
$table->unsignedSmallInteger('sort_order')->default(0);
$table->primary(['article_id', 'product_id']);
$table->index(['article_id', 'sort_order']);  // ordered fetch
```

### Slug uniqueness — defense in depth

Slugs are translatable JSON, so plain `unique()` does not apply. The project DB is MySQL (`.env`), version 8.0.13+, which supports functional unique indexes on JSON paths via `CAST(JSON_UNQUOTE(JSON_EXTRACT(...)) AS CHAR(191))` — `191` matches Laravel's default index length, `JSON_UNQUOTE` strips the surrounding quotes that `JSON_EXTRACT` would otherwise include in the indexed value.

Three layers, in order of authority:

1. **DB unique indexes** (`pages_slug_{uk,en}_uniq`, `articles_slug_{uk,en}_uniq`) — the source of truth. Catches duplicates from seeders, factories, tinker, future imports, future APIs. A duplicate write fails with `QueryException` no matter the entry point.
2. **Filament form validation** — `Rule::unique($table, 'slug->uk')->ignore($record)` and same for `en`, as two separate rules. Surfaces friendly errors before the DB rejects.
3. **Factory guards** — `PageFactory` / `ArticleFactory` append `Str::random(4)` suffix to slugs to make collisions practically impossible during test runs.

### Reserved slugs

A Page is intended to live at `/{locale}/{slug}` and would collide with `/{locale}/blog`, `/{locale}/admin`, etc. The list of reserved words is centralised in `config/content.php`:

```php
return [
    'reserved_slugs' => [
        'admin', 'api', 'assets', 'storage', 'login', 'register', 'logout',
        'blog', 'articles', 'pages', 'sitemap', 'feed',
        'uk', 'en',  // locale prefixes
    ],
];
```

Enforcement:

- **Filament form**: slug field gets `->rule('not_in:' . implode(',', config('content.reserved_slugs')))` applied per-locale.
- **Model boot**: `Page::saving()` throws `\DomainException` if any locale's slug is in the reserved list. Defence against non-admin writers (seeders, factories, future APIs).
- Article model does NOT need the model-boot guard — the blog index `/{locale}/blog/{slug}` cannot collide with anything at that depth. Reserved-slug validation only applies to Page.

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
- `TextInput::make('slug')` — translatable, required. Per-locale uniqueness via `Rule::unique($table, 'slug->uk')->ignore($record)` and same for `en`. For Page, additionally `->rule('not_in:'.implode(',', config('content.reserved_slugs')))` per-locale. DB has a functional unique index as the source-of-truth (see Slug uniqueness section).
- `Textarea::make('intro')` — translatable, rows=3, max=300.
- `MarkdownEditor::make('content')` — translatable, required. Toolbar: bold, italic, link, headings, bulletList, orderedList, blockquote, codeBlock. No `attachFiles` (in-content images out of scope).
- `Toggle::make('is_published')`.
- `DateTimePicker::make('published_at')` — seconds(false), nullable. Helper: "Стаття з'явиться на сайті в цей час. Лишіть порожнім — публікація одразу".

**Зображення:** `SpatieMediaLibraryFileUpload::make('primary')->collection('primary')->image()->imageEditor()->maxSize(4096)` — exact copy of Product.

**SEO:**
- `TextInput::make('seo_title')` — translatable, max=70, character-count helper.
- `Textarea::make('seo_description')` — translatable, max=160, character-count helper.

**Прив'язані товари** (separate section in the SEO tab) — uses the `Repeater` pattern established by `ProductForm::notesRepeater()` (`app/Filament/Resources/Products/Schemas/ProductForm.php:136-150`):

```php
Repeater::make('products')
    ->relationship()              // binds to Article::products() BelongsToMany
    ->schema([
        Select::make('product_id')
            ->relationship('product', 'slug')
            ->getOptionLabelFromRecordUsing(fn ($record) => $record->name)
            ->searchable()
            ->preload()
            ->required(),
    ])
    ->orderColumn('sort_order')   // writes pivot.sort_order on save
    ->reorderable()
    ->defaultItems(0)
    ->addActionLabel(trans('content.fields.add_product'));
```

Why repeater, not `Select::multiple()->reorderable()`:
- Existing codebase pattern is repeater-based for ordered pivots — consistency.
- Repeater's `orderColumn('sort_order')` deterministically writes the pivot order column; `Select::reorderable()` on a m2m is ambiguous about pivot column persistence.
- `relationship('product', 'slug') + getOptionLabelFromRecordUsing(fn ($r) => $r->name)` is the project pattern for translatable label fields (Product.name is JSON) — direct `relationship('products', 'name')` would search/sort against raw JSON.

For this to work, the Repeater needs a row-level Eloquent relation `product()` (BelongsTo) on the pivot. Filament's `->relationship()` on Repeater handles this when paired with `withPivot(['sort_order'])` on `Article::products()`.

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

## Tests (Pest)

### Model + DB invariants

```
tests/Feature/Content/ArticleTest.php
  - casts published_at to datetime
  - stores translatable fields per locale
  - published scope hides is_published=false
  - published scope hides future published_at
  - products relation orders by pivot sort_order
  - DB rejects duplicate slug->uk for two articles      (QueryException)
  - DB rejects duplicate slug->en for two articles      (QueryException)

tests/Feature/Content/PageTest.php
  - stores translatable fields per locale
  - published scope returns only is_published=true
  - DB rejects duplicate slug->uk for two pages         (QueryException)
  - saving throws DomainException when slug is reserved (any locale)
```

### Filament behaviour for high-risk paths

The two riskiest paths run through the admin (slug uniqueness on duplicate input; ordered product pivot). These get focused Livewire tests — same approach as `tests/Feature/Catalogue/Filament/ProductResourceTest.php`:

```
tests/Feature/Content/Filament/ArticleResourceTest.php
  - create form rejects slug duplicating existing article (per locale)
  - create form rejects slug listed in reserved_slugs (Page only — n/a for Article)
  - edit form attaches selected products with sort_order matching repeater order
  - reorder in repeater persists new sort_order to article_product pivot

tests/Feature/Content/Filament/PageResourceTest.php
  - create form rejects slug listed in reserved_slugs (per locale)
  - create form rejects slug duplicating existing page (per locale)
```

Broad form/table rendering tests are still skipped — these targeted tests cover the behaviour at risk without paying the maintenance cost of full-render assertions.

## Implementation order

1. Config — `config/content.php` with `reserved_slugs` list.
2. Migrations — `pages`, `articles`, `article_product`. Each table migration also issues `DB::statement` for the functional unique slug indexes.
3. Models — Page, Article + factories. Page has `saving` boot hook that throws on reserved slug.
4. Translations — `lang/uk/content.php`, `lang/en/content.php`.
5. Filament `PageResource` — Resource, Schema, Table, Pages.
6. Filament `ArticleResource` — same. Repeater for ordered products.
7. Seeders — Page (placeholder), Article (3 demo).
8. Tests — model/DB invariants + Filament behaviour tests for slug + repeater.
9. Verification — `composer test` + manual admin smoke test (`php artisan serve` → `/admin`).

## Explicit non-goals

- No frontend routes, controllers, or Blade views.
- No author field, no Page sort_order, no draft state, no in-content image uploads.
- No category/tag taxonomy on articles.
- No JSON-LD, hreflang, sitemap, or reading-time computation — these belong to the future frontend work.

## Revision log

**2026-05-22 — post-review revision (5 comments addressed):**

- **P1 — slug uniqueness only in Filament** → added MySQL functional unique indexes per locale as source of truth; Filament + factory-side guards kept as defence-in-depth.
- **P1 — Page slug colliding with reserved routes** → introduced `config/content.php` `reserved_slugs` list, validated in Page form and enforced in Page model `saving` boot hook.
- **P2 — Article-Product ordering underspecified** → switched from `Select::multiple()->reorderable()` to the project-standard `Repeater` pattern (`notesRepeater()` in `ProductForm.php`) with `orderColumn('sort_order')`. Added explanatory rationale.
- **P2 — translatable product labels** → repeater's inner Select uses `relationship('product', 'slug') + getOptionLabelFromRecordUsing(fn ($r) => $r->name)` to match existing codebase pattern for translatable label fields.
- **P2 — test scope skips riskiest behaviour** → added focused Livewire/Filament tests for duplicate-slug rejection and pivot ordering; broad render tests still skipped.
