# Product Catalogue Design — LevantParfums

**Date:** 2026-05-22
**Status:** Draft, awaiting user approval
**Scope:** Backend models, migrations, Filament admin panel for the perfumery catalogue. Storefront/frontend is out of scope; design is not finalised.

## 1. Goals

Add a perfumery product catalogue to the existing Laravel 13 + Filament 5 application, including:

- A `Product` model with all fields required for catalogue, SEO and pricing.
- Nine reference dictionaries (perfume families, concentrations, series, notes, brands, tags, seasons, occasions, audiences), each editable through Filament.
- Full Filament admin (CRUD for products and all dictionaries).
- Multilingual content (`uk` default, `en`) using `spatie/laravel-translatable` with `lara-zeus/spatie-translatable` for the Filament UI.
- Dual-currency pricing (UAH for `uk` locale, EUR for `en` locale) stored as two explicit columns — no FX conversion.
- Media (primary image + gallery) via `spatie/laravel-medialibrary`.
- Seeders for static dictionaries and factories for all entities.
- Pest test coverage for the catalogue domain.

No storefront, no checkout, no inventory tracking, no roles/permissions in this iteration.

## 2. Non-goals

- Storefront / public pages.
- Cart, checkout, order processing.
- Stock quantity tracking (only a binary `in_stock` flag).
- Variants by volume (all products share a single volume field).
- EAV / dynamic attributes (fixed dictionaries only).
- FX conversion (prices are entered manually in both currencies).
- Roles, permissions, audit log.

## 3. Domain model

### 3.1 Main entity

**`products`** — single catalogue product. One product corresponds to one bottle (e.g. "LUXURY 4").

### 3.2 Dictionaries (each is a separate Filament resource)

| Table | Purpose | Examples |
|---|---|---|
| `perfume_families` | Олфакторне сімейство | Квіткове, Деревне, Східне, Цитрусове, Фужерне, Шипрове, Гурманське, Акватичне |
| `concentrations` | Концентрація | EDP, EDT, Parfum, EDC, Extrait |
| `series` | Серія LevantParfums | LUXURY, ROYAL, ... |
| `notes` | Окремі ноти | бергамот, личі, жасмін, мускус, ... |
| `brands` | Бренди оригінальних парфумів (для Inspired by) | Ex Nihilo, Tom Ford, Creed |
| `tags` | Беджі/мітки на картці товару | Бестселер, Новинка, Акція, Лімітка |
| `seasons` | Сезон | Весна, Літо, Осінь, Зима |
| `occasions` | Привід | бранч, романтичний вечір, офіс, вечірка |
| `audiences` | Аудиторія | молоді жінки, чоловіки 30+, тін-ейджери |

### 3.3 Relationships

**belongsTo (FK on `products`):**

- `perfume_family_id` → `perfume_families` (nullable)
- `concentration_id` → `concentrations` (nullable)
- `series_id` → `series` (nullable)
- `inspired_brand_id` → `brands` (nullable)

**belongsToMany:**

- `product_note` — with extra columns `level enum('top','heart','base')` and `sort_order int`. The same note may appear at multiple levels for the same product (e.g. jasmine in heart and base) but not twice at the same level. Unique index on `(product_id, note_id, level)`.
- `product_tag` — simple pivot.
- `product_season` — simple pivot.
- `product_occasion` — simple pivot.
- `product_audience` — simple pivot.

## 4. Schema

### 4.1 `products`

```
id                      bigint PK
sku                     string, unique, not null
slug                    string, unique, not null
name                    json (translatable: uk, en), not null
tagline                 json (translatable), nullable
description             json (translatable), nullable (long text)
inspired_perfume_name   string, nullable                       — proper noun, not translatable
inspired_brand_id       FK brands, nullable
volume_ml               unsigned smallint, not null, default 50
gender                  enum('male','female','unisex'), not null
price_uah               decimal(10,2), not null
price_eur               decimal(10,2), not null
in_stock                boolean, not null, default true
is_published            boolean, not null, default false
published_at            timestamp, nullable
seo_title               json (translatable), nullable
seo_description         json (translatable), nullable
perfume_family_id       FK perfume_families, nullable
concentration_id        FK concentrations, nullable
series_id               FK series, nullable
created_at, updated_at  timestamps
```

Indexes: `slug`, `sku` (unique); `is_published`, `published_at`, `series_id`, `perfume_family_id`, `concentration_id`, `inspired_brand_id` (regular).

No soft deletes.

### 4.2 Dictionary base shape

Applied to `perfume_families`, `concentrations`, `series`, `brands`, `seasons`, `occasions`, `audiences`:

```
id           bigint PK
name         json (translatable: uk, en), not null
slug         string, unique, not null
sort_order   integer, not null, default 0
is_active    boolean, not null, default true
created_at, updated_at
```

### 4.3 Dictionary overrides

**`notes`** adds:

```
description  json (translatable), nullable
```

**`tags`** adds:

```
color        string (hex), not null
is_featured  boolean, not null, default false
```

**`concentrations`** adds:

```
abbreviation string, not null     — short form like "EDP", non-translatable
```

**`brands`** adds:

```
country      string, nullable
```

### 4.4 Pivot tables

`product_note`:

```
id           bigint PK
product_id   FK products on delete cascade
note_id      FK notes on delete cascade
level        enum('top','heart','base'), not null
sort_order   integer, not null, default 0
UNIQUE(product_id, note_id, level)
```

`product_tag`, `product_season`, `product_occasion`, `product_audience`:

```
product_id      FK products on delete cascade
<dict>_id       FK <dict>   on delete cascade
PRIMARY KEY(product_id, <dict>_id)
```

## 5. Media

Via `spatie/laravel-medialibrary` on `Product`.

Two collections:

- `primary` — `singleFile()`, exactly one image.
- `gallery` — multiple, reorderable.

Conversions (generated on upload, async via queue):

| Name | Size | Mode | Format |
|---|---|---|---|
| `thumb` | 200×200 | contain | WebP |
| `card` | 600×800 | cover | WebP |
| `detail` | 1200×1600 | contain | WebP |

Original kept as-is. Storage: `public` disk under `storage/app/public/media/{model_id}/`.

Alt text is stored in `custom_properties.alt.{locale}` (translatable, editable in Filament).

On product deletion all related media are removed (Spatie default).

## 6. Localisation & pricing

### 6.1 Languages

- Default: `uk`. Configured in `config/laravellocalization.php` (already in place).
- Secondary: `en`.
- `hideDefaultLocaleInURL = true` — `uk` URLs have no locale prefix; `en` URLs start with `/en/`.

### 6.2 Translatable fields

`Product`: `name`, `tagline`, `description`, `seo_title`, `seo_description`.

Dictionaries: `name` (and `description` on `notes`).

Non-translatable (locked to one form): `slug`, `sku`, `inspired_perfume_name`, `concentrations.abbreviation`, `tags.color`, `brands.country`.

### 6.3 Validation

- Default locale (`uk`) is required for every translatable field.
- Non-default locales are optional; fallback to default.
- `slug` and `sku` must be globally unique.

### 6.4 Currency

Mapping is held in `config/catalogue.php`:

```php
return [
    'default_volume_ml' => 50,
    'currency_by_locale' => [
        'uk' => 'UAH',
        'en' => 'EUR',
    ],
];
```

Helper on `Product`:

```php
public function displayPrice(?string $locale = null): array
{
    $locale = $locale ?? app()->getLocale();
    $currency = config("catalogue.currency_by_locale.$locale", 'UAH');
    return match ($currency) {
        'EUR' => ['amount' => $this->price_eur, 'currency' => 'EUR'],
        default => ['amount' => $this->price_uah, 'currency' => 'UAH'],
    };
}
```

In the Filament product list both prices are shown side by side regardless of admin locale.

## 7. Filament admin

### 7.1 Panel & plugins

Existing panel: `app/Providers/Filament/AdminPanelProvider.php` (route `/admin`).

Add plugins:

- `lara-zeus/spatie-translatable` (already installed) — locale tabs on translatable fields.
- `filament/spatie-laravel-media-library-plugin` — **add to `composer.json`**.

Configure panel:

```php
->plugin(SpatieLaravelTranslatablePlugin::make()->defaultLocales(['uk','en']))
```

### 7.2 Navigation

```
Каталог
  └─ Товари

Атрибути
  ├─ Сімейства
  ├─ Концентрації
  ├─ Серії
  ├─ Ноти
  ├─ Бренди
  ├─ Беджі
  ├─ Сезони
  ├─ Приводи
  └─ Аудиторія
```

### 7.3 ProductResource — form

Two-column layout. Main column uses tabs:

1. **Основне** — `name`, `slug` (auto-from-uk-name, editable), `sku`, `gender` (radio), `volume_ml` (numeric, default 50), `is_published` (toggle), `published_at` (datetime, visible when `is_published=true`), `in_stock` (toggle).
2. **Опис** — `tagline` (text), `description` (TipTap rich editor).
3. **Аромат** — `perfume_family_id`, `concentration_id`, `series_id` (searchable selects with inline-create); **notes** rendered as **three separate Repeaters**, one per level (Верхні / Серцеві / Базові). Each repeater holds a list of `note_id` selects; the order of items in the repeater becomes `sort_order` for that `(product, level)` group. On save the resource flattens the three repeaters into the `product_note` pivot, writing the correct `level` column for each row.
4. **Inspired by** — `inspired_brand_id` (select with inline-create), `inspired_perfume_name` (text).
5. **Маркування** — four `Select multiple` for tags, seasons, occasions, audiences (searchable, inline-create).
6. **SEO** — `seo_title`, `seo_description` (textarea).
7. **Зображення** — `SpatieMediaLibraryFileUpload` for `primary` (single) and `gallery` (multiple, reorderable). Alt text editor under each image with UK/EN tabs.

Side column (sticky Section):

- **Ціни:** `price_uah` (suffix `₴`), `price_eur` (suffix `€`).
- **Статус:** mirrored `is_published`, `in_stock`, `published_at` for convenience.

### 7.4 ProductResource — table

Columns: `[primary thumb] | name(uk) | sku | series | family | concentration | price_uah / price_eur | in_stock | is_published | actions`.

- Search on: `name`, `sku`, `slug`.
- Filters: `series`, `perfume_family`, `concentration`, `gender`, `is_published`, `in_stock`, `tags` (multiple).
- Bulk actions: publish, unpublish, delete.
- Default sort: `published_at desc, id desc`.

### 7.5 Dictionary resources

Each dictionary gets the standard CRUD resource (form: `name` translatable, `slug`, `sort_order`, `is_active`, plus type-specific fields from §4.3; table: those columns + `products_count` via `withCount`). All translatable fields use locale tabs.

## 8. Seeders & factories

### 8.1 Seeders (idempotent, keyed on `slug`)

- `PerfumeFamilySeeder` — 8 standard families.
- `ConcentrationSeeder` — EDP, EDT, Parfum, EDC, Extrait (with `abbreviation`).
- `SeasonSeeder` — Весна, Літо, Осінь, Зима.
- `TagSeeder` — Бестселер (#C77B7B), Новинка (#7CB87A), Акція (#D4A04C), Лімітка (#8B6F8B).
- `BrandSeeder`, `NoteSeeder`, `SeriesSeeder`, `OccasionSeeder`, `AudienceSeeder` — empty scaffolds for `db:seed`.

Registered in `DatabaseSeeder`.

### 8.2 Factories

`ProductFactory` and one factory per dictionary, used by tests and demo data generation.

## 9. Tests (Pest)

1. `Feature/Catalogue/ProductCrudTest` — Filament `livewire` tests for ProductResource: create, edit, delete; required-field validation; translation tabs visible.
2. `Feature/Catalogue/ProductRelationsTest` — pivots: `product_note` levels, tags, seasons, occasions, audiences; same note allowed at two levels.
3. `Feature/Catalogue/PriceLocalizationTest` — `Product::displayPrice()` returns UAH for `uk` and EUR for `en`.
4. `Feature/Catalogue/SlugUniquenessTest` — `slug` and `sku` unique constraints.
5. `Feature/Catalogue/MediaTest` — upload to `primary` / `gallery`; conversions exist; alt text translatable.
6. `Feature/Catalogue/AttributeCrudTest` — parameterised Filament tests for every dictionary resource.

## 10. Technical notes & dependencies

- **Composer add:** `filament/spatie-laravel-media-library-plugin`.
- **Config add:** `config/catalogue.php` (see §6.4).
- **Filament locale switch:** add UK/EN switcher in admin panel for translatable previews.
- **Language files:** add `lang/uk/catalogue.php`, `lang/en/catalogue.php` for navigation/form labels.
- **`storage:link`** — add `@php artisan storage:link` to the `setup` script in `composer.json` so media is served from `public/storage`.
- **No permissions / roles** in this iteration.

## 11. Out of scope (explicit)

- Storefront pages, routing, templates.
- Cart / checkout / orders.
- Stock quantity beyond a boolean.
- Volume variants.
- FX rate management.
- User roles / ACL.
- Search beyond Filament's built-in resource search.

## 12. Decisions log (for traceability)

| Decision | Choice | Rejected alternative |
|---|---|---|
| Volume model | Single volume per product (no variants) | Variants per product |
| Attribute system | Fixed dictionaries | EAV / dynamic attributes / JSON column |
| Price storage | Two explicit columns (UAH, EUR) | Single base + FX |
| "Ideal for" structure | Three dictionaries (seasons, occasions, audiences) | One typed dictionary; one flat tag list |
| Inspired by | Brand dictionary + plain text perfume name | Two text fields; two dictionaries |
| Gender | enum field on product | Separate categories tree |
| Stock | boolean `in_stock` | numeric quantity |
| Slug | single, non-translatable, no `uk` prefix in URL | translatable slug |
| Notes table | one table + pivot `level` | three separate tables |
| Money type | `decimal(10,2)` | minor-units integer |
