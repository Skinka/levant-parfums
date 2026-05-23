# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

LevantParfums is a Laravel 13 + Filament 5 application for a perfumery catalogue. There is **no storefront yet** — the work to date is data layer + admin panel only (`routes/web.php` exposes a localized `/` welcome view and nothing else).

Two main domains, kept in separate namespaces:

- `App\Models\Catalogue\*` — `Product` plus nine reference dictionaries (`PerfumeFamily`, `Concentration`, `Series`, `Note`, `Brand`, `Tag`, `Season`, `Occasion`, `Audience`).
- `App\Models\Content\*` — `Page` (static pages, no listing) and `Article` (flat blog, scheduled publishing, M2M `article_product` with `sort_order`).

Filament resources mirror this split under `app/Filament/Resources/{Products,Articles,Pages,...}/`, each with `Schemas/`, `Tables/`, and `Pages/` subfolders. Resources are auto-discovered (see `AdminPanelProvider`). The admin panel lives at `/admin`.

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

### Public routing is localized

`routes/web.php` wraps everything in `LaravelLocalization::setLocale()` + the `localeSessionRedirect` / `localizationRedirect` / `localeViewPath` middleware. Any new public route must go inside this group; admin (`/admin`) lives outside it.

## Testing

- Pest 4 with `RefreshDatabase` auto-applied to everything under `tests/Feature` (see `tests/Pest.php`). Don't add the trait manually.
- Tests run against **SQLite `:memory:`** (`phpunit.xml`). Anything DB-specific must have a SQLite branch — see migrations.
- Filament resources are tested via Livewire (`Livewire::test(EditArticle::class, ...)`). When adding Filament tests look at `tests/Feature/Catalogue/Filament/ProductResourceTest.php` and `tests/Feature/Content/Filament/ArticleResourceTest.php` for the established style.
- An admin user is seeded by `DatabaseSeeder`: `admin@levantparfums.test` / `password`. Use it for manual admin checks after `migrate:fresh --seed`.

## Specs and plans

Detailed design docs live in `docs/superpowers/specs/` (the *why*) and execution plans in `docs/superpowers/plans/`. Before non-trivial work on catalogue or content, skim the matching spec — it records decisions (e.g. "no soft deletes", "no variants by volume", "no author on Article") that aren't visible in code.
