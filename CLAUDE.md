# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

LevantParfums is a Laravel 13 + Filament 5 application for a perfumery catalogue. It has three surfaces:

- **Admin panel** at `/admin` (Filament 5).
- **Storefront** at `/{locale}/...` — home (a `Page` flagged `is_homepage`), product catalogue (`/products`, `/products/{slug}`), and arbitrary CMS pages (`/{slug}` resolved against translated `Page.slug`).
- **Public forms** (contact, order) embedded as Livewire components.

Two main domains, kept in separate namespaces:

- `App\Models\Catalogue\*` — `Product` plus nine reference dictionaries (`PerfumeFamily`, `Concentration`, `Series`, `Note`, `Brand`, `Tag`, `Season`, `Occasion`, `Audience`).
- `App\Models\Content\*` — `Page` (static pages with `simple`/`landing` templates + JSON `blocks`, no public listing) and `Article` (flat blog, scheduled publishing, M2M `article_product` with `sort_order`).

Filament resources mirror this split under `app/Filament/Resources/{Products,Articles,Pages,...}/`, each with `Schemas/`, `Tables/`, and `Pages/` subfolders. Resources are auto-discovered (see `AdminPanelProvider`).

## Commands

```bash
composer setup                          # First-time setup (install, key:gen, migrate, npm build, storage:link)
composer dev                            # Parallel: artisan serve + queue + pail + vite (concurrently)
composer test                           # Clears config then runs `artisan test` (Pest)
php artisan test --filter=ProductMediaTest    # Run a single test file
php artisan test --filter='it renames pivot'  # Run a single test by name
./vendor/bin/pint                       # Format PHP (Laravel Pint)
./vendor/bin/pest --parallel            # Pest directly, parallel
php artisan migrate:fresh --seed        # Reset DB + run DatabaseSeeder (creates admin@levantparfums.test / password)
npm run dev                             # Vite dev server only
```

Local dev DB is **MySQL via Sail** (`compose.yaml`), but tests use **in-memory SQLite** (see `phpunit.xml`). Anything written for the catalogue/content must work on both — see "Multi-DB constraints" below.

## Architecture notes that span multiple files

### Translations (uk default, en fallback)

- Locales are pinned to `['uk', 'en']` in `config/catalogue.php` and in the Filament panel (`SpatieTranslatablePlugin::defaultLocales`).
- Translatable columns are `json`, declared via `Spatie\Translatable\HasTranslations` + `public array $translatable = [...]` on the model.
- Filament uses `lara-zeus/spatie-translatable` — every translatable resource adds `use Translatable;` (trait from `LaraZeus\SpatieTranslatable\Resources\Concerns\Translatable`) **and** Edit/Create pages add the corresponding `EditRecord\Concerns\Translatable` / `CreateRecord\Concerns\Translatable` traits plus a `LocaleSwitcher::make()` header action. Missing any of the three is a common bug.
- UI strings live in `lang/{uk,en}/{catalogue,content,messages}.php`. Filament navigation groups and labels resolve via `trans('catalogue.navigation.*')` / `trans('content.navigation.*')` inside `AdminPanelProvider` and each resource — **do not hardcode group names**, the panel resolves them at runtime so the active locale wins.

### Dual-currency pricing

`Product` stores `price_uah` and `price_eur` as two explicit decimal columns — there is no FX conversion. `Product::displayPrice($locale)` picks the column via `config('catalogue.currency_by_locale')`. When adding price-aware code, branch on locale via this config, never hardcode currencies.

### Translatable slugs with DB-level uniqueness

`Article` and `Page` slugs are translatable (`json`), but uniqueness must be enforced per-locale. Migrations add **functional unique indexes** on `JSON_EXTRACT(slug, '$.uk')` / `'$.en'`, with separate SQL for MySQL (`ALTER TABLE ... ADD UNIQUE ...`) and SQLite (`CREATE UNIQUE INDEX ...`). See `2026_05_23_055707_create_pages_table.php` for the template. New translatable-slug tables must replicate both branches or tests will silently pass on SQLite while prod (MySQL) breaks.

`config/content.php` lists `reserved_slugs` that must be rejected at the form layer for Page/Article.

### Filament repeater for M2M with sort_order (Article ↔ Product)

The `article_product` pivot has `sort_order`, but Filament's `Select::multiple()` cannot preserve ordering. The pattern in `app/Filament/Resources/Articles/Pages/EditArticle.php` is the reference:

1. `mutateFormDataBeforeFill` — hydrate from `record->products()` ordered by pivot.
2. `mutateFormDataBeforeSave` — cache the repeater rows into `$cachedProducts`, set `$productsCached = true`, then `unset($data['products'])` so the parent save doesn't choke.
3. `afterSave` — detach all, then re-attach with `sort_order` = array index, then reset `$productsCached = false` (the reset is required because Livewire instances are reused across saves; without it, the second save would skip the cache step).

Mirror this exact pattern for any future ordered M2M.

### Media (Spatie MediaLibrary)

Models with images implement `HasMedia` + `InteractsWithMedia` and define:

- `registerMediaCollections()` — `primary` (singleFile) and optionally `gallery`, restricted to `image/jpeg|png|webp`.
- `registerMediaConversions()` — `thumb`, `card`, `detail`, all `format('webp')` and `nonQueued()` (intentional: keeps tests sync and admin previews instant).

### Forms subsystem (`App\Forms\*`)

Public forms (contact, order, future) are handled by a single engine:

1. **One row per submission** — `form_submissions` table with JSON `data` + JSON `meta` + polymorphic `subject` + workflow `status` (`new` / `read` / `processed`). See `App\Forms\Models\FormSubmission`.
2. **One PHP class per form type** — extend `App\Forms\Types\FormType` (`key()`, `label()`, `rules()`, `attributes()`, `adminMailable()`, optional `clientMailable()` and `subjectClass()`). Register the class in `config('forms.types')`.
3. **One Livewire component per form** — extend `App\Forms\Livewire\FormComponent`, declare public properties for fields, override `formType()` and `render()`. The base owns: honeypot, rate-limit, validation, persistence, email dispatch, locale capture. Components live under `app/Forms/Livewire/`, so they must be registered with `Livewire::component()` in `AppServiceProvider::boot()` for `<livewire:my-form />` Blade syntax to work.
4. **One Blade view per form** under `resources/views/forms/{key}.blade.php` — the developer writes the markup; the only mandatory element is `<x-forms.honeypot wire:model="hp" />`.
5. **Per-type Mailable + Markdown template** under `App\Forms\Mail\*` and `resources/views/emails/forms/{key}-{admin|client}.blade.php`. Admin mail goes in `config('app.fallback_locale')`; client mail goes in the submission's captured locale.
6. **Admin sees everything in one inbox** — `FormSubmissionResource` (read-only: only List + View). New rows fan out Filament database notifications to all admin users via `FormSubmissionObserver` (wired in `AppServiceProvider::boot()`).

Anti-spam: silent honeypot (an empty `$hp` is the only acceptable value) + Laravel `RateLimiter` keyed on `forms:{type}:{ip}`. Rate-limit breach surfaces as a `ValidationException` on the `form` key so it shows up inline like any other field error.

### Public routing is localized + the `/{slug}` catch-all

`routes/web.php` wraps every public route in `LaravelLocalization::setLocale()` + the `localeSessionRedirect` / `localizationRedirect` / `localeViewPath` middleware. Admin (`/admin`) lives outside this group.

Route order matters: `/products` and `/products/{slug}` are defined before the catch-all `/{slug}` → `PageController@show`, which resolves the slug against `Page` per locale via `whereJsonContains("slug->{$locale}", $slug)`. Any new top-level path (e.g. `/blog`, `/cart`) must be:

1. Registered **before** the `/{slug}` route, **and**
2. Added to `config('content.reserved_slugs')` so it can never be claimed by a CMS page (saving a `Page` with a reserved slug throws `DomainException` from `Page::booted()`).

### Storefront pages, templates, and blocks

The home page and CMS pages share `PageController` + `resources/views/pages/templates/{template}.blade.php`. `Page::template` is a `PageTemplate` enum (`simple` | `landing`); the controller dispatches on `$page->template->value`.

- **`simple`** uses `intro` + `content` (translatable HTML).
- **`landing`** ignores `content` and renders `Page::visibleBlocks()` — an ordered array from the JSON `blocks` column, with each entry shaped `['type' => BlockType, 'data' => [...]]`. `visibleBlocks()` filters out blocks where `data.is_visible === false`. Block partials live in `resources/views/pages/blocks/{hero,products,text,articles}.blade.php`, matching the `BlockType` enum cases. `Page::booted()` also normalizes Spatie's `{"uk":null}` translation artifact to a true DB `NULL` for `content` — write through `attributes` directly if you need to bypass the translation setter.

### Series-driven theming

Each `Series` has a `theme_class` column. `ProductCatalogController@show` passes `$theme = $product->series?->theme_class ?? 'theme-cream'` to the view; `layouts/site.blade.php` writes it onto `<body class="{{ $theme ?? 'theme-cream' }}">`. New pages/controllers that should respect series theming must pass `$theme` explicitly — the layout has no fallback lookup.

### Catalogue listing: sort + series filter

`ProductCatalogController@index` only accepts a whitelisted `series` query param (`onyx`, `luxury` — see `ALLOWED_SERIES`) and a whitelisted `sort` token (`pop` | `new` | `priceA` | `priceB` — see `ALLOWED_SORTS`); anything else is silently coerced to the default. Sort tokens `pop` and `new` rank products that carry the `bestseller` / `new` `Tag` (matched by `tags.slug`) ahead of the rest via a correlated `EXISTS` subquery on `product_tag`. The tag slugs are interpolated into raw SQL — keep them slug-safe (the route regex is `[A-Za-z0-9\-_]+`) and never derive them from user input.

### Frontend stack

- **Tailwind CSS v4** via the `@tailwindcss/vite` plugin; entry is `resources/css/app.css` which imports `tailwindcss`, then the modular `resources/css/site/index.css` (which pulls in `site/components/*.css` and `site/pages/*.css`). Theme tokens (`--font-sans`, `--font-serif`) are declared in the `@theme` block in `app.css`.
- **Fonts**: Inter + Piazzolla, wired through `laravel-vite-plugin/fonts` (`bunny(...)` in `vite.config.js`) and injected via the `@fonts` Blade directive in the site layout. Piazzolla replaced the original Fraunces because Fraunces ships no Cyrillic subset on Bunny/Google Fonts — Ukrainian text fell back to Georgia. Any future serif swap must verify Cyrillic glyph coverage.
- **Alpine.js 3** is registered once at the layout level. JS entry `resources/js/app.js` imports a small set of site modules from `resources/js/site/` (`reveal`, `intro-veil`, `lightbox`). Do not pull in a second Alpine instance — see commit `056fb9b` ("drop duplicate Alpine") for the precedent.
- Public site Blade components live under `resources/views/components/site/*` and are invoked as `<x-site.{name} />`.

## Testing

- Pest 4 with `RefreshDatabase` auto-applied to everything under `tests/Feature` (see `tests/Pest.php`). Don't add the trait manually.
- Tests run against **SQLite `:memory:`** (`phpunit.xml`). Anything DB-specific must have a SQLite branch — see migrations.
- Filament resources are tested via Livewire (`Livewire::test(EditArticle::class, ...)`). When adding Filament tests look at `tests/Feature/Catalogue/Filament/ProductResourceTest.php` and `tests/Feature/Content/Filament/ArticleResourceTest.php` for the established style.
- An admin user is seeded by `DatabaseSeeder`: `admin@levantparfums.test` / `password`. Use it for manual admin checks after `migrate:fresh --seed`.

## Specs and plans

Detailed design docs live in `docs/superpowers/specs/` (the *why*) and execution plans in `docs/superpowers/plans/`. Before non-trivial work on catalogue or content, skim the matching spec — it records decisions (e.g. "no soft deletes", "no variants by volume", "no author on Article") that aren't visible in code.
