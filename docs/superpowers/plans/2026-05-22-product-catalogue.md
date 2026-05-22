# Product Catalogue Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a perfumery product catalogue (Product + 9 dictionaries) with full Filament 5 admin, Spatie translations (uk default + en), dual-currency pricing, and Spatie Media Library images. No storefront in scope.

**Architecture:** Single `Product` Eloquent model with 4 `belongsTo` FKs to dictionaries (perfume_family, concentration, series, inspired_brand) and 5 `belongsToMany` relations (notes with `level` pivot column, tags, seasons, occasions, audiences). All dictionaries are fixed-shape (no EAV). Translations via `spatie/laravel-translatable` rendered as locale tabs by `lara-zeus/spatie-translatable` in Filament. Prices stored as two explicit columns (`price_uah`, `price_eur`) and selected by current locale. Images via `spatie/medialibrary` with two collections (`primary`, `gallery`) and WebP conversions.

**Tech Stack:** Laravel 13 (PHP 8.3), Filament 5, Pest 4, MySQL/SQLite, `spatie/laravel-translatable` 6, `spatie/laravel-medialibrary` 11, `lara-zeus/spatie-translatable` 2, `filament/spatie-laravel-media-library-plugin` (to install).

**Spec reference:** `docs/superpowers/specs/2026-05-22-product-catalogue-design.md`.

---

## File Structure

### New files

```
config/catalogue.php
lang/uk/catalogue.php
lang/en/catalogue.php

database/migrations/2026_05_22_1XXX01_create_perfume_families_table.php
database/migrations/2026_05_22_1XXX02_create_series_table.php
database/migrations/2026_05_22_1XXX03_create_seasons_table.php
database/migrations/2026_05_22_1XXX04_create_occasions_table.php
database/migrations/2026_05_22_1XXX05_create_audiences_table.php
database/migrations/2026_05_22_1XXX06_create_concentrations_table.php
database/migrations/2026_05_22_1XXX07_create_brands_table.php
database/migrations/2026_05_22_1XXX08_create_tags_table.php
database/migrations/2026_05_22_1XXX09_create_notes_table.php
database/migrations/2026_05_22_1XXX10_create_products_table.php
database/migrations/2026_05_22_1XXX11_create_product_note_table.php
database/migrations/2026_05_22_1XXX12_create_product_tag_table.php
database/migrations/2026_05_22_1XXX13_create_product_season_table.php
database/migrations/2026_05_22_1XXX14_create_product_occasion_table.php
database/migrations/2026_05_22_1XXX15_create_product_audience_table.php

app/Models/Catalogue/PerfumeFamily.php
app/Models/Catalogue/Series.php
app/Models/Catalogue/Season.php
app/Models/Catalogue/Occasion.php
app/Models/Catalogue/Audience.php
app/Models/Catalogue/Concentration.php
app/Models/Catalogue/Brand.php
app/Models/Catalogue/Tag.php
app/Models/Catalogue/Note.php
app/Models/Catalogue/Product.php
app/Enums/Gender.php
app/Enums/NoteLevel.php

database/factories/Catalogue/PerfumeFamilyFactory.php
database/factories/Catalogue/SeriesFactory.php
database/factories/Catalogue/SeasonFactory.php
database/factories/Catalogue/OccasionFactory.php
database/factories/Catalogue/AudienceFactory.php
database/factories/Catalogue/ConcentrationFactory.php
database/factories/Catalogue/BrandFactory.php
database/factories/Catalogue/TagFactory.php
database/factories/Catalogue/NoteFactory.php
database/factories/Catalogue/ProductFactory.php

database/seeders/Catalogue/PerfumeFamilySeeder.php
database/seeders/Catalogue/ConcentrationSeeder.php
database/seeders/Catalogue/SeasonSeeder.php
database/seeders/Catalogue/TagSeeder.php
database/seeders/Catalogue/BrandSeeder.php
database/seeders/Catalogue/NoteSeeder.php
database/seeders/Catalogue/SeriesSeeder.php
database/seeders/Catalogue/OccasionSeeder.php
database/seeders/Catalogue/AudienceSeeder.php

app/Filament/Resources/PerfumeFamilies/PerfumeFamilyResource.php (+ Schemas/Tables/Pages)
app/Filament/Resources/Series/SeriesResource.php (+ ...)
app/Filament/Resources/Seasons/SeasonResource.php (+ ...)
app/Filament/Resources/Occasions/OccasionResource.php (+ ...)
app/Filament/Resources/Audiences/AudienceResource.php (+ ...)
app/Filament/Resources/Concentrations/ConcentrationResource.php (+ ...)
app/Filament/Resources/Brands/BrandResource.php (+ ...)
app/Filament/Resources/Tags/TagResource.php (+ ...)
app/Filament/Resources/Notes/NoteResource.php (+ ...)
app/Filament/Resources/Products/ProductResource.php (+ Schemas/Tables/Pages)

tests/Feature/Catalogue/DictionaryBehaviourTest.php
tests/Feature/Catalogue/ProductModelTest.php
tests/Feature/Catalogue/ProductPriceLocalizationTest.php
tests/Feature/Catalogue/ProductMediaTest.php
tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
tests/Feature/Catalogue/Filament/ProductResourceTest.php
```

### Modified files

```
composer.json                                         (add filament media plugin + storage:link)
tests/Pest.php                                        (enable RefreshDatabase for Feature)
app/Providers/Filament/AdminPanelProvider.php         (navigation groups + locale switcher)
database/seeders/DatabaseSeeder.php                   (wire catalogue seeders)
```

Each Filament resource lives in its own subfolder per Filament 5 convention: `App\Filament\Resources\<Plural>\<Singular>Resource` with `Schemas/<Singular>Form.php`, `Tables/<Plural>Table.php`, and `Pages/{Create,Edit,List}<Singular>.php`.

---

## Conventions used by every task

- **Migrations** are created with `php artisan make:migration create_<table>_table`; the actual filename timestamp comes from the command. Do not hand-edit timestamps.
- **Models** are created with `php artisan make:model Catalogue/<Name> -mf` (creates model + migration + factory in one go) where convenient. Otherwise use separate commands.
- **Tests** use Pest 4. `RefreshDatabase` is enabled globally for `Feature/` in `tests/Pest.php` (Task 4). DB is SQLite in-memory via the `testing` connection in `phpunit.xml`.
- **Run tests:** `php artisan test --filter=<TestName>` for a single test, `php artisan test` for the full suite.
- **Commit format:** `<area>: <change>` (e.g. `catalogue: add Product migration`). Each task ends with one commit.

---

## Phase 1 — Foundation

### Task 1: Install Spatie Media Library Filament plugin

**Files:**
- Modify: `composer.json` (require block)
- Modify: `composer.lock` (regenerated)

- [ ] **Step 1:** Add the plugin via composer.

Run:
```bash
composer require filament/spatie-laravel-media-library-plugin:"^5.0"
```

Expected: composer installs the plugin without dependency conflicts.

- [ ] **Step 2:** Verify the plugin is loaded.

Run:
```bash
php artisan about | grep -i "media"
```

Expected: see media-library entry. If `php artisan about` does not show plugins explicitly, just confirm `composer show filament/spatie-laravel-media-library-plugin` prints version `^5.0`.

- [ ] **Step 3:** Commit.

```bash
git add composer.json composer.lock
git commit -m "catalogue: install filament spatie media library plugin"
```

---

### Task 2: Add `storage:link` to composer setup script

**Files:**
- Modify: `composer.json` (`scripts.setup` array)

- [ ] **Step 1:** Open `composer.json`, find the `scripts.setup` array (currently lines around `"setup": [...]`), and append `"@php artisan storage:link"` as the final entry.

After change, `scripts.setup` should be:

```json
"setup": [
    "composer install",
    "@php -r \"file_exists('.env') || copy('.env.example', '.env');\"",
    "@php artisan key:generate",
    "@php artisan migrate --force",
    "npm install --ignore-scripts",
    "npm run build",
    "@php artisan storage:link"
]
```

- [ ] **Step 2:** Run the command locally so the symlink exists for the rest of the work.

Run:
```bash
php artisan storage:link
```

Expected: `The [public/storage] link has been connected to [storage/app/public].` (or "exists" if already linked — harmless).

- [ ] **Step 3:** Commit.

```bash
git add composer.json
git commit -m "catalogue: storage:link in composer setup script"
```

---

### Task 3: Create `config/catalogue.php`

**Files:**
- Create: `config/catalogue.php`

- [ ] **Step 1:** Create the file with this exact content.

```php
<?php

return [
    'default_volume_ml' => 50,

    'currency_by_locale' => [
        'uk' => 'UAH',
        'en' => 'EUR',
    ],

    'locales' => ['uk', 'en'],
    'default_locale' => 'uk',
];
```

- [ ] **Step 2:** Verify it is loadable.

Run:
```bash
php artisan tinker --execute="echo config('catalogue.default_volume_ml');"
```

Expected: prints `50`.

- [ ] **Step 3:** Commit.

```bash
git add config/catalogue.php
git commit -m "catalogue: add catalogue config (default volume, currency map)"
```

---

### Task 4: Enable `RefreshDatabase` for Feature tests

**Files:**
- Modify: `tests/Pest.php`

- [ ] **Step 1:** Open `tests/Pest.php`. Find the `pest()->extend(TestCase::class)` block at the top. Uncomment the `->use(RefreshDatabase::class)` line.

Before:
```php
pest()->extend(TestCase::class)
 // ->use(RefreshDatabase::class)
    ->in('Feature');
```

After:
```php
pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');
```

- [ ] **Step 2:** Verify the existing test suite still runs.

Run:
```bash
php artisan test --filter=InfraTest
```

Expected: 3 passing tests.

- [ ] **Step 3:** Commit.

```bash
git add tests/Pest.php
git commit -m "catalogue: enable RefreshDatabase for Feature suite"
```

---

### Task 5: Translation files for catalogue labels

**Files:**
- Create: `lang/uk/catalogue.php`
- Create: `lang/en/catalogue.php`

- [ ] **Step 1:** Create `lang/uk/catalogue.php`.

```php
<?php

return [
    'navigation' => [
        'catalogue' => 'Каталог',
        'attributes' => 'Атрибути',
        'products' => 'Товари',
        'perfume_families' => 'Сімейства',
        'concentrations' => 'Концентрації',
        'series' => 'Серії',
        'notes' => 'Ноти',
        'brands' => 'Бренди',
        'tags' => 'Беджі',
        'seasons' => 'Сезони',
        'occasions' => 'Приводи',
        'audiences' => 'Аудиторія',
    ],
    'product' => [
        'singular' => 'Товар',
        'plural' => 'Товари',
        'tabs' => [
            'main' => 'Основне',
            'description' => 'Опис',
            'aroma' => 'Аромат',
            'inspired_by' => 'Inspired by',
            'marking' => 'Маркування',
            'seo' => 'SEO',
            'images' => 'Зображення',
        ],
        'fields' => [
            'name' => 'Назва',
            'slug' => 'URL',
            'sku' => 'Артикул',
            'tagline' => 'Слоган',
            'description' => 'Опис',
            'gender' => 'Стать',
            'volume_ml' => "Об'єм, мл",
            'is_published' => 'Опубліковано',
            'in_stock' => 'У наявності',
            'published_at' => 'Дата публікації',
            'price_uah' => 'Ціна, грн',
            'price_eur' => 'Ціна, €',
            'perfume_family' => 'Сімейство',
            'concentration' => 'Концентрація',
            'series' => 'Серія',
            'inspired_brand' => 'Бренд (Inspired by)',
            'inspired_perfume_name' => 'Назва оригінального парфуму',
            'notes_top' => 'Верхні ноти',
            'notes_heart' => 'Серцеві ноти',
            'notes_base' => 'Базові ноти',
            'tags' => 'Беджі',
            'seasons' => 'Сезони',
            'occasions' => 'Приводи',
            'audiences' => 'Аудиторія',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'primary_image' => 'Головне зображення',
            'gallery' => 'Галерея',
            'alt' => 'Alt-текст',
        ],
    ],
    'dictionary' => [
        'fields' => [
            'name' => 'Назва',
            'slug' => 'URL',
            'sort_order' => 'Порядок',
            'is_active' => 'Активно',
            'description' => 'Опис',
            'abbreviation' => 'Скорочення',
            'color' => 'Колір',
            'is_featured' => 'Показувати на картці',
            'country' => 'Країна',
            'products_count' => 'Товарів',
        ],
    ],
    'gender' => [
        'male' => 'Чоловіча',
        'female' => 'Жіноча',
        'unisex' => 'Унісекс',
    ],
];
```

- [ ] **Step 2:** Create `lang/en/catalogue.php` with the same keys (English values).

```php
<?php

return [
    'navigation' => [
        'catalogue' => 'Catalogue',
        'attributes' => 'Attributes',
        'products' => 'Products',
        'perfume_families' => 'Families',
        'concentrations' => 'Concentrations',
        'series' => 'Series',
        'notes' => 'Notes',
        'brands' => 'Brands',
        'tags' => 'Badges',
        'seasons' => 'Seasons',
        'occasions' => 'Occasions',
        'audiences' => 'Audiences',
    ],
    'product' => [
        'singular' => 'Product',
        'plural' => 'Products',
        'tabs' => [
            'main' => 'Main',
            'description' => 'Description',
            'aroma' => 'Aroma',
            'inspired_by' => 'Inspired by',
            'marking' => 'Marking',
            'seo' => 'SEO',
            'images' => 'Images',
        ],
        'fields' => [
            'name' => 'Name',
            'slug' => 'URL slug',
            'sku' => 'SKU',
            'tagline' => 'Tagline',
            'description' => 'Description',
            'gender' => 'Gender',
            'volume_ml' => 'Volume, ml',
            'is_published' => 'Published',
            'in_stock' => 'In stock',
            'published_at' => 'Published at',
            'price_uah' => 'Price, UAH',
            'price_eur' => 'Price, EUR',
            'perfume_family' => 'Family',
            'concentration' => 'Concentration',
            'series' => 'Series',
            'inspired_brand' => 'Inspired brand',
            'inspired_perfume_name' => 'Original perfume name',
            'notes_top' => 'Top notes',
            'notes_heart' => 'Heart notes',
            'notes_base' => 'Base notes',
            'tags' => 'Badges',
            'seasons' => 'Seasons',
            'occasions' => 'Occasions',
            'audiences' => 'Audiences',
            'seo_title' => 'SEO Title',
            'seo_description' => 'SEO Description',
            'primary_image' => 'Primary image',
            'gallery' => 'Gallery',
            'alt' => 'Alt text',
        ],
    ],
    'dictionary' => [
        'fields' => [
            'name' => 'Name',
            'slug' => 'URL slug',
            'sort_order' => 'Sort order',
            'is_active' => 'Active',
            'description' => 'Description',
            'abbreviation' => 'Abbreviation',
            'color' => 'Color',
            'is_featured' => 'Show on card',
            'country' => 'Country',
            'products_count' => 'Products',
        ],
    ],
    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
        'unisex' => 'Unisex',
    ],
];
```

- [ ] **Step 3:** Verify both files load.

Run:
```bash
php artisan tinker --execute="echo trans('catalogue.product.singular', [], 'uk');"
php artisan tinker --execute="echo trans('catalogue.product.singular', [], 'en');"
```

Expected: prints `Товар` then `Product`.

- [ ] **Step 4:** Commit.

```bash
git add lang/uk/catalogue.php lang/en/catalogue.php
git commit -m "catalogue: lang files for navigation and field labels"
```

---

## Phase 2 — Enums (shared types)

### Task 6: Enums for Gender and NoteLevel

**Files:**
- Create: `app/Enums/Gender.php`
- Create: `app/Enums/NoteLevel.php`

- [ ] **Step 1:** Create `app/Enums/Gender.php`.

```php
<?php

namespace App\Enums;

enum Gender: string
{
    case Male = 'male';
    case Female = 'female';
    case Unisex = 'unisex';

    public function label(): string
    {
        return trans("catalogue.gender.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $g) => [$g->value => $g->label()])
            ->all();
    }
}
```

- [ ] **Step 2:** Create `app/Enums/NoteLevel.php`.

```php
<?php

namespace App\Enums;

enum NoteLevel: string
{
    case Top = 'top';
    case Heart = 'heart';
    case Base = 'base';

    public function label(): string
    {
        return trans("catalogue.product.fields.notes_{$this->value}");
    }
}
```

- [ ] **Step 3:** Sanity-check both via tinker.

Run:
```bash
php artisan tinker --execute="dump(App\Enums\Gender::cases()); dump(App\Enums\NoteLevel::cases());"
```

Expected: prints arrays of 3 enum cases each.

- [ ] **Step 4:** Commit.

```bash
git add app/Enums
git commit -m "catalogue: Gender and NoteLevel enums"
```

---

## Phase 3 — Dictionary models

### Task 7: Create base-shape dictionaries (PerfumeFamily, Series, Season, Occasion, Audience)

These five dictionaries share exactly the same schema: `id, name (translatable), slug, sort_order, is_active, timestamps`.

**Files (created via `make:model -mf`):**
- Create: `app/Models/Catalogue/PerfumeFamily.php`
- Create: `app/Models/Catalogue/Series.php`
- Create: `app/Models/Catalogue/Season.php`
- Create: `app/Models/Catalogue/Occasion.php`
- Create: `app/Models/Catalogue/Audience.php`
- Create: 5 migration files in `database/migrations/`
- Create: 5 factory files in `database/factories/Catalogue/`

- [ ] **Step 1:** Generate scaffolding for all five.

Run:
```bash
php artisan make:model Catalogue/PerfumeFamily -mf
php artisan make:model Catalogue/Series -mf
php artisan make:model Catalogue/Season -mf
php artisan make:model Catalogue/Occasion -mf
php artisan make:model Catalogue/Audience -mf
```

This creates models in `app/Models/Catalogue/`, factories in `database/factories/Catalogue/` (note: artisan may put factories in `database/factories/` — move them under `database/factories/Catalogue/` and update namespace `Database\Factories\Catalogue`), and migrations in `database/migrations/`.

- [ ] **Step 2:** Edit each migration's `up()` to use the base shape. Open `database/migrations/*_create_perfume_families_table.php` and replace `up()`:

```php
public function up(): void
{
    Schema::create('perfume_families', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

Repeat the same body (with the appropriate table name) for: `series`, `seasons`, `occasions`, `audiences`.

- [ ] **Step 3:** Edit each model. Replace the entire body of `app/Models/Catalogue/PerfumeFamily.php` with:

```php
<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\PerfumeFamilyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class PerfumeFamily extends Model
{
    /** @use HasFactory<PerfumeFamilyFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
```

Repeat for the remaining four models, changing only:
- class name (`Series`, `Season`, `Occasion`, `Audience`)
- factory FQCN in `@use HasFactory<...>`

- [ ] **Step 4:** Edit each factory. Replace `database/factories/Catalogue/PerfumeFamilyFactory.php`:

```php
<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<PerfumeFamily>
 */
class PerfumeFamilyFactory extends Factory
{
    protected $model = PerfumeFamily::class;

    public function definition(): array
    {
        $uk = fake('uk_UA')->unique()->word();

        return [
            'name' => ['uk' => $uk, 'en' => Str::title($uk)],
            'slug' => Str::slug($uk).'-'.fake()->unique()->numberBetween(1, 99999),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

Repeat for `SeriesFactory`, `SeasonFactory`, `OccasionFactory`, `AudienceFactory`, swapping the class and model names. Keep the body identical.

- [ ] **Step 5:** Run migrations.

Run:
```bash
php artisan migrate
```

Expected: all five migrations report `DONE`.

- [ ] **Step 6:** Write a smoke test confirming the base shape works (translatable + slug uniqueness + factory).

Create `tests/Feature/Catalogue/DictionaryBehaviourTest.php`:

```php
<?php

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Validation\ValidationException;

it('creates a base-shape dictionary record with translatable name', function () {
    $family = PerfumeFamily::create([
        'name' => ['uk' => 'Квіткове', 'en' => 'Floral'],
        'slug' => 'kvitkove',
    ]);

    app()->setLocale('uk');
    expect($family->fresh()->name)->toBe('Квіткове');

    app()->setLocale('en');
    expect($family->fresh()->name)->toBe('Floral');
});

it('enforces unique slug on base dictionaries', function () {
    PerfumeFamily::create(['name' => ['uk' => 'A'], 'slug' => 'dup']);

    expect(fn () => PerfumeFamily::create(['name' => ['uk' => 'B'], 'slug' => 'dup']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('boots through a factory', function () {
    $f = PerfumeFamily::factory()->create();
    expect($f->exists)->toBeTrue();
    expect($f->slug)->toBeString();
});
```

- [ ] **Step 7:** Run the test.

Run:
```bash
php artisan test --filter=DictionaryBehaviourTest
```

Expected: 3 passing tests.

- [ ] **Step 8:** Commit.

```bash
git add app/Models/Catalogue database/factories/Catalogue database/migrations tests/Feature/Catalogue/DictionaryBehaviourTest.php
git commit -m "catalogue: base-shape dictionaries (family, series, season, occasion, audience)"
```

---

### Task 8: Concentration dictionary (with `abbreviation`)

**Files:**
- Create: `app/Models/Catalogue/Concentration.php`
- Create: `database/migrations/*_create_concentrations_table.php`
- Create: `database/factories/Catalogue/ConcentrationFactory.php`

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:model Catalogue/Concentration -mf
```

Move factory to `database/factories/Catalogue/ConcentrationFactory.php` if artisan placed it elsewhere; update namespace to `Database\Factories\Catalogue`.

- [ ] **Step 2:** Migration `up()`:

```php
public function up(): void
{
    Schema::create('concentrations', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->string('abbreviation');
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

- [ ] **Step 3:** Model:

```php
<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\ConcentrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Concentration extends Model
{
    /** @use HasFactory<ConcentrationFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'abbreviation', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 4:** Factory:

```php
<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Concentration;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Concentration>
 */
class ConcentrationFactory extends Factory
{
    protected $model = Concentration::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        $abbr = strtoupper(Str::substr($name, 0, 3));

        return [
            'name' => ['uk' => "EDP $name", 'en' => "EDP $name"],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'abbreviation' => $abbr,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 5:** Run migration.

```bash
php artisan migrate
```

Expected: `concentrations` table created.

- [ ] **Step 6:** Smoke test it.

Add to `tests/Feature/Catalogue/DictionaryBehaviourTest.php`:

```php
it('creates a concentration with abbreviation', function () {
    $c = App\Models\Catalogue\Concentration::factory()->create(['abbreviation' => 'EDP']);
    expect($c->abbreviation)->toBe('EDP');
});
```

Run:
```bash
php artisan test --filter=DictionaryBehaviourTest
```

Expected: 4 passing tests.

- [ ] **Step 7:** Commit.

```bash
git add app/Models/Catalogue/Concentration.php database/factories/Catalogue/ConcentrationFactory.php database/migrations/*_create_concentrations_table.php tests/Feature/Catalogue/DictionaryBehaviourTest.php
git commit -m "catalogue: Concentration dictionary with abbreviation"
```

---

### Task 9: Brand dictionary (with `country`)

**Files:**
- Create: `app/Models/Catalogue/Brand.php`
- Create: migration `*_create_brands_table.php`
- Create: `database/factories/Catalogue/BrandFactory.php`

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:model Catalogue/Brand -mf
```

Move factory to `database/factories/Catalogue/`, fix namespace.

- [ ] **Step 2:** Migration `up()`:

```php
public function up(): void
{
    Schema::create('brands', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->string('country')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

- [ ] **Step 3:** Model:

```php
<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\BrandFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Brand extends Model
{
    /** @use HasFactory<BrandFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'country', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 4:** Factory:

```php
<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Brand;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Brand>
 */
class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'country' => fake()->countryCode(),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 5:** Migrate and smoke-test.

```bash
php artisan migrate
```

Add to `DictionaryBehaviourTest.php`:

```php
it('creates a brand with country', function () {
    $b = App\Models\Catalogue\Brand::factory()->create(['country' => 'FR']);
    expect($b->country)->toBe('FR');
});
```

Run:
```bash
php artisan test --filter=DictionaryBehaviourTest
```

Expected: 5 passing tests.

- [ ] **Step 6:** Commit.

```bash
git add app/Models/Catalogue/Brand.php database/factories/Catalogue/BrandFactory.php database/migrations/*_create_brands_table.php tests/Feature/Catalogue/DictionaryBehaviourTest.php
git commit -m "catalogue: Brand dictionary with country"
```

---

### Task 10: Tag dictionary (with `color`, `is_featured`)

**Files:**
- Create: `app/Models/Catalogue/Tag.php`
- Create: migration `*_create_tags_table.php`
- Create: `database/factories/Catalogue/TagFactory.php`

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:model Catalogue/Tag -mf
```

Move factory under `Catalogue/`, fix namespace.

- [ ] **Step 2:** Migration `up()`:

```php
public function up(): void
{
    Schema::create('tags', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->string('color', 7);
        $table->boolean('is_featured')->default(false);
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

- [ ] **Step 3:** Model:

```php
<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\TagFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'color', 'is_featured', 'sort_order', 'is_active'];

    public array $translatable = ['name'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 4:** Factory:

```php
<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Tag;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tag>
 */
class TagFactory extends Factory
{
    protected $model = Tag::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'color' => fake()->hexColor(),
            'is_featured' => fake()->boolean(50),
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 5:** Migrate and smoke-test.

```bash
php artisan migrate
```

Add to `DictionaryBehaviourTest.php`:

```php
it('creates a tag with color and is_featured', function () {
    $t = App\Models\Catalogue\Tag::factory()->create(['color' => '#C77B7B', 'is_featured' => true]);
    expect($t->color)->toBe('#C77B7B');
    expect($t->is_featured)->toBeTrue();
});
```

Run:
```bash
php artisan test --filter=DictionaryBehaviourTest
```

Expected: 6 passing tests.

- [ ] **Step 6:** Commit.

```bash
git add app/Models/Catalogue/Tag.php database/factories/Catalogue/TagFactory.php database/migrations/*_create_tags_table.php tests/Feature/Catalogue/DictionaryBehaviourTest.php
git commit -m "catalogue: Tag dictionary with color and featured flag"
```

---

### Task 11: Note dictionary (with translatable `description`)

**Files:**
- Create: `app/Models/Catalogue/Note.php`
- Create: migration `*_create_notes_table.php`
- Create: `database/factories/Catalogue/NoteFactory.php`

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:model Catalogue/Note -mf
```

Move factory under `Catalogue/`, fix namespace.

- [ ] **Step 2:** Migration `up()`:

```php
public function up(): void
{
    Schema::create('notes', function (Blueprint $table) {
        $table->id();
        $table->json('name');
        $table->string('slug')->unique();
        $table->json('description')->nullable();
        $table->integer('sort_order')->default(0);
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}
```

- [ ] **Step 3:** Model:

```php
<?php

namespace App\Models\Catalogue;

use Database\Factories\Catalogue\NoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class Note extends Model
{
    /** @use HasFactory<NoteFactory> */
    use HasFactory;
    use HasTranslations;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active'];

    public array $translatable = ['name', 'description'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
```

- [ ] **Step 4:** Factory:

```php
<?php

namespace Database\Factories\Catalogue;

use App\Models\Catalogue\Note;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ['uk' => $name, 'en' => $name],
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => null,
            'sort_order' => 0,
            'is_active' => true,
        ];
    }
}
```

- [ ] **Step 5:** Migrate and smoke-test.

```bash
php artisan migrate
```

Add to `DictionaryBehaviourTest.php`:

```php
it('creates a note with translatable description', function () {
    $n = App\Models\Catalogue\Note::create([
        'name' => ['uk' => 'Жасмін', 'en' => 'Jasmine'],
        'slug' => 'jasmin',
        'description' => ['uk' => 'Квіткова нота', 'en' => 'Floral note'],
    ]);

    app()->setLocale('en');
    expect($n->fresh()->description)->toBe('Floral note');
});
```

Run:
```bash
php artisan test --filter=DictionaryBehaviourTest
```

Expected: 7 passing tests.

- [ ] **Step 6:** Commit.

```bash
git add app/Models/Catalogue/Note.php database/factories/Catalogue/NoteFactory.php database/migrations/*_create_notes_table.php tests/Feature/Catalogue/DictionaryBehaviourTest.php
git commit -m "catalogue: Note dictionary with translatable description"
```

---

## Phase 4 — Product model

### Task 12: Product migration

**Files:**
- Create: migration `*_create_products_table.php`

- [ ] **Step 1:** Generate migration.

```bash
php artisan make:migration create_products_table
```

- [ ] **Step 2:** Replace migration `up()`:

```php
public function up(): void
{
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('sku')->unique();
        $table->string('slug')->unique();
        $table->json('name');
        $table->json('tagline')->nullable();
        $table->json('description')->nullable();
        $table->string('inspired_perfume_name')->nullable();
        $table->foreignId('inspired_brand_id')->nullable()->constrained('brands')->nullOnDelete();
        $table->unsignedSmallInteger('volume_ml')->default(50);
        $table->string('gender', 16);
        $table->decimal('price_uah', 10, 2);
        $table->decimal('price_eur', 10, 2);
        $table->boolean('in_stock')->default(true);
        $table->boolean('is_published')->default(false);
        $table->timestamp('published_at')->nullable();
        $table->json('seo_title')->nullable();
        $table->json('seo_description')->nullable();
        $table->foreignId('perfume_family_id')->nullable()->constrained('perfume_families')->nullOnDelete();
        $table->foreignId('concentration_id')->nullable()->constrained('concentrations')->nullOnDelete();
        $table->foreignId('series_id')->nullable()->constrained('series')->nullOnDelete();
        $table->timestamps();

        $table->index('is_published');
        $table->index('published_at');
    });
}
```

- [ ] **Step 3:** Migrate.

```bash
php artisan migrate
```

Expected: `products` table created.

- [ ] **Step 4:** Commit.

```bash
git add database/migrations/*_create_products_table.php
git commit -m "catalogue: products table migration"
```

---

### Task 13: Product model + factory

**Files:**
- Create: `app/Models/Catalogue/Product.php`
- Create: `database/factories/Catalogue/ProductFactory.php`

- [ ] **Step 1:** Create `app/Models/Catalogue/Product.php`.

```php
<?php

namespace App\Models\Catalogue;

use App\Enums\Gender;
use Database\Factories\Catalogue\ProductFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Product extends Model implements HasMedia
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'sku', 'slug', 'name', 'tagline', 'description',
        'inspired_perfume_name', 'inspired_brand_id',
        'volume_ml', 'gender',
        'price_uah', 'price_eur',
        'in_stock', 'is_published', 'published_at',
        'seo_title', 'seo_description',
        'perfume_family_id', 'concentration_id', 'series_id',
    ];

    public array $translatable = ['name', 'tagline', 'description', 'seo_title', 'seo_description'];

    protected function casts(): array
    {
        return [
            'in_stock' => 'boolean',
            'is_published' => 'boolean',
            'published_at' => 'datetime',
            'volume_ml' => 'integer',
            'price_uah' => 'decimal:2',
            'price_eur' => 'decimal:2',
            'gender' => Gender::class,
        ];
    }

    public function perfumeFamily(): BelongsTo
    {
        return $this->belongsTo(PerfumeFamily::class);
    }

    public function concentration(): BelongsTo
    {
        return $this->belongsTo(Concentration::class);
    }

    public function series(): BelongsTo
    {
        return $this->belongsTo(Series::class);
    }

    public function inspiredBrand(): BelongsTo
    {
        return $this->belongsTo(Brand::class, 'inspired_brand_id');
    }

    public function displayPrice(?string $locale = null): array
    {
        $locale = $locale ?? app()->getLocale();
        $currency = config("catalogue.currency_by_locale.$locale", 'UAH');

        return match ($currency) {
            'EUR' => ['amount' => $this->price_eur, 'currency' => 'EUR'],
            default => ['amount' => $this->price_uah, 'currency' => 'UAH'],
        };
    }
}
```

Notes:
- The `notes()`, `tags()`, `seasons()`, `occasions()`, `audiences()` relations are added in Tasks 14 and 15 — leave them out for now.
- Media collections are configured in Task 16.

- [ ] **Step 2:** Create `database/factories/Catalogue/ProductFactory.php`.

```php
<?php

namespace Database\Factories\Catalogue;

use App\Enums\Gender;
use App\Models\Catalogue\Brand;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = 'LUXURY '.fake()->unique()->numberBetween(1, 9999);

        return [
            'sku' => 'LV-'.fake()->unique()->numerify('######'),
            'slug' => Str::slug($name),
            'name' => ['uk' => $name, 'en' => $name],
            'tagline' => ['uk' => fake('uk_UA')->sentence(4), 'en' => fake()->sentence(4)],
            'description' => ['uk' => fake('uk_UA')->paragraph(), 'en' => fake()->paragraph()],
            'inspired_perfume_name' => fake()->words(2, true),
            'inspired_brand_id' => Brand::factory(),
            'volume_ml' => 50,
            'gender' => fake()->randomElement(Gender::cases())->value,
            'price_uah' => fake()->randomFloat(2, 500, 5000),
            'price_eur' => fake()->randomFloat(2, 15, 130),
            'in_stock' => true,
            'is_published' => true,
            'published_at' => now(),
            'seo_title' => ['uk' => $name, 'en' => $name],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'perfume_family_id' => PerfumeFamily::factory(),
            'concentration_id' => Concentration::factory(),
            'series_id' => Series::factory(),
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false, 'published_at' => null]);
    }

    public function outOfStock(): static
    {
        return $this->state(['in_stock' => false]);
    }
}
```

- [ ] **Step 3:** Write a model test exercising the basics.

Create `tests/Feature/Catalogue/ProductModelTest.php`:

```php
<?php

use App\Enums\Gender;
use App\Models\Catalogue\Product;

it('creates a product with all belongsTo relations', function () {
    $p = Product::factory()->create();

    expect($p->exists)->toBeTrue();
    expect($p->perfumeFamily)->not->toBeNull();
    expect($p->concentration)->not->toBeNull();
    expect($p->series)->not->toBeNull();
    expect($p->inspiredBrand)->not->toBeNull();
});

it('casts gender to Gender enum', function () {
    $p = Product::factory()->create(['gender' => Gender::Unisex->value]);
    expect($p->fresh()->gender)->toBe(Gender::Unisex);
});

it('keeps name translatable across locales', function () {
    $p = Product::factory()->create(['name' => ['uk' => 'Лакшері', 'en' => 'Luxury']]);

    app()->setLocale('uk');
    expect($p->fresh()->name)->toBe('Лакшері');

    app()->setLocale('en');
    expect($p->fresh()->name)->toBe('Luxury');
});

it('enforces unique sku', function () {
    Product::factory()->create(['sku' => 'DUP-1']);
    expect(fn () => Product::factory()->create(['sku' => 'DUP-1']))
        ->toThrow(Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 4:** Run the test.

```bash
php artisan test --filter=ProductModelTest
```

Expected: 4 passing tests.

- [ ] **Step 5:** Commit.

```bash
git add app/Models/Catalogue/Product.php database/factories/Catalogue/ProductFactory.php tests/Feature/Catalogue/ProductModelTest.php
git commit -m "catalogue: Product model with belongsTo relations and translatables"
```

---

### Task 14: `product_note` pivot and `notes()` relation with `level`

**Files:**
- Create: migration `*_create_product_note_table.php`
- Modify: `app/Models/Catalogue/Product.php`
- Modify: `tests/Feature/Catalogue/ProductModelTest.php`

- [ ] **Step 1:** Generate migration.

```bash
php artisan make:migration create_product_note_table
```

- [ ] **Step 2:** Migration `up()`:

```php
public function up(): void
{
    Schema::create('product_note', function (Blueprint $table) {
        $table->id();
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('note_id')->constrained()->cascadeOnDelete();
        $table->string('level', 8);
        $table->integer('sort_order')->default(0);
        $table->timestamps();

        $table->unique(['product_id', 'note_id', 'level']);
    });
}
```

- [ ] **Step 3:** Add `notes()` relation to `Product`. Append before `displayPrice()`:

```php
use App\Enums\NoteLevel;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

// ...

public function notes(): BelongsToMany
{
    return $this->belongsToMany(Note::class)
        ->withPivot(['level', 'sort_order'])
        ->withTimestamps()
        ->orderByPivot('sort_order');
}

public function notesByLevel(NoteLevel $level): BelongsToMany
{
    return $this->notes()->wherePivot('level', $level->value);
}
```

(Add the `use` statements at the top of the file.)

- [ ] **Step 4:** Migrate.

```bash
php artisan migrate
```

- [ ] **Step 5:** Test the relation.

Append to `ProductModelTest.php`:

```php
use App\Enums\NoteLevel;
use App\Models\Catalogue\Note;

it('attaches notes at different levels', function () {
    $p = Product::factory()->create();
    $jasmine = Note::factory()->create(['name' => ['uk' => 'Жасмін', 'en' => 'Jasmine'], 'slug' => 'jasmine']);
    $musk = Note::factory()->create(['name' => ['uk' => 'Мускус', 'en' => 'Musk'], 'slug' => 'musk']);

    $p->notes()->attach($jasmine->id, ['level' => NoteLevel::Heart->value, 'sort_order' => 0]);
    $p->notes()->attach($jasmine->id, ['level' => NoteLevel::Base->value, 'sort_order' => 0]); // same note, different level — allowed
    $p->notes()->attach($musk->id, ['level' => NoteLevel::Base->value, 'sort_order' => 1]);

    expect($p->notes()->count())->toBe(3);
    expect($p->notesByLevel(NoteLevel::Base)->count())->toBe(2);
    expect($p->notesByLevel(NoteLevel::Heart)->first()->slug)->toBe('jasmine');
});

it('rejects duplicate note at same level', function () {
    $p = Product::factory()->create();
    $n = Note::factory()->create();

    $p->notes()->attach($n->id, ['level' => NoteLevel::Top->value, 'sort_order' => 0]);

    expect(fn () => $p->notes()->attach($n->id, ['level' => NoteLevel::Top->value, 'sort_order' => 1]))
        ->toThrow(Illuminate\Database\QueryException::class);
});
```

- [ ] **Step 6:** Run the test.

```bash
php artisan test --filter=ProductModelTest
```

Expected: 6 passing tests.

- [ ] **Step 7:** Commit.

```bash
git add database/migrations/*_create_product_note_table.php app/Models/Catalogue/Product.php tests/Feature/Catalogue/ProductModelTest.php
git commit -m "catalogue: product_note pivot with level + notesByLevel()"
```

---

### Task 15: Simple pivots — tags, seasons, occasions, audiences

**Files:**
- Create: 4 migrations
- Modify: `app/Models/Catalogue/Product.php`

- [ ] **Step 1:** Generate the four migrations.

```bash
php artisan make:migration create_product_tag_table
php artisan make:migration create_product_season_table
php artisan make:migration create_product_occasion_table
php artisan make:migration create_product_audience_table
```

- [ ] **Step 2:** For each migration, use this template (substituting `<dict>` and the referenced table name). Example for `product_tag`:

```php
public function up(): void
{
    Schema::create('product_tag', function (Blueprint $table) {
        $table->foreignId('product_id')->constrained()->cascadeOnDelete();
        $table->foreignId('tag_id')->constrained()->cascadeOnDelete();
        $table->primary(['product_id', 'tag_id']);
    });
}
```

Repeat with `(product_season, season_id, seasons)`, `(product_occasion, occasion_id, occasions)`, `(product_audience, audience_id, audiences)`.

- [ ] **Step 3:** Add four relations to `Product` (append below `notesByLevel()`):

```php
public function tags(): BelongsToMany
{
    return $this->belongsToMany(Tag::class);
}

public function seasons(): BelongsToMany
{
    return $this->belongsToMany(Season::class);
}

public function occasions(): BelongsToMany
{
    return $this->belongsToMany(Occasion::class);
}

public function audiences(): BelongsToMany
{
    return $this->belongsToMany(Audience::class);
}
```

- [ ] **Step 4:** Migrate.

```bash
php artisan migrate
```

- [ ] **Step 5:** Test the relations.

Append to `ProductModelTest.php`:

```php
use App\Models\Catalogue\Audience;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Tag;

it('attaches simple many-to-many relations', function () {
    $p = Product::factory()->create();

    $tag = Tag::factory()->create();
    $season = Season::factory()->create();
    $occasion = Occasion::factory()->create();
    $audience = Audience::factory()->create();

    $p->tags()->attach($tag);
    $p->seasons()->attach($season);
    $p->occasions()->attach($occasion);
    $p->audiences()->attach($audience);

    expect($p->tags)->toHaveCount(1);
    expect($p->seasons)->toHaveCount(1);
    expect($p->occasions)->toHaveCount(1);
    expect($p->audiences)->toHaveCount(1);
});
```

- [ ] **Step 6:** Run.

```bash
php artisan test --filter=ProductModelTest
```

Expected: 7 passing tests.

- [ ] **Step 7:** Commit.

```bash
git add database/migrations/*_create_product_tag_table.php database/migrations/*_create_product_season_table.php database/migrations/*_create_product_occasion_table.php database/migrations/*_create_product_audience_table.php app/Models/Catalogue/Product.php tests/Feature/Catalogue/ProductModelTest.php
git commit -m "catalogue: simple pivots for tags/seasons/occasions/audiences"
```

---

### Task 16: Product media collections (primary, gallery) + conversions

**Files:**
- Modify: `app/Models/Catalogue/Product.php`
- Create: `tests/Feature/Catalogue/ProductMediaTest.php`

- [ ] **Step 1:** Add media-collection registration to `Product`. Add this method:

```php
use Spatie\MediaLibrary\MediaCollections\File as MediaFile;

// ...

public function registerMediaCollections(): void
{
    $this
        ->addMediaCollection('primary')
        ->singleFile()
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

    $this
        ->addMediaCollection('gallery')
        ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
}

public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->fit(\Spatie\Image\Enums\Fit::Contain, 200, 200)
        ->format('webp')
        ->nonQueued();

    $this->addMediaConversion('card')
        ->fit(\Spatie\Image\Enums\Fit::Crop, 600, 800)
        ->format('webp')
        ->nonQueued();

    $this->addMediaConversion('detail')
        ->fit(\Spatie\Image\Enums\Fit::Contain, 1200, 1600)
        ->format('webp')
        ->nonQueued();
}
```

(Conversions are non-queued so tests don't need to mock the queue. The default queue setting in `media-library.php` can override this if we later want async.)

- [ ] **Step 2:** Test media upload.

Create `tests/Feature/Catalogue/ProductMediaTest.php`:

```php
<?php

use App\Models\Catalogue\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('adds a primary image and replaces it', function () {
    $p = Product::factory()->create();

    $p->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('primary');
    expect($p->getMedia('primary'))->toHaveCount(1);

    $p->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('primary');
    expect($p->getMedia('primary'))->toHaveCount(1); // singleFile replaces
});

it('adds multiple gallery images preserving order', function () {
    $p = Product::factory()->create();

    $p->addMedia(UploadedFile::fake()->image('1.jpg'))->toMediaCollection('gallery');
    $p->addMedia(UploadedFile::fake()->image('2.jpg'))->toMediaCollection('gallery');
    $p->addMedia(UploadedFile::fake()->image('3.jpg'))->toMediaCollection('gallery');

    expect($p->getMedia('gallery'))->toHaveCount(3);
});

it('stores translatable alt text in custom properties', function () {
    $p = Product::factory()->create();
    $media = $p->addMedia(UploadedFile::fake()->image('a.jpg'))
        ->withCustomProperties(['alt' => ['uk' => 'Флакон', 'en' => 'Bottle']])
        ->toMediaCollection('primary');

    expect($media->getCustomProperty('alt.uk'))->toBe('Флакон');
    expect($media->getCustomProperty('alt.en'))->toBe('Bottle');
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductMediaTest
```

Expected: 3 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Models/Catalogue/Product.php tests/Feature/Catalogue/ProductMediaTest.php
git commit -m "catalogue: Product media collections (primary, gallery) with WebP conversions"
```

---

### Task 17: `displayPrice()` localisation test

**Files:**
- Create: `tests/Feature/Catalogue/ProductPriceLocalizationTest.php`

The helper is already implemented in `Product` (Task 13). This task just adds the locale test.

- [ ] **Step 1:** Create the test.

```php
<?php

use App\Models\Catalogue\Product;

it('returns UAH price for uk locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    app()->setLocale('uk');
    $price = $p->displayPrice();

    expect($price['amount'])->toBe('1290.00');
    expect($price['currency'])->toBe('UAH');
});

it('returns EUR price for en locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    app()->setLocale('en');
    $price = $p->displayPrice();

    expect($price['amount'])->toBe('35.00');
    expect($price['currency'])->toBe('EUR');
});

it('falls back to UAH for unknown locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    $price = $p->displayPrice('xx');

    expect($price['currency'])->toBe('UAH');
});
```

- [ ] **Step 2:** Run.

```bash
php artisan test --filter=ProductPriceLocalizationTest
```

Expected: 3 passing tests.

- [ ] **Step 3:** Commit.

```bash
git add tests/Feature/Catalogue/ProductPriceLocalizationTest.php
git commit -m "catalogue: tests for displayPrice() locale behaviour"
```

---

## Phase 5 — Filament admin (dictionaries)

### Task 18: Configure AdminPanelProvider — navigation groups

**Files:**
- Modify: `app/Providers/Filament/AdminPanelProvider.php`

- [ ] **Step 1:** Add navigation groups configuration. Inside the `panel()` method, before `->plugins([...])`, add:

```php
->navigationGroups([
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('catalogue.navigation.catalogue')),
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('catalogue.navigation.attributes'))
        ->collapsed(),
])
```

Final structure (showing only the changes — keep the rest as-is):

```php
return $panel
    // ... existing config ...
    ->navigationGroups([
        \Filament\Navigation\NavigationGroup::make()
            ->label(fn () => trans('catalogue.navigation.catalogue')),
        \Filament\Navigation\NavigationGroup::make()
            ->label(fn () => trans('catalogue.navigation.attributes'))
            ->collapsed(),
    ])
    ->plugins([
        SpatieTranslatablePlugin::make()
            ->defaultLocales(['uk', 'en']),
    ]);
```

- [ ] **Step 2:** Run the existing tests to confirm nothing breaks.

```bash
php artisan test --filter=InfraTest
```

Expected: 3 passing tests, including the Filament login page test.

- [ ] **Step 3:** Commit.

```bash
git add app/Providers/Filament/AdminPanelProvider.php
git commit -m "catalogue: navigation groups Catalogue + Attributes in admin panel"
```

---

### Task 19: PerfumeFamily Filament resource (canonical base-shape resource)

**Files (generated by artisan, then edited):**
- Create: `app/Filament/Resources/PerfumeFamilies/PerfumeFamilyResource.php`
- Create: `app/Filament/Resources/PerfumeFamilies/Schemas/PerfumeFamilyForm.php`
- Create: `app/Filament/Resources/PerfumeFamilies/Tables/PerfumeFamiliesTable.php`
- Create: `app/Filament/Resources/PerfumeFamilies/Pages/{Create,Edit,List}PerfumeFamily.php`

- [ ] **Step 1:** Generate the resource.

```bash
php artisan make:filament-resource Catalogue/PerfumeFamily --generate --view=0
```

If the artisan command does not accept the namespace prefix, run `php artisan make:filament-resource PerfumeFamily --generate` and edit the namespaces afterwards.

- [ ] **Step 2:** Open `PerfumeFamilyResource.php`. Replace the body to:
  - Bind to the right model (`App\Models\Catalogue\PerfumeFamily`)
  - Set navigation group to the "Attributes" group
  - Set labels to translated strings

```php
<?php

namespace App\Filament\Resources\PerfumeFamilies;

use App\Filament\Resources\PerfumeFamilies\Pages\CreatePerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\EditPerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\ListPerfumeFamilies;
use App\Filament\Resources\PerfumeFamilies\Schemas\PerfumeFamilyForm;
use App\Filament\Resources\PerfumeFamilies\Tables\PerfumeFamiliesTable;
use App\Models\Catalogue\PerfumeFamily;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class PerfumeFamilyResource extends Resource
{
    protected static ?string $model = PerfumeFamily::class;

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.attributes');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.perfume_families');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.navigation.perfume_families');
    }

    public static function form(Schema $schema): Schema
    {
        return PerfumeFamilyForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PerfumeFamiliesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPerfumeFamilies::route('/'),
            'create' => CreatePerfumeFamily::route('/create'),
            'edit' => EditPerfumeFamily::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 3:** Open `Schemas/PerfumeFamilyForm.php`. Replace with the base-shape form:

```php
<?php

namespace App\Filament\Resources\PerfumeFamilies\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class PerfumeFamilyForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(fn () => trans('catalogue.dictionary.fields.name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (! $get('slug')) {
                            $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label(fn () => trans('catalogue.dictionary.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('sort_order')
                    ->label(fn () => trans('catalogue.dictionary.fields.sort_order'))
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label(fn () => trans('catalogue.dictionary.fields.is_active'))
                    ->default(true),
            ]);
    }
}
```

- [ ] **Step 4:** Open `Tables/PerfumeFamiliesTable.php`. Replace with:

```php
<?php

namespace App\Filament\Resources\PerfumeFamilies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class PerfumeFamiliesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('sort_order')->sortable(),
                IconColumn::make('is_active')->boolean(),
                TextColumn::make('products_count')
                    ->counts('products')
                    ->label(fn () => trans('catalogue.dictionary.fields.products_count')),
            ])
            ->defaultSort('sort_order')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
```

Note: the `products_count` column reads a `products()` relation from `PerfumeFamily`. Add it inside the model:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\Catalogue\Product::class);
}
```

- [ ] **Step 5:** Test the resource via Livewire.

Create `tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php`:

```php
<?php

use App\Filament\Resources\PerfumeFamilies\Pages\CreatePerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\ListPerfumeFamilies;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists PerfumeFamily records', function () {
    $records = PerfumeFamily::factory()->count(3)->create();
    livewire(ListPerfumeFamilies::class)
        ->assertOk()
        ->assertCanSeeTableRecords($records);
});

it('creates a PerfumeFamily', function () {
    livewire(CreatePerfumeFamily::class)
        ->fillForm([
            'name' => ['uk' => 'Цитрусове', 'en' => 'Citrus'],
            'slug' => 'citrus',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('perfume_families', ['slug' => 'citrus']);
});
```

- [ ] **Step 6:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 2 passing tests.

- [ ] **Step 7:** Commit.

```bash
git add app/Filament/Resources/PerfumeFamilies app/Models/Catalogue/PerfumeFamily.php tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: PerfumeFamilyResource (canonical base-shape Filament resource)"
```

---

### Task 20: Filament resources for Series, Season, Occasion, Audience

These four dictionaries share the same form/table as PerfumeFamily. Repeat the same scaffolding pattern for each.

**Files (per dictionary):**
- Create: `app/Filament/Resources/<Plural>/<Singular>Resource.php`
- Create: `app/Filament/Resources/<Plural>/Schemas/<Singular>Form.php`
- Create: `app/Filament/Resources/<Plural>/Tables/<Plural>Table.php`
- Create: 3 Pages files

- [ ] **Step 1:** Generate scaffolding for all four.

```bash
php artisan make:filament-resource Series --generate
php artisan make:filament-resource Season --generate
php artisan make:filament-resource Occasion --generate
php artisan make:filament-resource Audience --generate
```

- [ ] **Step 2:** For each resource, edit the main `<Singular>Resource.php` so it matches the PerfumeFamily pattern from Task 19 Step 2, substituting:
  - model class (`App\Models\Catalogue\Series` etc.)
  - navigation labels (`catalogue.navigation.series`, `.seasons`, `.occasions`, `.audiences`)

- [ ] **Step 3:** For each resource, set the form Schema identical to Task 19 Step 3 (the base form). Just the namespaces change.

- [ ] **Step 4:** For each resource, set the table identical to Task 19 Step 4 (drop the `products_count` column for Audience since the relation is via pivot — we use `withCount('products')` and add a `products()` `belongsToMany` on Audience):

For the four dictionaries `Series`, `Season`, `Occasion`, `Audience`, append to each model:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\HasMany|\Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    // For Series — HasMany (FK on products)
    // For Season, Occasion, Audience — BelongsToMany (pivot)
}
```

Concretely:

In `Series.php`:
```php
public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\Catalogue\Product::class);
}
```

In `Season.php`, `Occasion.php`, `Audience.php`:
```php
public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(\App\Models\Catalogue\Product::class);
}
```

- [ ] **Step 5:** Add tests for all four to `DictionaryResourcesTest.php`. Use the `dataset()` helper:

```php
use App\Filament\Resources\Series\Pages\CreateSeries;
use App\Filament\Resources\Seasons\Pages\CreateSeason;
use App\Filament\Resources\Occasions\Pages\CreateOccasion;
use App\Filament\Resources\Audiences\Pages\CreateAudience;
use App\Models\Catalogue\Series as SeriesModel;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\Audience;

it('creates a base-shape dictionary record via Filament', function (string $createPage, string $model, string $slug) {
    livewire($createPage)
        ->fillForm([
            'name' => ['uk' => 'Тест', 'en' => 'Test'],
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas((new $model)->getTable(), ['slug' => $slug]);
})->with([
    'series'    => [CreateSeries::class, SeriesModel::class, 'series-x'],
    'season'    => [CreateSeason::class, Season::class, 'season-x'],
    'occasion'  => [CreateOccasion::class, Occasion::class, 'occasion-x'],
    'audience'  => [CreateAudience::class, Audience::class, 'audience-x'],
]);
```

- [ ] **Step 6:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 6 passing tests (2 from Task 19 + 4 from the dataset).

- [ ] **Step 7:** Commit.

```bash
git add app/Filament/Resources/Series app/Filament/Resources/Seasons app/Filament/Resources/Occasions app/Filament/Resources/Audiences app/Models/Catalogue tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: Filament resources for Series, Season, Occasion, Audience"
```

---

### Task 21: ConcentrationResource (with `abbreviation`)

**Files:**
- Create: `app/Filament/Resources/Concentrations/...` (resource + form + table + pages)
- Modify: `app/Models/Catalogue/Concentration.php` (add `products()`)

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:filament-resource Concentration --generate
```

- [ ] **Step 2:** Edit `ConcentrationResource.php` like Task 19 Step 2, with model = `App\Models\Catalogue\Concentration`, navigation label = `catalogue.navigation.concentrations`.

- [ ] **Step 3:** `Schemas/ConcentrationForm.php`:

```php
<?php

namespace App\Filament\Resources\Concentrations\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ConcentrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label(fn () => trans('catalogue.dictionary.fields.name'))
                    ->required()
                    ->live(onBlur: true)
                    ->afterStateUpdated(function ($state, callable $get, callable $set) {
                        if (! $get('slug')) {
                            $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                        }
                    }),
                TextInput::make('slug')
                    ->label(fn () => trans('catalogue.dictionary.fields.slug'))
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('abbreviation')
                    ->label(fn () => trans('catalogue.dictionary.fields.abbreviation'))
                    ->required()
                    ->maxLength(16),
                TextInput::make('sort_order')
                    ->label(fn () => trans('catalogue.dictionary.fields.sort_order'))
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->label(fn () => trans('catalogue.dictionary.fields.is_active'))
                    ->default(true),
            ]);
    }
}
```

- [ ] **Step 4:** `Tables/ConcentrationsTable.php` — copy the base table from Task 19 Step 4 and add `abbreviation` column:

```php
TextColumn::make('abbreviation')
    ->label(fn () => trans('catalogue.dictionary.fields.abbreviation')),
```

Insert before `is_active` column.

- [ ] **Step 5:** Add `products()` to `Concentration` model:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\Catalogue\Product::class);
}
```

- [ ] **Step 6:** Append test to `DictionaryResourcesTest.php`:

```php
use App\Filament\Resources\Concentrations\Pages\CreateConcentration;
use App\Models\Catalogue\Concentration;

it('creates a Concentration with abbreviation via Filament', function () {
    livewire(CreateConcentration::class)
        ->fillForm([
            'name' => ['uk' => 'Парфум', 'en' => 'Parfum'],
            'slug' => 'parfum',
            'abbreviation' => 'PARF',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('concentrations', ['slug' => 'parfum', 'abbreviation' => 'PARF']);
});
```

- [ ] **Step 7:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 7 passing tests.

- [ ] **Step 8:** Commit.

```bash
git add app/Filament/Resources/Concentrations app/Models/Catalogue/Concentration.php tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: ConcentrationResource with abbreviation field"
```

---

### Task 22: BrandResource (with `country`)

**Files:**
- Create: `app/Filament/Resources/Brands/...`
- Modify: `app/Models/Catalogue/Brand.php` (add `products()` for inspired-by reverse relation)

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:filament-resource Brand --generate
```

- [ ] **Step 2:** Resource class (same pattern as Task 19 Step 2; model = `Brand`, label = `catalogue.navigation.brands`).

- [ ] **Step 3:** Form Schema — base form + `country`:

```php
TextInput::make('country')
    ->label(fn () => trans('catalogue.dictionary.fields.country'))
    ->maxLength(2),
```

Insert after the `slug` field. Other fields are identical to PerfumeFamilyForm (Task 19 Step 3).

- [ ] **Step 4:** Table — base table + `country` column.

```php
TextColumn::make('country'),
```

Insert before `sort_order`.

- [ ] **Step 5:** Add `products()` reverse relation to `Brand`:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\HasMany
{
    return $this->hasMany(\App\Models\Catalogue\Product::class, 'inspired_brand_id');
}
```

- [ ] **Step 6:** Append test to `DictionaryResourcesTest.php`:

```php
use App\Filament\Resources\Brands\Pages\CreateBrand;

it('creates a Brand with country via Filament', function () {
    livewire(CreateBrand::class)
        ->fillForm([
            'name' => ['uk' => 'Бренд', 'en' => 'Brand'],
            'slug' => 'brand-x',
            'country' => 'FR',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('brands', ['slug' => 'brand-x', 'country' => 'FR']);
});
```

- [ ] **Step 7:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 8 passing tests.

- [ ] **Step 8:** Commit.

```bash
git add app/Filament/Resources/Brands app/Models/Catalogue/Brand.php tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: BrandResource with country field"
```

---

### Task 23: TagResource (with `color`, `is_featured`)

**Files:**
- Create: `app/Filament/Resources/Tags/...`
- Modify: `app/Models/Catalogue/Tag.php` (add `products()` belongsToMany)

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:filament-resource Tag --generate
```

- [ ] **Step 2:** Resource class (same pattern; model = `Tag`, label = `catalogue.navigation.tags`).

- [ ] **Step 3:** Form Schema — base form + `color` (ColorPicker) and `is_featured` (Toggle):

```php
use Filament\Forms\Components\ColorPicker;

// inside ->components([...]):
ColorPicker::make('color')
    ->label(fn () => trans('catalogue.dictionary.fields.color'))
    ->required(),
Toggle::make('is_featured')
    ->label(fn () => trans('catalogue.dictionary.fields.is_featured')),
```

Insert after `slug`.

- [ ] **Step 4:** Table — add `color` (ColorColumn) and `is_featured` (IconColumn):

```php
use Filament\Tables\Columns\ColorColumn;

// inside columns:
ColorColumn::make('color'),
IconColumn::make('is_featured')->boolean(),
```

Insert before `sort_order`.

- [ ] **Step 5:** Add `products()` relation to `Tag`:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(\App\Models\Catalogue\Product::class);
}
```

- [ ] **Step 6:** Append test:

```php
use App\Filament\Resources\Tags\Pages\CreateTag;

it('creates a Tag with color and featured flag via Filament', function () {
    livewire(CreateTag::class)
        ->fillForm([
            'name' => ['uk' => 'Бестселер', 'en' => 'Bestseller'],
            'slug' => 'bestseller',
            'color' => '#C77B7B',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('tags', ['slug' => 'bestseller', 'color' => '#C77B7B', 'is_featured' => true]);
});
```

- [ ] **Step 7:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 9 passing tests.

- [ ] **Step 8:** Commit.

```bash
git add app/Filament/Resources/Tags app/Models/Catalogue/Tag.php tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: TagResource with color and featured flag"
```

---

### Task 24: NoteResource (with translatable `description`)

**Files:**
- Create: `app/Filament/Resources/Notes/...`
- Modify: `app/Models/Catalogue/Note.php` (add `products()` belongsToMany)

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:filament-resource Note --generate
```

- [ ] **Step 2:** Resource class (same pattern; model = `Note`, label = `catalogue.navigation.notes`).

- [ ] **Step 3:** Form Schema — base form + `description` (Textarea, translatable):

```php
use Filament\Forms\Components\Textarea;

// inside ->components([...]) after slug:
Textarea::make('description')
    ->label(fn () => trans('catalogue.dictionary.fields.description'))
    ->rows(3),
```

The translatable plugin wraps the field automatically when the model declares it in `$translatable`.

- [ ] **Step 4:** Table — base table is fine. No new columns needed.

- [ ] **Step 5:** Add `products()` to `Note` model:

```php
public function products(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
{
    return $this->belongsToMany(\App\Models\Catalogue\Product::class)
        ->withPivot(['level', 'sort_order'])
        ->withTimestamps();
}
```

- [ ] **Step 6:** Append test:

```php
use App\Filament\Resources\Notes\Pages\CreateNote;

it('creates a Note with translatable description via Filament', function () {
    livewire(CreateNote::class)
        ->fillForm([
            'name' => ['uk' => 'Жасмін', 'en' => 'Jasmine'],
            'slug' => 'jasmine',
            'description' => ['uk' => 'Квіткова', 'en' => 'Floral'],
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('notes', ['slug' => 'jasmine']);
});
```

- [ ] **Step 7:** Run.

```bash
php artisan test --filter=DictionaryResourcesTest
```

Expected: 10 passing tests.

- [ ] **Step 8:** Commit.

```bash
git add app/Filament/Resources/Notes app/Models/Catalogue/Note.php tests/Feature/Catalogue/Filament/DictionaryResourcesTest.php
git commit -m "catalogue: NoteResource with translatable description"
```

---

## Phase 6 — Filament admin (Product)

The ProductResource is broken across multiple tasks, one per major area, so each task fits in a small mental window. We'll build the file incrementally; expect to re-edit `Schemas/ProductForm.php` across tasks.

### Task 25: ProductResource scaffold + "Основне" tab

**Files:**
- Create: `app/Filament/Resources/Products/...` (resource + form + table + pages)

- [ ] **Step 1:** Scaffold.

```bash
php artisan make:filament-resource Product --generate
```

- [ ] **Step 2:** Edit `ProductResource.php`:

```php
<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\EditProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Filament\Resources\Products\Schemas\ProductForm;
use App\Filament\Resources\Products\Tables\ProductsTable;
use App\Models\Catalogue\Product;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getNavigationGroup(): ?string
    {
        return trans('catalogue.navigation.catalogue');
    }

    public static function getNavigationLabel(): string
    {
        return trans('catalogue.navigation.products');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('catalogue.product.plural');
    }

    public static function getModelLabel(): string
    {
        return trans('catalogue.product.singular');
    }

    public static function form(Schema $schema): Schema
    {
        return ProductForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProductsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProducts::route('/'),
            'create' => CreateProduct::route('/create'),
            'edit' => EditProduct::route('/{record}/edit'),
        ];
    }
}
```

- [ ] **Step 3:** Edit `Schemas/ProductForm.php` — start with the "Основне" tab only.

```php
<?php

namespace App\Filament\Resources\Products\Schemas;

use App\Enums\Gender;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('product')
                    ->tabs([
                        Tab::make(trans('catalogue.product.tabs.main'))
                            ->schema(self::mainTab()),
                    ])
                    ->columnSpan(['lg' => 2]),

                Section::make('sidebar')
                    ->schema(self::sidebar())
                    ->columnSpan(['lg' => 1]),
            ])
            ->columns(3);
    }

    protected static function mainTab(): array
    {
        return [
            TextInput::make('name')
                ->label(fn () => trans('catalogue.product.fields.name'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('catalogue.product.fields.slug'))
                ->required()
                ->unique(ignoreRecord: true),
            TextInput::make('sku')
                ->label(fn () => trans('catalogue.product.fields.sku'))
                ->required()
                ->unique(ignoreRecord: true),
            Radio::make('gender')
                ->label(fn () => trans('catalogue.product.fields.gender'))
                ->options(Gender::options())
                ->required()
                ->inline(),
            TextInput::make('volume_ml')
                ->label(fn () => trans('catalogue.product.fields.volume_ml'))
                ->numeric()
                ->default(config('catalogue.default_volume_ml'))
                ->required(),
            Toggle::make('is_published')
                ->label(fn () => trans('catalogue.product.fields.is_published'))
                ->live(),
            DateTimePicker::make('published_at')
                ->label(fn () => trans('catalogue.product.fields.published_at'))
                ->visible(fn (callable $get) => $get('is_published')),
            Toggle::make('in_stock')
                ->label(fn () => trans('catalogue.product.fields.in_stock'))
                ->default(true),
        ];
    }

    protected static function sidebar(): array
    {
        return [
            TextInput::make('price_uah')
                ->label(fn () => trans('catalogue.product.fields.price_uah'))
                ->numeric()
                ->prefix('₴')
                ->required(),
            TextInput::make('price_eur')
                ->label(fn () => trans('catalogue.product.fields.price_eur'))
                ->numeric()
                ->prefix('€')
                ->required(),
        ];
    }
}
```

- [ ] **Step 4:** Edit `Tables/ProductsTable.php` (minimal first cut; expanded in Task 32):

```php
<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('price_uah')->suffix(' ₴'),
                TextColumn::make('price_eur')->suffix(' €'),
                IconColumn::make('is_published')->boolean(),
                IconColumn::make('in_stock')->boolean(),
            ])
            ->defaultSort('id', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([BulkActionGroup::make([DeleteBulkAction::make()])]);
    }
}
```

- [ ] **Step 5:** Create `tests/Feature/Catalogue/Filament/ProductResourceTest.php`:

```php
<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Catalogue\Product;
use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the product list page', function () {
    Product::factory()->count(3)->create();
    livewire(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Product::all());
});

it('renders the create product page with the main tab', function () {
    livewire(CreateProduct::class)->assertOk();
});
```

- [ ] **Step 6:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 2 passing tests.

- [ ] **Step 7:** Commit.

```bash
git add app/Filament/Resources/Products tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductResource scaffold + Основне tab"
```

---

### Task 26: ProductForm — "Опис" tab

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`

- [ ] **Step 1:** Add the "Опис" tab to the Tabs array in `configure()`. Append to the `tabs([...])` array:

```php
Tab::make(trans('catalogue.product.tabs.description'))
    ->schema(self::descriptionTab()),
```

- [ ] **Step 2:** Add the `descriptionTab()` method:

```php
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Textarea;

// ...

protected static function descriptionTab(): array
{
    return [
        Textarea::make('tagline')
            ->label(fn () => trans('catalogue.product.fields.tagline'))
            ->rows(2),
        RichEditor::make('description')
            ->label(fn () => trans('catalogue.product.fields.description')),
    ];
}
```

- [ ] **Step 3:** Append a test to `ProductResourceTest.php`:

```php
it('creates a product with description tab fields', function () {
    $payload = Product::factory()->make()->toArray();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 4', 'en' => 'LUXURY 4'],
            'slug' => 'luxury-4',
            'sku' => 'LV-001',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'tagline' => ['uk' => 'Флоральний наркотик', 'en' => 'Floral narcotic'],
            'description' => ['uk' => '<p>Опис</p>', 'en' => '<p>Description</p>'],
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('products', ['slug' => 'luxury-4', 'sku' => 'LV-001']);
});
```

- [ ] **Step 4:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 3 passing tests.

- [ ] **Step 5:** Commit.

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm Опис tab (tagline + description)"
```

---

### Task 27: ProductForm — "Аромат" tab (three notes repeaters + family/concentration/series)

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`
- Modify: `app/Models/Catalogue/Product.php` (add `saveNotesFromForm()` helper)
- Create: `app/Filament/Resources/Products/Pages/CreateProduct.php` and `EditProduct.php` (override save to persist notes)

- [ ] **Step 1:** Append `aromaTab()` to `ProductForm`. The tab has three Select fields (family/concentration/series) and three Repeaters for note levels.

```php
use App\Enums\NoteLevel;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\Note;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Series;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;

// add Tab to tabs():
Tab::make(trans('catalogue.product.tabs.aroma'))
    ->schema(self::aromaTab()),

// new method:
protected static function aromaTab(): array
{
    return [
        Select::make('perfume_family_id')
            ->label(fn () => trans('catalogue.product.fields.perfume_family'))
            ->relationship('perfumeFamily', 'slug')
            ->searchable()
            ->preload(),
        Select::make('concentration_id')
            ->label(fn () => trans('catalogue.product.fields.concentration'))
            ->relationship('concentration', 'slug')
            ->searchable()
            ->preload(),
        Select::make('series_id')
            ->label(fn () => trans('catalogue.product.fields.series'))
            ->relationship('series', 'slug')
            ->searchable()
            ->preload(),

        self::notesRepeater('notes_top', NoteLevel::Top),
        self::notesRepeater('notes_heart', NoteLevel::Heart),
        self::notesRepeater('notes_base', NoteLevel::Base),
    ];
}

protected static function notesRepeater(string $key, NoteLevel $level): Repeater
{
    return Repeater::make($key)
        ->label(fn () => trans("catalogue.product.fields.{$key}"))
        ->schema([
            Select::make('note_id')
                ->options(fn () => Note::query()->orderBy('slug')->pluck('slug', 'id'))
                ->searchable()
                ->required(),
        ])
        ->orderColumn('sort_order')
        ->reorderable()
        ->defaultItems(0)
        ->addActionLabel(trans('catalogue.product.fields.'.$key));
}
```

- [ ] **Step 2:** Make ProductForm hydrate the three repeaters from the existing `notes()` relation. Override `mutateRecordDataUsing` on the EditProduct page. Edit `app/Filament/Resources/Products/Pages/EditProduct.php`:

```php
<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\NoteLevel;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\EditRecord;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $data[$key] = $this->record->notes()
                ->wherePivot('level', $level->value)
                ->orderBy('product_note.sort_order')
                ->get()
                ->map(fn ($note) => ['note_id' => $note->id])
                ->all();
        }

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Pop out the repeater keys; they're not real columns
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $this->cachedNotes[$level->value] = $data[$key] ?? [];
            unset($data[$key]);
        }

        return $data;
    }

    protected array $cachedNotes = [];

    protected function afterSave(): void
    {
        $sync = [];
        foreach (NoteLevel::cases() as $level) {
            $rows = $this->cachedNotes[$level->value] ?? [];
            foreach ($rows as $i => $row) {
                if (! empty($row['note_id'])) {
                    // Multiple notes at same level share the level value; the pivot's unique key allows this.
                    $sync[] = [
                        'note_id' => $row['note_id'],
                        'level' => $level->value,
                        'sort_order' => $i,
                    ];
                }
            }
        }

        $this->record->notes()->detach();
        foreach ($sync as $row) {
            $this->record->notes()->attach($row['note_id'], [
                'level' => $row['level'],
                'sort_order' => $row['sort_order'],
            ]);
        }
    }
}
```

- [ ] **Step 3:** Mirror the same logic on `CreateProduct`:

```php
<?php

namespace App\Filament\Resources\Products\Pages;

use App\Enums\NoteLevel;
use App\Filament\Resources\Products\ProductResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected array $cachedNotes = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        foreach (NoteLevel::cases() as $level) {
            $key = "notes_{$level->value}";
            $this->cachedNotes[$level->value] = $data[$key] ?? [];
            unset($data[$key]);
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        foreach (NoteLevel::cases() as $level) {
            foreach (($this->cachedNotes[$level->value] ?? []) as $i => $row) {
                if (! empty($row['note_id'])) {
                    $this->record->notes()->attach($row['note_id'], [
                        'level' => $level->value,
                        'sort_order' => $i,
                    ]);
                }
            }
        }
    }
}
```

- [ ] **Step 4:** Test creation with notes.

Append to `ProductResourceTest.php`:

```php
use App\Models\Catalogue\Note;

it('creates a product with top/heart/base notes', function () {
    $lychee = Note::factory()->create(['slug' => 'lychee']);
    $jasmine = Note::factory()->create(['slug' => 'jasmine']);
    $musk = Note::factory()->create(['slug' => 'musk']);

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 5', 'en' => 'LUXURY 5'],
            'slug' => 'luxury-5',
            'sku' => 'LV-002',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'notes_top' => [['note_id' => $lychee->id]],
            'notes_heart' => [['note_id' => $jasmine->id]],
            'notes_base' => [['note_id' => $musk->id]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('slug', 'luxury-5');
    expect($product->notesByLevel(\App\Enums\NoteLevel::Top)->pluck('id')->all())->toContain($lychee->id);
    expect($product->notesByLevel(\App\Enums\NoteLevel::Heart)->pluck('id')->all())->toContain($jasmine->id);
    expect($product->notesByLevel(\App\Enums\NoteLevel::Base)->pluck('id')->all())->toContain($musk->id);
});
```

- [ ] **Step 5:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 4 passing tests.

- [ ] **Step 6:** Commit.

```bash
git add app/Filament/Resources/Products tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm Аромат tab with three notes repeaters"
```

---

### Task 28: ProductForm — "Inspired by" tab

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`

- [ ] **Step 1:** Add the tab and its method.

```php
Tab::make(trans('catalogue.product.tabs.inspired_by'))
    ->schema(self::inspiredByTab()),

// ...

protected static function inspiredByTab(): array
{
    return [
        Select::make('inspired_brand_id')
            ->label(fn () => trans('catalogue.product.fields.inspired_brand'))
            ->relationship('inspiredBrand', 'slug')
            ->searchable()
            ->preload(),
        TextInput::make('inspired_perfume_name')
            ->label(fn () => trans('catalogue.product.fields.inspired_perfume_name'))
            ->maxLength(255),
    ];
}
```

- [ ] **Step 2:** Append test:

```php
use App\Models\Catalogue\Brand;

it('saves inspired-by brand and perfume name', function () {
    $brand = Brand::factory()->create(['slug' => 'ex-nihilo']);

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 6', 'en' => 'LUXURY 6'],
            'slug' => 'luxury-6',
            'sku' => 'LV-003',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'inspired_brand_id' => $brand->id,
            'inspired_perfume_name' => 'Fleur Narcotique',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('products', [
        'slug' => 'luxury-6',
        'inspired_brand_id' => $brand->id,
        'inspired_perfume_name' => 'Fleur Narcotique',
    ]);
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 5 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm Inspired by tab"
```

---

### Task 29: ProductForm — "Маркування" tab (tags, seasons, occasions, audiences)

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`

- [ ] **Step 1:** Add the tab and method.

```php
Tab::make(trans('catalogue.product.tabs.marking'))
    ->schema(self::markingTab()),

// ...

protected static function markingTab(): array
{
    return [
        Select::make('tags')
            ->label(fn () => trans('catalogue.product.fields.tags'))
            ->relationship('tags', 'slug')
            ->multiple()->searchable()->preload(),
        Select::make('seasons')
            ->label(fn () => trans('catalogue.product.fields.seasons'))
            ->relationship('seasons', 'slug')
            ->multiple()->searchable()->preload(),
        Select::make('occasions')
            ->label(fn () => trans('catalogue.product.fields.occasions'))
            ->relationship('occasions', 'slug')
            ->multiple()->searchable()->preload(),
        Select::make('audiences')
            ->label(fn () => trans('catalogue.product.fields.audiences'))
            ->relationship('audiences', 'slug')
            ->multiple()->searchable()->preload(),
    ];
}
```

Filament will sync these belongsToMany relations automatically when keys match relation names.

- [ ] **Step 2:** Append test:

```php
use App\Models\Catalogue\Tag;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\Audience;

it('attaches tags / seasons / occasions / audiences', function () {
    $tag = Tag::factory()->create();
    $season = Season::factory()->create();
    $occasion = Occasion::factory()->create();
    $audience = Audience::factory()->create();

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 7', 'en' => 'LUXURY 7'],
            'slug' => 'luxury-7',
            'sku' => 'LV-004',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'tags' => [$tag->id],
            'seasons' => [$season->id],
            'occasions' => [$occasion->id],
            'audiences' => [$audience->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-7');
    expect($p->tags->pluck('id')->all())->toContain($tag->id);
    expect($p->seasons->pluck('id')->all())->toContain($season->id);
    expect($p->occasions->pluck('id')->all())->toContain($occasion->id);
    expect($p->audiences->pluck('id')->all())->toContain($audience->id);
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 6 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm Маркування tab (tags/seasons/occasions/audiences)"
```

---

### Task 30: ProductForm — "SEO" tab

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`

- [ ] **Step 1:** Add tab + method.

```php
Tab::make(trans('catalogue.product.tabs.seo'))
    ->schema(self::seoTab()),

// ...

protected static function seoTab(): array
{
    return [
        TextInput::make('seo_title')
            ->label(fn () => trans('catalogue.product.fields.seo_title'))
            ->maxLength(255),
        Textarea::make('seo_description')
            ->label(fn () => trans('catalogue.product.fields.seo_description'))
            ->rows(3)
            ->maxLength(500),
    ];
}
```

- [ ] **Step 2:** Append test:

```php
it('saves SEO fields translatable per locale', function () {
    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 8', 'en' => 'LUXURY 8'],
            'slug' => 'luxury-8',
            'sku' => 'LV-005',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'seo_title' => ['uk' => 'Купити LUXURY 8', 'en' => 'Buy LUXURY 8'],
            'seo_description' => ['uk' => 'Нотатки', 'en' => 'Notes'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-8');
    app()->setLocale('en');
    expect($p->fresh()->seo_title)->toBe('Buy LUXURY 8');
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 7 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm SEO tab"
```

---

### Task 31: ProductForm — "Зображення" tab (Spatie Media)

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`

- [ ] **Step 1:** Add tab and method using `SpatieMediaLibraryFileUpload`.

```php
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;

// add tab:
Tab::make(trans('catalogue.product.tabs.images'))
    ->schema(self::imagesTab()),

// new method:
protected static function imagesTab(): array
{
    return [
        SpatieMediaLibraryFileUpload::make('primary')
            ->label(fn () => trans('catalogue.product.fields.primary_image'))
            ->collection('primary')
            ->image()
            ->imageEditor()
            ->maxSize(5120),

        SpatieMediaLibraryFileUpload::make('gallery')
            ->label(fn () => trans('catalogue.product.fields.gallery'))
            ->collection('gallery')
            ->multiple()
            ->image()
            ->reorderable()
            ->appendFiles()
            ->maxSize(5120),
    ];
}
```

- [ ] **Step 2:** Append test:

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('uploads primary and gallery images via Filament', function () {
    Storage::fake('public');

    livewire(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 9', 'en' => 'LUXURY 9'],
            'slug' => 'luxury-9',
            'sku' => 'LV-006',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'primary' => [UploadedFile::fake()->image('main.jpg')],
            'gallery' => [UploadedFile::fake()->image('g1.jpg'), UploadedFile::fake()->image('g2.jpg')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-9');
    expect($p->getMedia('primary'))->toHaveCount(1);
    expect($p->getMedia('gallery'))->toHaveCount(2);
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 8 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductForm Зображення tab (Spatie media uploader)"
```

---

### Task 32: ProductTable — columns, filters, bulk actions

**Files:**
- Modify: `app/Filament/Resources/Products/Tables/ProductsTable.php`

- [ ] **Step 1:** Replace the table contents with the full version:

```php
<?php

namespace App\Filament\Resources\Products\Tables;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('primary')
                    ->collection('primary')
                    ->conversion('thumb')
                    ->circular(),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('sku')->searchable(),
                TextColumn::make('series.slug')
                    ->label(fn () => trans('catalogue.product.fields.series')),
                TextColumn::make('perfumeFamily.slug')
                    ->label(fn () => trans('catalogue.product.fields.perfume_family')),
                TextColumn::make('concentration.abbreviation')
                    ->label(fn () => trans('catalogue.product.fields.concentration')),
                TextColumn::make('price_uah')->suffix(' ₴')->sortable(),
                TextColumn::make('price_eur')->suffix(' €')->sortable(),
                IconColumn::make('in_stock')->boolean(),
                IconColumn::make('is_published')->boolean(),
            ])
            ->filters([
                SelectFilter::make('series_id')->relationship('series', 'slug'),
                SelectFilter::make('perfume_family_id')->relationship('perfumeFamily', 'slug'),
                SelectFilter::make('concentration_id')->relationship('concentration', 'slug'),
                SelectFilter::make('gender')->options([
                    'male' => trans('catalogue.gender.male'),
                    'female' => trans('catalogue.gender.female'),
                    'unisex' => trans('catalogue.gender.unisex'),
                ]),
                TernaryFilter::make('is_published'),
                TernaryFilter::make('in_stock'),
                SelectFilter::make('tags')
                    ->relationship('tags', 'slug')
                    ->multiple()
                    ->preload(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([EditAction::make(), DeleteAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('publish')
                        ->label('Publish')
                        ->action(fn ($records) => $records->each->update(['is_published' => true, 'published_at' => now()])),
                    BulkAction::make('unpublish')
                        ->label('Unpublish')
                        ->action(fn ($records) => $records->each->update(['is_published' => false])),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
```

- [ ] **Step 2:** Append test for filters:

```php
it('filters products by published flag', function () {
    Product::factory()->count(2)->create(['is_published' => true]);
    Product::factory()->count(3)->draft()->create();

    livewire(ListProducts::class)
        ->filterTable('is_published', true)
        ->assertCountTableRecords(2);
});

it('bulk-publishes selected products', function () {
    $drafts = Product::factory()->count(2)->draft()->create();

    livewire(ListProducts::class)
        ->callTableBulkAction('publish', $drafts);

    expect(Product::whereIn('id', $drafts->pluck('id'))->where('is_published', true)->count())->toBe(2);
});
```

- [ ] **Step 3:** Run.

```bash
php artisan test --filter=ProductResourceTest
```

Expected: 10 passing tests.

- [ ] **Step 4:** Commit.

```bash
git add app/Filament/Resources/Products/Tables/ProductsTable.php tests/Feature/Catalogue/Filament/ProductResourceTest.php
git commit -m "catalogue: ProductsTable full columns, filters, bulk publish/unpublish"
```

---

## Phase 7 — Seeders and finalization

### Task 33: Catalogue seeders + DatabaseSeeder wiring

**Files:**
- Create: 9 seeders under `database/seeders/Catalogue/`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1:** Create `database/seeders/Catalogue/PerfumeFamilySeeder.php`:

```php
<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Database\Seeder;

class PerfumeFamilySeeder extends Seeder
{
    public function run(): void
    {
        $families = [
            ['slug' => 'citrus', 'name' => ['uk' => 'Цитрусове', 'en' => 'Citrus']],
            ['slug' => 'floral', 'name' => ['uk' => 'Квіткове', 'en' => 'Floral']],
            ['slug' => 'fougere', 'name' => ['uk' => 'Фужерне', 'en' => 'Fougère']],
            ['slug' => 'woody', 'name' => ['uk' => 'Деревне', 'en' => 'Woody']],
            ['slug' => 'oriental', 'name' => ['uk' => 'Східне', 'en' => 'Oriental']],
            ['slug' => 'chypre', 'name' => ['uk' => 'Шипрове', 'en' => 'Chypre']],
            ['slug' => 'gourmand', 'name' => ['uk' => 'Гурманське', 'en' => 'Gourmand']],
            ['slug' => 'aquatic', 'name' => ['uk' => 'Акватичне', 'en' => 'Aquatic']],
        ];

        foreach ($families as $i => $f) {
            PerfumeFamily::updateOrCreate(
                ['slug' => $f['slug']],
                array_merge($f, ['sort_order' => $i, 'is_active' => true]),
            );
        }
    }
}
```

- [ ] **Step 2:** Create `ConcentrationSeeder.php`:

```php
<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Concentration;
use Illuminate\Database\Seeder;

class ConcentrationSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'edp', 'abbreviation' => 'EDP', 'name' => ['uk' => 'Eau de Parfum', 'en' => 'Eau de Parfum']],
            ['slug' => 'edt', 'abbreviation' => 'EDT', 'name' => ['uk' => 'Eau de Toilette', 'en' => 'Eau de Toilette']],
            ['slug' => 'parfum', 'abbreviation' => 'PARF', 'name' => ['uk' => 'Parfum', 'en' => 'Parfum']],
            ['slug' => 'edc', 'abbreviation' => 'EDC', 'name' => ['uk' => 'Eau de Cologne', 'en' => 'Eau de Cologne']],
            ['slug' => 'extrait', 'abbreviation' => 'EXT', 'name' => ['uk' => 'Extrait', 'en' => 'Extrait']],
        ];

        foreach ($rows as $i => $r) {
            Concentration::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
```

- [ ] **Step 3:** Create `SeasonSeeder.php`:

```php
<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Season;
use Illuminate\Database\Seeder;

class SeasonSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'spring', 'name' => ['uk' => 'Весна', 'en' => 'Spring']],
            ['slug' => 'summer', 'name' => ['uk' => 'Літо', 'en' => 'Summer']],
            ['slug' => 'autumn', 'name' => ['uk' => 'Осінь', 'en' => 'Autumn']],
            ['slug' => 'winter', 'name' => ['uk' => 'Зима', 'en' => 'Winter']],
        ];

        foreach ($rows as $i => $r) {
            Season::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true]));
        }
    }
}
```

- [ ] **Step 4:** Create `TagSeeder.php`:

```php
<?php

namespace Database\Seeders\Catalogue;

use App\Models\Catalogue\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $rows = [
            ['slug' => 'bestseller', 'color' => '#C77B7B', 'name' => ['uk' => 'Бестселер', 'en' => 'Bestseller']],
            ['slug' => 'new', 'color' => '#7CB87A', 'name' => ['uk' => 'Новинка', 'en' => 'New']],
            ['slug' => 'sale', 'color' => '#D4A04C', 'name' => ['uk' => 'Акція', 'en' => 'Sale']],
            ['slug' => 'limited', 'color' => '#8B6F8B', 'name' => ['uk' => 'Лімітка', 'en' => 'Limited']],
        ];

        foreach ($rows as $i => $r) {
            Tag::updateOrCreate(['slug' => $r['slug']], array_merge($r, ['sort_order' => $i, 'is_active' => true, 'is_featured' => true]));
        }
    }
}
```

- [ ] **Step 5:** Create empty scaffolds for the remaining five (`SeriesSeeder`, `OccasionSeeder`, `AudienceSeeder`, `BrandSeeder`, `NoteSeeder`). Example for `SeriesSeeder.php`:

```php
<?php

namespace Database\Seeders\Catalogue;

use Illuminate\Database\Seeder;

class SeriesSeeder extends Seeder
{
    public function run(): void
    {
        // Content manager will add Series records via Filament admin.
    }
}
```

Repeat with the same body for `OccasionSeeder`, `AudienceSeeder`, `BrandSeeder`, `NoteSeeder`.

- [ ] **Step 6:** Wire into `database/seeders/DatabaseSeeder.php`:

```php
<?php

namespace Database\Seeders;

use Database\Seeders\Catalogue\AudienceSeeder;
use Database\Seeders\Catalogue\BrandSeeder;
use Database\Seeders\Catalogue\ConcentrationSeeder;
use Database\Seeders\Catalogue\NoteSeeder;
use Database\Seeders\Catalogue\OccasionSeeder;
use Database\Seeders\Catalogue\PerfumeFamilySeeder;
use Database\Seeders\Catalogue\SeasonSeeder;
use Database\Seeders\Catalogue\SeriesSeeder;
use Database\Seeders\Catalogue\TagSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
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
        ]);
    }
}
```

- [ ] **Step 7:** Verify the seeders run.

Run:
```bash
php artisan migrate:fresh --seed
```

Expected: migrations + seed all succeed. Verify with:
```bash
php artisan tinker --execute="echo App\Models\Catalogue\PerfumeFamily::count() . ' families, ' . App\Models\Catalogue\Tag::count() . ' tags';"
```

Expected: `8 families, 4 tags`.

- [ ] **Step 8:** Re-run seed to confirm idempotency.

```bash
php artisan db:seed
```

Expected: no errors (records already exist; `updateOrCreate` is idempotent).

```bash
php artisan tinker --execute="echo App\Models\Catalogue\PerfumeFamily::count();"
```

Expected: still `8`.

- [ ] **Step 9:** Commit.

```bash
git add database/seeders
git commit -m "catalogue: seeders for families, concentrations, seasons, tags + scaffolds for the rest"
```

---

### Task 34: Final verification — full migrate:fresh + tests

This is a self-check, not a code change.

- [ ] **Step 1:** Reset DB and re-run seeds.

```bash
php artisan migrate:fresh --seed
```

Expected: clean run, no errors.

- [ ] **Step 2:** Run the full test suite.

```bash
php artisan test
```

Expected: all tests pass. Specifically you should see:
- `InfraTest`: 3 passing
- `DictionaryBehaviourTest`: 7 passing
- `ProductModelTest`: 7 passing
- `ProductMediaTest`: 3 passing
- `ProductPriceLocalizationTest`: 3 passing
- `Filament/DictionaryResourcesTest`: 10 passing
- `Filament/ProductResourceTest`: 10 passing
- **Total: 43 passing**

- [ ] **Step 3:** Manually verify in the admin panel. Start the dev server.

Run:
```bash
composer dev
```

Then open `http://localhost:8000/admin`, log in, and confirm:
- The sidebar has groups "Catalogue" and "Attributes".
- Each dictionary has a working list + create + edit page.
- The Products list page renders.
- Creating a product through all tabs works.
- Locale tabs (UK/EN) appear on translatable fields.

- [ ] **Step 4:** If everything is good, no commit needed — this task is verification only.

---

## Self-Review (filled in by the plan author)

**Spec coverage check:**
- ✅ Goals 1–9 from spec §1: all covered (Product, 9 dictionaries, Filament CRUD, translatable, dual currency, media, seeders, factories, tests).
- ✅ Non-goals from §2: respected (no variants, no EAV, no FX, no stock quantity, no roles).
- ✅ Domain model §3: 1 Product + 9 dictionaries + 4 belongsTo + 5 belongsToMany — Tasks 7–15.
- ✅ Schema §4: every column in every table is in a migration task.
- ✅ Media §5: Task 16, two collections + three conversions + alt translatable.
- ✅ Localisation §6: Tasks 4 (RefreshDatabase), 5 (lang files), 17 (currency test), 18 (panel navigation). Note: `hideDefaultLocaleInURL` is already a config-only concern handled by mcamara/laravel-localization defaults — no code change needed.
- ✅ Filament admin §7: Tasks 18–32 cover panel config, navigation, all dictionary resources, full ProductResource.
- ✅ Seeders §8: Task 33.
- ✅ Tests §9: dispersed across Tasks 7–32 (one test class per area).
- ✅ Technical notes §10: composer plugin (Task 1), config (Task 3), storage:link (Task 2), lang files (Task 5), no permissions (acknowledged out-of-scope).

**Placeholder scan:** No TBD / TODO / "implement later" / "similar to Task N" references found. Repeated code is repeated verbatim where called for.

**Type consistency check:**
- `App\Enums\Gender` is used in Product model (Task 13) and ProductForm (Task 25). ✅
- `App\Enums\NoteLevel` is used in Product (Task 14) and ProductForm/CreateProduct/EditProduct (Task 27). ✅
- `displayPrice()` signature defined Task 13, tested Task 17. ✅
- Relation names match between Product model and Filament selects: `notes`, `tags`, `seasons`, `occasions`, `audiences`, `perfumeFamily`, `concentration`, `series`, `inspiredBrand`. ✅
- `products()` relation added on every dictionary model (Tasks 19–24). ✅

Plan is internally consistent.
