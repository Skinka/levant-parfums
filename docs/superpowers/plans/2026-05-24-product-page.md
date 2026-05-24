# Product page implementation plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship `/products/{slug}` storefront page from the Claude Design bundle, with per-series CSS theme switching (Luxury → cream, Onyx → onyx) and an order form that becomes a preorder form when the product is out of stock.

**Architecture:** Single Laravel route + controller method. Per-series theme is a data-driven attribute on `series.theme_class` (column), not a slug-keyed `match`. Public layout `site.blade.php` reads `$theme` and writes it onto `<body>`. Product page is composed of 6 `<x-site.*>` Blade partials. The order form reuses the existing forms subsystem (`App\Forms\*`) via a small `metadata()` hook on `FormType` that injects an `is_preorder` snapshot into `submission->data`.

**Tech Stack:** Laravel 13, Filament 5, Livewire 3 (already wired), Spatie Translatable, Spatie MediaLibrary, Pest 4, vanilla CSS with theme variables, vanilla JS for lightbox. No Tailwind on storefront.

---

## Task 1: DB foundation — `series.theme_class` + 4 columns on `products`

**Files:**
- Create: `database/migrations/2026_05_24_120000_add_theme_class_to_series_table.php`
- Create: `database/migrations/2026_05_24_120001_add_character_block_to_products_table.php`
- Modify: `app/Models/Catalogue/Series.php`
- Modify: `app/Models/Catalogue/Product.php`
- Test: `tests/Feature/Catalogue/ProductModelTest.php` (new)

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Catalogue/ProductModelTest.php`:
```php
<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;

it('series has theme_class column with default theme-cream', function () {
    $s = Series::create(['name' => ['uk' => 'Test', 'en' => 'Test'], 'slug' => 'test-series']);
    expect($s->fresh()->theme_class)->toBe('theme-cream');
});

it('series accepts custom theme_class', function () {
    $s = Series::create([
        'name' => ['uk' => 'Test', 'en' => 'Test'],
        'slug' => 'test-series',
        'theme_class' => 'theme-onyx',
    ]);
    expect($s->fresh()->theme_class)->toBe('theme-onyx');
});

it('product persists translatable character + why and integer sillage + longevity', function () {
    $p = Product::factory()->create([
        'character' => ['uk' => 'Прохолодний шкіра', 'en' => 'Cool skin'],
        'why' => ['uk' => 'Бо нерви', 'en' => 'Because nerves'],
        'sillage_score' => 4,
        'longevity_hours' => 8,
    ]);

    $p = $p->fresh();
    expect($p->getTranslation('character', 'uk'))->toBe('Прохолодний шкіра');
    expect($p->getTranslation('character', 'en'))->toBe('Cool skin');
    expect($p->getTranslation('why', 'uk'))->toBe('Бо нерви');
    expect($p->sillage_score)->toBe(4);
    expect($p->longevity_hours)->toBe(8);
});

it('product allows null character + why + sillage + longevity', function () {
    $p = Product::factory()->create([
        'character' => null,
        'why' => null,
        'sillage_score' => null,
        'longevity_hours' => null,
    ]);

    $p = $p->fresh();
    expect($p->character)->toBeNull();
    expect($p->why)->toBeNull();
    expect($p->sillage_score)->toBeNull();
    expect($p->longevity_hours)->toBeNull();
});
```

- [ ] **Step 2: Run test, verify it fails**

Run: `php artisan test --filter='ProductModelTest'`
Expected: FAIL — columns don't exist.

- [ ] **Step 3: Create migration A — theme_class on series**

Create `database/migrations/2026_05_24_120000_add_theme_class_to_series_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->string('theme_class', 64)->default('theme-cream')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('series', function (Blueprint $table) {
            $table->dropColumn('theme_class');
        });
    }
};
```

- [ ] **Step 4: Create migration B — character block on products**

Create `database/migrations/2026_05_24_120001_add_character_block_to_products_table.php`:
```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->json('character')->nullable()->after('tagline');
            $table->json('why')->nullable()->after('character');
            $table->unsignedTinyInteger('sillage_score')->nullable()->after('why');
            $table->unsignedTinyInteger('longevity_hours')->nullable()->after('sillage_score');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['character', 'why', 'sillage_score', 'longevity_hours']);
        });
    }
};
```

- [ ] **Step 5: Update `Series` model fillable**

Modify `app/Models/Catalogue/Series.php` — replace `$fillable` line:
```php
protected $fillable = ['name', 'slug', 'sort_order', 'is_active', 'theme_class'];
```

- [ ] **Step 6: Update `Product` model — fillable, translatable, casts**

Modify `app/Models/Catalogue/Product.php`:

Replace `$fillable`:
```php
protected $fillable = [
    'sku', 'slug', 'name', 'tagline', 'description',
    'character', 'why',
    'inspired_perfume_name', 'inspired_brand_id',
    'volume_ml', 'gender',
    'price_uah', 'price_eur',
    'sillage_score', 'longevity_hours',
    'in_stock', 'is_published', 'published_at',
    'seo_title', 'seo_description',
    'perfume_family_id', 'concentration_id', 'series_id',
];
```

Replace `$translatable`:
```php
public array $translatable = ['name', 'tagline', 'description', 'character', 'why', 'seo_title', 'seo_description'];
```

In the `casts()` method, add:
```php
'sillage_score' => 'integer',
'longevity_hours' => 'integer',
```

- [ ] **Step 7: Run tests, verify pass**

```bash
php artisan migrate
php artisan test --filter='ProductModelTest'
```
Expected: PASS — 4 tests.

- [ ] **Step 8: Commit**

```bash
git add database/migrations/2026_05_24_120000_add_theme_class_to_series_table.php \
        database/migrations/2026_05_24_120001_add_character_block_to_products_table.php \
        app/Models/Catalogue/Series.php \
        app/Models/Catalogue/Product.php \
        tests/Feature/Catalogue/ProductModelTest.php
git commit -m "catalogue: add series.theme_class + character block on products"
```

---

## Task 2: Theme config + layout body class

**Files:**
- Create: `config/site.php`
- Modify: `resources/views/layouts/site.blade.php`
- Test: `tests/Feature/Public/LayoutThemeTest.php` (new)

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Public/LayoutThemeTest.php`:
```php
<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

it('layout renders body with default theme-cream when $theme not provided', function () {
    Route::get('/__layout-default', fn () => View::make('layouts.site')
        ->with('slot', '')
        ->nest('content', '<div>x</div>'));
    // simpler: render an inline view that extends layout
    $html = view('layouts.site')->renderSections()['content'] ?? '';
    // direct render of layout requires content section, so use real route below
})->skip('covered by ProductShowTest theme assertions');

it('config/site.php lists three allowed themes', function () {
    $themes = config('site.themes');
    expect($themes)->toHaveKeys(['theme-cream', 'theme-onyx', 'theme-editorial']);
});
```

Note: the layout-body test is best covered indirectly through `ProductShowTest` (Task 10). Keep `config('site.themes')` direct assertion now.

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='LayoutThemeTest'
```
Expected: FAIL on `config('site.themes')` (null).

- [ ] **Step 3: Create `config/site.php`**

```php
<?php

return [
    'themes' => [
        'theme-cream' => 'Cream (Luxury)',
        'theme-onyx' => 'Onyx (Dark)',
        'theme-editorial' => 'Editorial (Minimal)',
    ],
];
```

- [ ] **Step 4: Edit `resources/views/layouts/site.blade.php`**

Replace `<body class="theme-cream">` with:
```blade
<body class="{{ $theme ?? 'theme-cream' }}">
```

- [ ] **Step 5: Run test, verify pass**

```bash
php artisan config:clear
php artisan test --filter='LayoutThemeTest'
```
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add config/site.php resources/views/layouts/site.blade.php tests/Feature/Public/LayoutThemeTest.php
git commit -m "site: theme config + layout reads \$theme prop"
```

---

## Task 3: Filament `SeriesResource` — `theme_class` Select

**Files:**
- Modify: `app/Filament/Resources/Series/Schemas/SeriesForm.php` (path varies; locate via the existing SeriesResource)
- Test: `tests/Feature/Catalogue/Filament/SeriesResourceTest.php` (new)

- [ ] **Step 1: Locate the SeriesResource form file**

```bash
find app/Filament/Resources -path '*Series*' -name '*.php'
```
Expected file pattern: `app/Filament/Resources/Series/Schemas/SeriesForm.php` (modeled after ProductForm).

- [ ] **Step 2: Write failing test**

Create `tests/Feature/Catalogue/Filament/SeriesResourceTest.php`:
```php
<?php

use App\Filament\Resources\Series\Pages\EditSeries;
use App\Models\Catalogue\Series;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('series form has theme_class select with values from config', function () {
    $s = Series::create(['slug' => 'demo', 'name' => ['uk' => 'Demo', 'en' => 'Demo']]);

    Livewire::test(EditSeries::class, ['record' => $s->getRouteKey()])
        ->assertFormFieldExists('theme_class')
        ->assertFormSet(['theme_class' => 'theme-cream'])
        ->fillForm(['theme_class' => 'theme-onyx'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($s->fresh()->theme_class)->toBe('theme-onyx');
});
```

- [ ] **Step 3: Run test, verify it fails**

```bash
php artisan test --filter='SeriesResourceTest'
```
Expected: FAIL (form field doesn't exist or page class missing).

- [ ] **Step 4: Add `theme_class` Select to SeriesForm**

In `SeriesForm::configure()` (or whichever method composes the schema), add — after `slug`:
```php
\Filament\Forms\Components\Select::make('theme_class')
    ->label(fn () => trans('catalogue.series.fields.theme_class'))
    ->options(config('site.themes'))
    ->required()
    ->default('theme-cream'),
```

If the SeriesResource lacks Edit/Create page classes, create stubs based on existing PerfumeFamilyResource pattern. Inspect with:
```bash
ls app/Filament/Resources/PerfumeFamilies/Pages/
```

- [ ] **Step 5: Add translation key**

Edit `lang/uk/catalogue.php` — under `series.fields` (create section if missing):
```php
'series' => [
    'fields' => [
        'theme_class' => 'Тема оформлення',
    ],
],
```

Edit `lang/en/catalogue.php` — mirror:
```php
'series' => [
    'fields' => [
        'theme_class' => 'Theme',
    ],
],
```

- [ ] **Step 6: Run test, verify pass**

```bash
php artisan test --filter='SeriesResourceTest'
```
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Series/ lang/uk/catalogue.php lang/en/catalogue.php tests/Feature/Catalogue/Filament/SeriesResourceTest.php
git commit -m "filament: SeriesResource theme_class select"
```

---

## Task 4: Seeders — set `theme_class` and demo `character`/`why`/`sillage`/`longevity`

**Files:**
- Modify: `database/seeders/Catalogue/SeriesSeeder.php`
- Modify: `database/seeders/Catalogue/ProductSeeder.php`
- Test: `tests/Feature/Catalogue/SeederTest.php` (new)

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Catalogue/SeederTest.php`:
```php
<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Database\Seeders\Catalogue\ProductSeeder;
use Database\Seeders\Catalogue\SeriesSeeder;

it('seeds luxury with theme-cream and onyx with theme-onyx', function () {
    (new SeriesSeeder())->run();
    expect(Series::where('slug', 'luxury')->first()->theme_class)->toBe('theme-cream');
    expect(Series::where('slug', 'onyx')->first()->theme_class)->toBe('theme-onyx');
});

it('seeds character/sillage data on first 2 luxury + 2 onyx products', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $sampleLux = Product::where('slug', 'luxury-1')->first();
    $sampleOnyx = Product::where('slug', 'onyx-1')->first();

    expect($sampleLux->sillage_score)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5);
    expect($sampleLux->longevity_hours)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(12);
    expect($sampleLux->getTranslation('character', 'uk'))->not->toBeEmpty();
    expect($sampleLux->getTranslation('why', 'uk'))->not->toBeEmpty();

    expect($sampleOnyx->sillage_score)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5);
    expect($sampleOnyx->longevity_hours)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(12);
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='SeederTest'
```
Expected: FAIL.

- [ ] **Step 3: Update `SeriesSeeder`**

Modify `database/seeders/Catalogue/SeriesSeeder.php` — replace `$rows` with:
```php
$rows = [
    ['slug' => 'luxury', 'name' => ['uk' => 'Luxury', 'en' => 'Luxury'], 'theme_class' => 'theme-cream'],
    ['slug' => 'onyx', 'name' => ['uk' => 'Onyx', 'en' => 'Onyx'], 'theme_class' => 'theme-onyx'],
];
```

The existing `updateOrCreate(...)` line already merges `$r`, so `theme_class` will be persisted.

- [ ] **Step 4: Update `ProductSeeder` — add sample data for first 4**

In `database/seeders/Catalogue/ProductSeeder.php`, locate the array entries with `slug` values `luxury-1`, `luxury-2`, `onyx-1`, `onyx-2`. For each, add these keys alongside the existing keys:

For `luxury-1`:
```php
'character' => ['uk' => 'Прохолодний шкіра, сухий цитрус і свіжа герань', 'en' => 'Cool skin, dry citrus and fresh geranium'],
'why' => ['uk' => 'Універсальний денний підпис: легка серцева тиша й деревний слід без шуму.', 'en' => 'A universal daytime signature: a quiet heart and a calm woody trail.'],
'sillage_score' => 3,
'longevity_hours' => 6,
```

For `luxury-2`:
```php
'character' => ['uk' => 'Тепла бавовна і кремовий мускус', 'en' => 'Warm cotton and creamy musk'],
'why' => ['uk' => 'Для тих, хто шукає чистоту без пудри й гламуру.', 'en' => 'For those who want cleanliness without powder or gloss.'],
'sillage_score' => 2,
'longevity_hours' => 8,
```

For `onyx-1`:
```php
'character' => ['uk' => 'Чорний шкіра, темний удд і копчений ладан', 'en' => 'Black leather, dark oud and smoked frankincense'],
'why' => ['uk' => 'Вечірня самість, що не намагається сподобатись. Шлейф — як підпис у темряві.', 'en' => 'An evening self that does not try to please. A trail like a signature in the dark.'],
'sillage_score' => 5,
'longevity_hours' => 10,
```

For `onyx-2`:
```php
'character' => ['uk' => 'Гіркий шоколад, тютюн і кориця', 'en' => 'Bitter chocolate, tobacco and cinnamon'],
'why' => ['uk' => 'Для холодних вечорів, коли треба тримати тепло близько.', 'en' => 'For cold evenings when warmth must stay close.'],
'sillage_score' => 4,
'longevity_hours' => 12,
```

Locate via `grep -n "luxury-1\|luxury-2\|onyx-1\|onyx-2" database/seeders/Catalogue/ProductSeeder.php`.

- [ ] **Step 5: Run test, verify pass**

```bash
php artisan test --filter='SeederTest'
```
Expected: PASS — 2 tests.

- [ ] **Step 6: Commit**

```bash
git add database/seeders/Catalogue/ tests/Feature/Catalogue/SeederTest.php
git commit -m "seed: theme_class on series + character samples for 4 demo products"
```

---

## Task 5: Filament `ProductForm` — Character & strength section

**Files:**
- Modify: `app/Filament/Resources/Products/Schemas/ProductForm.php`
- Modify: `lang/uk/catalogue.php`, `lang/en/catalogue.php`
- Test: extend `tests/Feature/Catalogue/Filament/ProductResourceTest.php` (existing) OR new test file

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Catalogue/Filament/ProductFormCharacterTest.php`:
```php
<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('ProductForm has character + why + sillage + longevity fields', function () {
    $p = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $p->getRouteKey()])
        ->assertFormFieldExists('character')
        ->assertFormFieldExists('why')
        ->assertFormFieldExists('sillage_score')
        ->assertFormFieldExists('longevity_hours');
});

it('saving Character & strength values persists to product', function () {
    $p = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $p->getRouteKey()])
        ->fillForm([
            'character' => 'Темний шкіра і ладан',
            'why' => 'Для зими',
            'sillage_score' => 4,
            'longevity_hours' => 10,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $p = $p->fresh();
    expect($p->sillage_score)->toBe(4);
    expect($p->longevity_hours)->toBe(10);
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='ProductFormCharacterTest'
```
Expected: FAIL.

- [ ] **Step 3: Add a `characterTab()` method to `ProductForm`**

In `app/Filament/Resources/Products/Schemas/ProductForm.php`, add this new protected method (insert after `aromaTab`):
```php
protected static function characterTab(): array
{
    return [
        TextInput::make('character')
            ->label(fn () => trans('catalogue.product.fields.character'))
            ->maxLength(160),
        Textarea::make('why')
            ->label(fn () => trans('catalogue.product.fields.why'))
            ->rows(4),
        Select::make('sillage_score')
            ->label(fn () => trans('catalogue.product.fields.sillage_score'))
            ->options([
                1 => trans('catalogue.product.sillage_words.1'),
                2 => trans('catalogue.product.sillage_words.2'),
                3 => trans('catalogue.product.sillage_words.3'),
                4 => trans('catalogue.product.sillage_words.4'),
                5 => trans('catalogue.product.sillage_words.5'),
            ])
            ->native(false),
        Select::make('longevity_hours')
            ->label(fn () => trans('catalogue.product.fields.longevity_hours'))
            ->options(collect([2, 4, 6, 8, 10, 12])->mapWithKeys(fn ($h) => [$h => $h.' h'])->all())
            ->native(false),
    ];
}
```

Register the new tab in `configure()` — insert into the `tabs(...)` array between `aroma` and `inspired_by`:
```php
Tab::make(trans('catalogue.product.tabs.character'))
    ->schema(self::characterTab()),
```

- [ ] **Step 4: Add translation keys**

Edit `lang/uk/catalogue.php` — under `product.tabs` add:
```php
'character' => 'Характер',
```
Under `product.fields` add:
```php
'character' => 'Характер (короткий настрій)',
'why' => 'Чому саме цей',
'sillage_score' => 'Шлейф',
'longevity_hours' => 'Стійкість',
```
Add new section `product.sillage_words`:
```php
'sillage_words' => [
    1 => 'Поряд зі шкірою',
    2 => 'Близько',
    3 => 'Помірно',
    4 => 'Сильно',
    5 => 'Тяжко',
],
```

Mirror to `lang/en/catalogue.php`:
```php
// product.tabs
'character' => 'Character',
// product.fields
'character' => 'Character (mood line)',
'why' => 'Why this one',
'sillage_score' => 'Sillage',
'longevity_hours' => 'Longevity',
// product.sillage_words
'sillage_words' => [
    1 => 'Skin',
    2 => 'Close',
    3 => 'Moderate',
    4 => 'Strong',
    5 => 'Heavy',
],
```

- [ ] **Step 5: Run test, verify pass**

```bash
php artisan test --filter='ProductFormCharacterTest'
```
Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Filament/Resources/Products/Schemas/ProductForm.php \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Catalogue/Filament/ProductFormCharacterTest.php
git commit -m "filament: ProductForm Character & strength section"
```

---

## Task 6: Forms — `metadata()` hook + `is_preorder` snapshot

**Files:**
- Modify: `app/Forms/Types/FormType.php`
- Modify: `app/Forms/Livewire/FormComponent.php`
- Modify: `app/Forms/Types/OrderFormType.php`
- Test: `tests/Feature/Forms/FormTypeTest.php` (existing — add cases)

- [ ] **Step 1: Write failing test**

Append to `tests/Feature/Forms/FormTypeTest.php`:
```php
it('FormType::metadata() default returns empty array', function () {
    $type = new class extends \App\Forms\Types\FormType {
        public function key(): string { return 'x'; }
        public function label(): string { return 'X'; }
        public function rules(?\Illuminate\Database\Eloquent\Model $s = null): array { return []; }
        public function adminMailable(\App\Forms\Models\FormSubmission $s): \Illuminate\Mail\Mailable {
            return new class extends \Illuminate\Mail\Mailable {};
        }
    };
    expect($type->metadata(null))->toBe([]);
});

it('OrderFormType::metadata() returns is_preorder = false for in_stock product', function () {
    $p = \App\Models\Catalogue\Product::factory()->create(['in_stock' => true]);
    expect(app(\App\Forms\Types\OrderFormType::class)->metadata($p))
        ->toBe(['is_preorder' => false]);
});

it('OrderFormType::metadata() returns is_preorder = true for out-of-stock product', function () {
    $p = \App\Models\Catalogue\Product::factory()->create(['in_stock' => false]);
    expect(app(\App\Forms\Types\OrderFormType::class)->metadata($p))
        ->toBe(['is_preorder' => true]);
});

it('OrderFormType::metadata() returns is_preorder = false when subject is null', function () {
    expect(app(\App\Forms\Types\OrderFormType::class)->metadata(null))
        ->toBe(['is_preorder' => false]);
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='FormTypeTest'
```
Expected: FAIL — `metadata()` not defined.

- [ ] **Step 3: Add `metadata()` to base `FormType`**

Append to `app/Forms/Types/FormType.php` (before the closing `}`):
```php
    /**
     * Per-type fields merged into FormSubmission::$data at submit time.
     * Use to snapshot state from $subject (e.g. is_preorder).
     */
    public function metadata(?Model $subject): array
    {
        return [];
    }
```

- [ ] **Step 4: Override `metadata()` in `OrderFormType`**

Append to `app/Forms/Types/OrderFormType.php` (before the closing `}`):
```php
    public function metadata(?Model $subject): array
    {
        return [
            'is_preorder' => $subject instanceof Product ? ! $subject->in_stock : false,
        ];
    }
```

- [ ] **Step 5: Merge metadata into submission data in `FormComponent::submit()`**

In `app/Forms/Livewire/FormComponent.php`, replace this block:
```php
$data = $this->validate($type->rules($this->subject), [], $type->attributes());

$submission = FormSubmission::create([
    'type' => $type->key(),
    'status' => FormSubmission::STATUS_NEW,
    'data' => $data,
```
with:
```php
$data = $this->validate($type->rules($this->subject), [], $type->attributes());
$data = array_merge($data, $type->metadata($this->subject));

$submission = FormSubmission::create([
    'type' => $type->key(),
    'status' => FormSubmission::STATUS_NEW,
    'data' => $data,
```

- [ ] **Step 6: Run test, verify pass**

```bash
php artisan test --filter='FormTypeTest'
```
Expected: PASS — 4 new tests pass; existing FormTypeTest cases still pass.

- [ ] **Step 7: Commit**

```bash
git add app/Forms/Types/FormType.php app/Forms/Types/OrderFormType.php \
        app/Forms/Livewire/FormComponent.php tests/Feature/Forms/FormTypeTest.php
git commit -m "forms: metadata() hook + OrderFormType snapshots is_preorder into submission data"
```

---

## Task 7: `OrderFormType` schema rewrite + `OrderForm` Livewire + qty stepper

**Files:**
- Modify: `app/Forms/Types/OrderFormType.php`
- Modify: `app/Forms/Livewire/OrderForm.php`
- Modify: `lang/uk/forms.php`, `lang/en/forms.php`
- Modify: `tests/Feature/Forms/OrderFormTest.php`

- [ ] **Step 1: Update test — new schema + qty stepper assertions**

Replace `tests/Feature/Forms/OrderFormTest.php` with:
```php
<?php

use App\Forms\Livewire\OrderForm;
use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    Mail::fake();
    RateLimiter::clear('forms:order:127.0.0.1');
});

function validOrderPayload(): array
{
    return [
        'name' => 'Iван',
        'phone' => '+380501234567',
        'email' => 'ivan@example.test',
        'city' => 'Київ',
        'np_office' => 'Відділення №12',
        'qty' => 2,
        'comment' => 'Дзвоніть після 18:00',
    ];
}

it('mount without subject throws', function () {
    expect(fn () => Livewire::test(OrderForm::class))->toThrow(LogicException::class);
});

it('mount with non-Product subject throws', function () {
    $article = Article::factory()->create();
    expect(fn () => Livewire::test(OrderForm::class, ['subject' => $article]))->toThrow(LogicException::class);
});

it('valid submit on in-stock product persists with is_preorder=false', function () {
    $product = Product::factory()->create(['in_stock' => true]);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    $row = FormSubmission::first();
    expect($row->data)->toMatchArray(validOrderPayload() + ['is_preorder' => false]);

    Mail::assertQueued(OrderAdminMail::class);
    Mail::assertQueued(OrderClientMail::class);
});

it('valid submit on out-of-stock product persists with is_preorder=true', function () {
    $product = Product::factory()->create(['in_stock' => false]);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())
        ->call('submit')
        ->assertHasNoErrors();

    $row = FormSubmission::first();
    expect($row->data['is_preorder'])->toBeTrue();
});

it('rejects missing required city', function () {
    $product = Product::factory()->create();
    $payload = validOrderPayload();
    $payload['city'] = '';

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set($payload)
        ->call('submit')
        ->assertHasErrors(['city']);
});

it('rejects missing required np_office', function () {
    $product = Product::factory()->create();
    $payload = validOrderPayload();
    $payload['np_office'] = '';

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set($payload)
        ->call('submit')
        ->assertHasErrors(['np_office']);
});

it('qty must be 1..5 inclusive', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())->set('qty', 0)->call('submit')->assertHasErrors(['qty']);

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())->set('qty', 6)->call('submit')->assertHasErrors(['qty']);
});

it('increment() and decrement() clamp qty to 1..5', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->assertSet('qty', 1)
        ->call('decrement')->assertSet('qty', 1)
        ->call('increment')->assertSet('qty', 2)
        ->call('increment')->call('increment')->call('increment')->call('increment')
        ->assertSet('qty', 5);
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='OrderFormTest'
```
Expected: FAIL — schema mismatch, increment/decrement undefined.

- [ ] **Step 3: Rewrite `OrderFormType` rules + attributes**

In `app/Forms/Types/OrderFormType.php`, replace `rules()` and `attributes()`:
```php
public function rules(?Model $subject = null): array
{
    return [
        'name' => ['required', 'string', 'max:120'],
        'phone' => ['required', 'string', 'max:40'],
        'email' => ['required', 'string', 'email:rfc', 'max:255'],
        'city' => ['required', 'string', 'max:120'],
        'np_office' => ['required', 'string', 'max:80'],
        'qty' => ['required', 'integer', 'min:1', 'max:5'],
        'comment' => ['nullable', 'string', 'max:1000'],
    ];
}

public function attributes(): array
{
    return [
        'name' => trans('forms.fields.name'),
        'phone' => trans('forms.fields.phone'),
        'email' => trans('forms.fields.email'),
        'city' => trans('forms.fields.city'),
        'np_office' => trans('forms.fields.np_office'),
        'qty' => trans('forms.fields.qty'),
        'comment' => trans('forms.fields.comment'),
    ];
}
```

- [ ] **Step 4: Rewrite `OrderForm` Livewire props + actions**

Replace `app/Forms/Livewire/OrderForm.php` body:
```php
<?php

namespace App\Forms\Livewire;

use App\Forms\Types\FormType;
use App\Forms\Types\OrderFormType;
use Illuminate\Contracts\View\View;

class OrderForm extends FormComponent
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $city = '';
    public string $np_office = '';
    public int $qty = 1;
    public string $comment = '';

    protected function formType(): FormType
    {
        return app(OrderFormType::class);
    }

    public function increment(): void
    {
        $this->qty = min(5, $this->qty + 1);
    }

    public function decrement(): void
    {
        $this->qty = max(1, $this->qty - 1);
    }

    public function getSubtotalProperty(): float
    {
        if (! $this->subject) {
            return 0;
        }
        return (float) $this->subject->displayPrice()['amount'] * $this->qty;
    }

    public function render(): View
    {
        return view('forms.order');
    }
}
```

- [ ] **Step 5: Update `lang/uk/forms.php` and `lang/en/forms.php` field labels**

In `lang/uk/forms.php`, under `fields`:
- Add `'city' => 'Місто',`
- Add `'np_office' => 'Відділення Нової Пошти',`
- Add `'comment' => 'Коментар',`
- Keep `qty` and `note` — `note` becomes unused but stays harmless.

Mirror to `lang/en/forms.php`:
- `'city' => 'City',`
- `'np_office' => 'Nova Poshta office',`
- `'comment' => 'Comment',`

- [ ] **Step 6: Run test, verify pass**

```bash
php artisan test --filter='OrderFormTest'
```
Expected: PASS (all 8 cases including new ones).

- [ ] **Step 7: Commit**

```bash
git add app/Forms/Types/OrderFormType.php app/Forms/Livewire/OrderForm.php \
        lang/uk/forms.php lang/en/forms.php \
        tests/Feature/Forms/OrderFormTest.php
git commit -m "forms: OrderForm schema (city + np_office + comment) + qty stepper"
```

---

## Task 8: Mailables dynamic subject + email templates with preorder notice

**Files:**
- Modify: `app/Forms/Mail/OrderAdminMail.php`
- Modify: `app/Forms/Mail/OrderClientMail.php`
- Modify: `resources/views/emails/forms/order-admin.blade.php`
- Modify: `resources/views/emails/forms/order-client.blade.php`
- Modify: `lang/uk/forms.php`, `lang/en/forms.php`
- Test: `tests/Feature/Forms/OrderFormTest.php` (extend)

- [ ] **Step 1: Add mail subject assertions to the test**

Append to `tests/Feature/Forms/OrderFormTest.php`:
```php
it('admin mail subject differs for order vs preorder', function () {
    config()->set('app.fallback_locale', 'uk');

    $inStock = Product::factory()->create(['in_stock' => true]);
    Livewire::test(OrderForm::class, ['subject' => $inStock])
        ->set(validOrderPayload())->call('submit');

    Mail::assertQueued(OrderAdminMail::class, function ($mail) {
        return str_contains($mail->envelope()->subject, 'замовлення')
            && ! str_contains($mail->envelope()->subject, 'Передзамовлення');
    });

    Mail::fake();
    RateLimiter::clear('forms:order:127.0.0.1');

    $outOfStock = Product::factory()->create(['in_stock' => false]);
    Livewire::test(OrderForm::class, ['subject' => $outOfStock])
        ->set(validOrderPayload())->call('submit');

    Mail::assertQueued(OrderAdminMail::class, function ($mail) {
        return str_contains($mail->envelope()->subject, 'передзамовлення');
    });
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='OrderFormTest'
```
Expected: FAIL on subject mismatch (both currently say «Нове замовлення»).

- [ ] **Step 3: Update `OrderAdminMail::envelope()`**

Replace `envelope()` in `app/Forms/Mail/OrderAdminMail.php`:
```php
public function envelope(): Envelope
{
    $isPreorder = (bool) ($this->submission->data['is_preorder'] ?? false);
    $key = $isPreorder ? 'preorder' : 'order';
    return new Envelope(subject: trans("forms.mail.{$key}.admin.subject"));
}
```

- [ ] **Step 4: Update `OrderClientMail::envelope()`**

Replace `envelope()` in `app/Forms/Mail/OrderClientMail.php`:
```php
public function envelope(): Envelope
{
    $isPreorder = (bool) ($this->submission->data['is_preorder'] ?? false);
    $key = $isPreorder ? 'preorder' : 'order';
    return new Envelope(subject: trans("forms.mail.{$key}.client.subject"));
}
```

- [ ] **Step 5: Add preorder translation keys**

In `lang/uk/forms.php`, under `mail`, add new top-level `preorder` block (mirror existing `order`):
```php
'preorder' => [
    'admin' => [
        'subject' => 'Нове передзамовлення',
        'intro' => 'Отримано нове передзамовлення (товар тимчасово відсутній).',
    ],
    'client' => [
        'subject' => 'Ми отримали ваше передзамовлення',
        'intro' => 'Дякуємо! Це передзамовлення — ми звʼяжемося, коли товар буде готовий до відправлення.',
    ],
],
```

In `lang/en/forms.php`:
```php
'preorder' => [
    'admin' => [
        'subject' => 'New preorder',
        'intro' => 'A new preorder has arrived (item temporarily out of stock).',
    ],
    'client' => [
        'subject' => 'We received your preorder',
        'intro' => 'Thank you. This is a preorder — we will reach out as soon as the item is ready to ship.',
    ],
],
```

- [ ] **Step 6: Update email templates to switch heading and add notice**

Replace `resources/views/emails/forms/order-admin.blade.php` content with:
```blade
@php($isPreorder = (bool) ($s->data['is_preorder'] ?? false))
@php($key = $isPreorder ? 'preorder' : 'order')
<x-mail::message>
# {{ trans("forms.mail.{$key}.admin.subject") }}

{{ trans("forms.mail.{$key}.admin.intro") }}

@if($isPreorder)
> {{ trans('forms.order.preorder_admin_notice') }}
@endif

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '' }}
**{{ trans('forms.fields.phone') }}:** {{ $s->data['phone'] ?? '' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '' }}
**{{ trans('forms.fields.city') }}:** {{ $s->data['city'] ?? '' }}
**{{ trans('forms.fields.np_office') }}:** {{ $s->data['np_office'] ?? '' }}
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '' }}

@if(! empty($s->data['comment']))
**{{ trans('forms.fields.comment') }}:**
{{ $s->data['comment'] }}
@endif

@if($s->subject)
---
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? ($s->subject->title ?? $s->subject->getKey()) }}
@endif
</x-mail::message>
```

Replace `resources/views/emails/forms/order-client.blade.php` content with:
```blade
@php($isPreorder = (bool) ($s->data['is_preorder'] ?? false))
@php($key = $isPreorder ? 'preorder' : 'order')
<x-mail::message>
# {{ trans("forms.mail.{$key}.client.subject") }}

{{ trans("forms.mail.{$key}.client.intro") }}

@if($isPreorder)
> {{ trans('forms.order.preorder_client_notice') }}
@endif

— LEVANT Parfums
</x-mail::message>
```

Add to `lang/uk/forms.php` (under a new `order` block alongside `fields`/`mail`):
```php
'order' => [
    'preorder_admin_notice' => 'Товар тимчасово відсутній на складі — це передзамовлення.',
    'preorder_client_notice' => 'Замовлений товар тимчасово відсутній. Ми зарезервуємо ваше передзамовлення та повідомимо про термін.',
],
```

Mirror in `lang/en/forms.php`:
```php
'order' => [
    'preorder_admin_notice' => 'Item is temporarily out of stock — this is a preorder.',
    'preorder_client_notice' => 'The item is temporarily out of stock. We will reserve your preorder and let you know when it is ready.',
],
```

- [ ] **Step 7: Run tests, verify pass**

```bash
php artisan test --filter='OrderFormTest'
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add app/Forms/Mail/ resources/views/emails/forms/ \
        lang/uk/forms.php lang/en/forms.php \
        tests/Feature/Forms/OrderFormTest.php
git commit -m "forms: dynamic mail subjects + preorder notices in templates"
```

---

## Task 9: `FormSubmissionResource` preorder badge column

**Files:**
- Modify: `app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php`
- Test: `tests/Feature/Forms/Filament/FormSubmissionResourceTest.php` (new — or extend if exists)

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Forms/Filament/FormSubmissionPreorderBadgeTest.php`:
```php
<?php

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('preorder column shows PRE-ORDER badge for preorder submissions', function () {
    $p = Product::factory()->create();
    $order = FormSubmission::create([
        'type' => 'order', 'status' => 'new', 'data' => ['is_preorder' => false],
        'subject_type' => $p->getMorphClass(), 'subject_id' => $p->id,
    ]);
    $preorder = FormSubmission::create([
        'type' => 'order', 'status' => 'new', 'data' => ['is_preorder' => true],
        'subject_type' => $p->getMorphClass(), 'subject_id' => $p->id,
    ]);

    Livewire::test(ListFormSubmissions::class)
        ->assertCanSeeTableRecords([$order, $preorder])
        ->assertTableColumnExists('preorder');
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='FormSubmissionPreorderBadgeTest'
```
Expected: FAIL.

- [ ] **Step 3: Add `preorder` column to FormSubmissionsTable**

In `app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php`, find the `columns([...])` array and insert:
```php
\Filament\Tables\Columns\TextColumn::make('preorder')
    ->label('')
    ->badge()
    ->color('warning')
    ->getStateUsing(fn ($record) => ($record->data['is_preorder'] ?? false) ? 'PRE-ORDER' : null),
```

Place it adjacent to the existing `type` column.

- [ ] **Step 4: Run test, verify pass**

```bash
php artisan test --filter='FormSubmissionPreorderBadgeTest'
```
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php \
        tests/Feature/Forms/Filament/FormSubmissionPreorderBadgeTest.php
git commit -m "filament: preorder badge column on form submissions table"
```

---

## Task 10: Route + `ProductCatalogController@show` + minimal `products/show.blade.php` + theme E2E test

**Files:**
- Modify: `routes/web.php`
- Modify: `app/Http/Controllers/ProductCatalogController.php`
- Create: `resources/views/products/show.blade.php` (minimal, theme switch only)
- Test: `tests/Feature/Public/ProductShowTest.php` (new)

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Public/ProductShowTest.php`:
```php
<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Database\Seeders\Catalogue\SeriesSeeder;

beforeEach(function () {
    (new SeriesSeeder())->run();
});

function publishedProductInSeries(string $seriesSlug, array $attrs = []): Product
{
    $s = Series::where('slug', $seriesSlug)->first();
    return Product::factory()->create(array_merge([
        'series_id' => $s->id, 'is_published' => true, 'published_at' => now()->subDay(),
    ], $attrs));
}

it('luxury product page returns 200 with theme-cream body class', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertOk()->assertSee('class="theme-cream"', false);
});

it('onyx product page returns 200 with theme-onyx body class', function () {
    $p = publishedProductInSeries('onyx');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertOk()->assertSee('class="theme-onyx"', false);
});

it('unpublished product returns 404', function () {
    $p = publishedProductInSeries('luxury', ['is_published' => false]);
    $this->get(route('products.show', $p->slug))->assertNotFound();
});

it('missing slug returns 404', function () {
    $this->get(route('products.show', 'nope-not-real'))->assertNotFound();
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL — route stub returns 404 for all.

- [ ] **Step 3: Replace route stub**

In `routes/web.php`, replace this line:
```php
Route::get('/products/{slug}', fn () => abort(404))
    ->where('slug', '[A-Za-z0-9\-_]+')
    ->name('products.show');
```
with:
```php
Route::get('/products/{product:slug}', [ProductCatalogController::class, 'show'])
    ->where('product', '[A-Za-z0-9\-_]+')
    ->name('products.show');
```

- [ ] **Step 4: Add `show()` method to controller**

Append to `app/Http/Controllers/ProductCatalogController.php`:
```php
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

        return view('products.show', compact('product', 'related', 'theme'));
    }
```

- [ ] **Step 5: Create minimal `resources/views/products/show.blade.php`**

```blade
@extends('layouts.site', ['theme' => $theme])

@section('title', $product->name . ' · LEVANT Parfums')

@section('content')
    <div class="product-page">
        <div class="container">
            <h1>{{ $product->name }}</h1>
            <p>{{ $product->tagline }}</p>
        </div>
    </div>
@endsection
```

- [ ] **Step 6: Run test, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS — 4 tests.

- [ ] **Step 7: Commit**

```bash
git add routes/web.php app/Http/Controllers/ProductCatalogController.php \
        resources/views/products/show.blade.php tests/Feature/Public/ProductShowTest.php
git commit -m "products/show: route + controller + theme switching E2E"
```

---

## Task 11: Universal `<x-site.breadcrumbs>` component + refactor catalog

**Files:**
- Create: `resources/views/components/site/breadcrumbs.blade.php`
- Modify: `resources/views/products/index.blade.php` (replace inline `.crumbs` block)
- Test: `tests/Feature/Public/ProductCatalogTest.php` (verify no regression — should already exist)

- [ ] **Step 1: Create breadcrumbs component**

Create `resources/views/components/site/breadcrumbs.blade.php`:
```blade
@props(['items' => []])

<nav class="crumbs" aria-label="breadcrumb">
    @foreach($items as $i => $item)
        @if($i > 0)<span class="sep">/</span>@endif
        @if(!empty($item['href']))
            <a href="{{ $item['href'] }}">{{ $item['label'] }}</a>
        @else
            <span class="current">{{ $item['label'] }}</span>
        @endif
    @endforeach
</nav>
```

- [ ] **Step 2: Refactor catalog page to use it**

In `resources/views/products/index.blade.php`, replace the existing `<nav class="crumbs" …>…</nav>` block with:
```blade
<x-site.breadcrumbs :items="[
    ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('catalogue.public.crumb_home')],
    ['label' => __('catalogue.public.title')],
]"/>
```

- [ ] **Step 3: Run existing tests for regression**

```bash
php artisan test --filter='ProductCatalogTest'
```
Expected: PASS — catalog still renders breadcrumbs.

- [ ] **Step 4: Commit**

```bash
git add resources/views/components/site/breadcrumbs.blade.php resources/views/products/index.blade.php
git commit -m "site: universal breadcrumbs component, catalog uses it"
```

---

## Task 12: `<x-site.product-gallery>` + lightbox

**Files:**
- Create: `resources/views/components/site/product-gallery.blade.php`
- Create: `resources/css/site/components/lightbox.css`
- Create: `resources/js/site/lightbox.js`
- Modify: `resources/css/site/index.css` (add `@import './components/lightbox.css';`)
- Modify: `resources/js/app.js` (add `import './site/lightbox.js';`)
- Test: extend `tests/Feature/Public/ProductShowTest.php`

- [ ] **Step 1: Write failing test (extend ProductShowTest)**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('gallery renders main image as data-lightbox-trigger button', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee('data-lightbox-trigger', false);
    $r->assertSee('data-lightbox-images', false);
});
```

- [ ] **Step 2: Run test, verify it fails**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL — gallery not yet in show view.

- [ ] **Step 3: Create gallery component**

Create `resources/views/components/site/product-gallery.blade.php`:
```blade
@props(['product'])

@php
    $gallery = $product->getMedia('gallery');
    if ($gallery->isEmpty() && $product->getFirstMedia('primary')) {
        $gallery = collect([$product->getFirstMedia('primary')]);
    }
    $urls = $gallery->map(fn ($m) => $m->getUrl('detail'))->values()->all();
    $cardUrls = $gallery->map(fn ($m) => $m->getUrl('thumb'))->values()->all();
@endphp

<div class="gallery">
    @if(count($urls) > 1)
        <div class="thumbs">
            @foreach($cardUrls as $i => $thumb)
                <button type="button" class="{{ $i === 0 ? 'active' : '' }}" data-thumb-index="{{ $i }}">
                    <img src="{{ $thumb }}" alt="">
                </button>
            @endforeach
        </div>
    @endif

    <button
        type="button"
        class="main-img"
        data-lightbox-trigger
        data-lightbox-images='@json($urls)'
        data-lightbox-index="0"
        @aria-label="__('catalogue.public.product.gallery_open')"
    >
        @if(! empty($urls))
            <img src="{{ $urls[0] }}" alt="{{ $product->name }}" data-main-image>
        @else
            <span class="placeholder"></span>
        @endif
        <span class="zoom-hint">{{ __('catalogue.public.product.gallery_zoom') }}</span>
    </button>
</div>
```

- [ ] **Step 4: Add gallery to product/show.blade.php**

Replace the body of `resources/views/products/show.blade.php` with:
```blade
@extends('layouts.site', ['theme' => $theme])

@section('title', $product->name . ' · LEVANT Parfums')
@section('description', $product->tagline ?: \Illuminate\Support\Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
    <div class="product-page">
        <div class="container">
            <x-site.breadcrumbs :items="[
                ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('catalogue.public.crumb_home')],
                ['href' => route('products.index'), 'label' => __('catalogue.public.title')],
                ['href' => route('products.index', ['series' => $product->series?->slug]),
                 'label' => $product->series?->name ?? ''],
                ['label' => $product->name],
            ]"/>

            <div class="top">
                <x-site.product-gallery :product="$product"/>
                <div class="info"><h1>{{ $product->name }}</h1></div>
            </div>
        </div>
    </div>
@endsection
```

- [ ] **Step 5: Add translation keys**

In `lang/uk/catalogue.php` under `public` add:
```php
'product' => [
    'gallery_open' => 'Відкрити зображення',
    'gallery_zoom' => 'Натисніть, щоб збільшити',
],
```

Mirror in `lang/en/catalogue.php`:
```php
'product' => [
    'gallery_open' => 'Open image',
    'gallery_zoom' => 'Click to enlarge',
],
```

- [ ] **Step 6: Create `resources/js/site/lightbox.js`**

```js
const html = (urls, index) => `
  <div class="lightbox" data-lightbox-overlay>
    <button type="button" class="close" data-lightbox-close aria-label="Close">×</button>
    <button type="button" class="nav-l" data-lightbox-prev aria-label="Previous">‹</button>
    <button type="button" class="nav-r" data-lightbox-next aria-label="Next">›</button>
    <img src="${urls[index]}" alt="">
    <div class="counter">${String(index + 1).padStart(2, '0')} / ${String(urls.length).padStart(2, '0')}</div>
  </div>`;

let activeOverlay = null;
let activeUrls = [];
let activeIndex = 0;

function open(urls, index) {
  activeUrls = urls;
  activeIndex = index;
  document.body.insertAdjacentHTML('beforeend', html(urls, index));
  activeOverlay = document.querySelector('[data-lightbox-overlay]');
  document.addEventListener('keydown', onKey);
}

function close() {
  if (!activeOverlay) return;
  document.removeEventListener('keydown', onKey);
  activeOverlay.remove();
  activeOverlay = null;
}

function go(delta) {
  if (!activeOverlay || !activeUrls.length) return;
  activeIndex = (activeIndex + delta + activeUrls.length) % activeUrls.length;
  activeOverlay.querySelector('img').src = activeUrls[activeIndex];
  activeOverlay.querySelector('.counter').textContent =
    `${String(activeIndex + 1).padStart(2, '0')} / ${String(activeUrls.length).padStart(2, '0')}`;
}

function onKey(e) {
  if (e.key === 'Escape') close();
  if (e.key === 'ArrowLeft') go(-1);
  if (e.key === 'ArrowRight') go(1);
}

document.addEventListener('click', (e) => {
  const trigger = e.target.closest('[data-lightbox-trigger]');
  if (trigger) {
    e.preventDefault();
    let urls = [];
    try { urls = JSON.parse(trigger.dataset.lightboxImages || '[]'); } catch { return; }
    if (! urls.length) return;
    open(urls, parseInt(trigger.dataset.lightboxIndex || '0', 10));
    return;
  }
  if (activeOverlay) {
    if (e.target.closest('[data-lightbox-close]')) close();
    else if (e.target.closest('[data-lightbox-prev]')) go(-1);
    else if (e.target.closest('[data-lightbox-next]')) go(1);
    else if (e.target === activeOverlay) close();
  }
});

document.addEventListener('click', (e) => {
  const thumb = e.target.closest('[data-thumb-index]');
  if (! thumb) return;
  const trigger = thumb.closest('.gallery')?.querySelector('[data-lightbox-trigger]');
  if (! trigger) return;
  let urls = [];
  try { urls = JSON.parse(trigger.dataset.lightboxImages || '[]'); } catch { return; }
  const i = parseInt(thumb.dataset.thumbIndex, 10);
  if (Number.isNaN(i) || ! urls[i]) return;
  trigger.dataset.lightboxIndex = String(i);
  trigger.querySelector('[data-main-image]').src = urls[i];
  trigger.closest('.gallery').querySelectorAll('.thumbs button').forEach((b, j) =>
    b.classList.toggle('active', j === i));
});
```

- [ ] **Step 7: Create `resources/css/site/components/lightbox.css`**

```css
.lightbox {
  position: fixed; inset: 0; z-index: 9999;
  background: rgba(0,0,0,0.92);
  display: flex; align-items: center; justify-content: center;
}
.lightbox img { max-width: 90vw; max-height: 90vh; object-fit: contain; }
.lightbox .close, .lightbox .nav-l, .lightbox .nav-r {
  position: absolute; background: transparent; border: 1px solid rgba(255,255,255,0.3);
  color: #fff; width: 48px; height: 48px; font-size: 22px;
  display: flex; align-items: center; justify-content: center; cursor: pointer;
}
.lightbox .close { top: 24px; right: 24px; }
.lightbox .nav-l { left: 24px; top: 50%; transform: translateY(-50%); }
.lightbox .nav-r { right: 24px; top: 50%; transform: translateY(-50%); }
.lightbox .counter {
  position: absolute; bottom: 24px; left: 50%; transform: translateX(-50%);
  color: #fff; font-family: var(--font-mono); font-size: 12px; letter-spacing: 0.2em;
}
```

- [ ] **Step 8: Wire imports**

Add to `resources/css/site/index.css` (after existing component imports):
```css
@import './components/lightbox.css';
```

Add to `resources/js/app.js`:
```js
import './site/lightbox.js';
```

- [ ] **Step 9: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add resources/views/components/site/product-gallery.blade.php \
        resources/views/products/show.blade.php \
        resources/css/site/components/lightbox.css resources/css/site/index.css \
        resources/js/site/lightbox.js resources/js/app.js \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Public/ProductShowTest.php
git commit -m "product-page: gallery component + lightbox JS module"
```

---

## Task 13: `<x-site.product-info>` + `product.css` + CTA preorder switch

**Files:**
- Create: `resources/views/components/site/product-info.blade.php`
- Create: `resources/css/site/pages/product.css`
- Modify: `resources/css/site/index.css`
- Modify: `resources/views/products/show.blade.php`
- Modify: `lang/uk/catalogue.php`, `lang/en/catalogue.php`
- Test: extend `tests/Feature/Public/ProductShowTest.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('renders product name, tagline, description', function () {
    $p = publishedProductInSeries('luxury', [
        'name' => ['uk' => 'Luxury № 01', 'en' => 'Luxury № 01'],
        'tagline' => ['uk' => 'Тиха ясність', 'en' => 'Quiet clarity'],
        'description' => ['uk' => 'Опис українською', 'en' => 'Description in English'],
    ]);

    $this->get(route('products.show', $p->slug))
        ->assertSee('Luxury № 01')
        ->assertSee('Тиха ясність')
        ->assertSee('Опис українською');
});

it('shows order CTA when in_stock=true', function () {
    $p = publishedProductInSeries('luxury', ['in_stock' => true]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.order_cta'));
    $r->assertDontSee(__('catalogue.public.product.preorder_cta'));
});

it('shows preorder CTA + btn-secondary when in_stock=false', function () {
    $p = publishedProductInSeries('luxury', ['in_stock' => false]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.preorder_cta'));
    $r->assertSee('btn-secondary', false);
});

it('hides why-block when why is null', function () {
    $p = publishedProductInSeries('luxury', ['why' => null]);
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.why_label'));
});
```

- [ ] **Step 2: Run tests, verify they fail**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL.

- [ ] **Step 3: Create `<x-site.product-info>`**

Create `resources/views/components/site/product-info.blade.php`:
```blade
@props(['product'])

@php
    $firstOccasion = $product->relationLoaded('occasions') ? $product->occasions->first() : null;
    $tags = $product->relationLoaded('tags') ? $product->tags : collect();
    $isNew = $tags->contains('slug', 'new');
    $isBest = $tags->contains('slug', 'bestseller');
@endphp

<div class="info">
    @if($product->series)
        <div class="series">— {{ $product->series->name }}</div>
    @endif

    <h1 class="display-italic">{{ $product->name }}</h1>

    @if($product->tagline)
        <div class="subtitle">{{ $product->tagline }}</div>
    @endif

    @if($product->character || $firstOccasion)
        <div class="character-line">
            @if($product->character)
                <span class="accent">{{ $product->character }}</span>
            @endif
            @if($product->character && $firstOccasion) · @endif
            @if($firstOccasion)
                <span>{{ $firstOccasion->name }}</span>
            @endif
        </div>
    @endif

    @if($isNew || $isBest)
        <div class="badges">
            @if($isNew)<span class="badge badge-new">{{ __('catalogue.public.product.badges.new') }}</span>@endif
            @if($isBest)<span class="badge badge-best">{{ __('catalogue.public.product.badges.best') }}</span>@endif
        </div>
    @endif

    @php($price = $product->displayPrice())
    <div class="price-row">
        <div class="price">{{ number_format((float) $price['amount'], 0, ',', ' ') }} {{ $price['currency'] }}</div>
        <div class="vol">{{ $product->volume_ml }} ml · eau de parfum</div>
    </div>

    @if($product->description)
        <p class="desc">{{ $product->description }}</p>
    @endif

    @if($product->why)
        <div class="why-block">
            <div class="l">{{ __('catalogue.public.product.why_label') }}</div>
            <p>{{ $product->why }}</p>
        </div>
    @endif

    <div class="specs">
        @if($product->sku)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.sku') }}</span><span class="v">{{ $product->sku }}</span></div>
        @endif
        <div class="row"><span class="l">{{ __('catalogue.public.product.specs.volume') }}</span><span class="v">{{ $product->volume_ml }} ml</span></div>
        @if($product->perfumeFamily)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.family') }}</span><span class="v">{{ $product->perfumeFamily->name }}</span></div>
        @endif
        @if($product->concentration)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.concentration') }}</span><span class="v">{{ $product->concentration->name }}</span></div>
        @endif
        <div class="row"><span class="l">{{ __('catalogue.public.product.specs.composed') }}</span><span class="v">{{ __('catalogue.public.product.specs.composed_value') }}</span></div>
        @if($product->series)
            <div class="row"><span class="l">{{ __('catalogue.public.product.specs.series') }}</span><span class="v">{{ $product->series->name }}</span></div>
        @endif
    </div>

    <div class="cta-row">
        @if($product->in_stock)
            <a href="#order" class="btn">{{ __('catalogue.public.product.order_cta') }}</a>
        @else
            <a href="#order" class="btn btn-secondary">{{ __('catalogue.public.product.preorder_cta') }}</a>
        @endif
    </div>
</div>
```

- [ ] **Step 4: Add translation keys**

In `lang/uk/catalogue.php`, extend `public.product`:
```php
'why_label' => 'Чому саме цей',
'order_cta' => 'Замовити',
'preorder_cta' => 'Передзамовити',
'badges' => ['new' => 'Новинка', 'best' => 'Бестселер'],
'specs' => [
    'sku' => 'Артикул',
    'volume' => 'Обʼєм',
    'family' => 'Родина',
    'concentration' => 'Концентрація',
    'composed' => 'Розроблено',
    'composed_value' => 'Іспанія / ES',
    'series' => 'Серія',
],
```

Mirror in `lang/en/catalogue.php`:
```php
'why_label' => 'Why this one',
'order_cta' => 'Order',
'preorder_cta' => 'Preorder',
'badges' => ['new' => 'New', 'best' => 'Bestseller'],
'specs' => [
    'sku' => 'SKU',
    'volume' => 'Volume',
    'family' => 'Family',
    'concentration' => 'Concentration',
    'composed' => 'Composed',
    'composed_value' => 'Spain / ES',
    'series' => 'Series',
],
```

- [ ] **Step 5: Create `resources/css/site/pages/product.css`**

```css
.product-page .top {
  display: grid;
  grid-template-columns: minmax(0, 1.1fr) minmax(0, 0.9fr);
  gap: 64px;
  margin-top: 48px;
}
@media (max-width: 880px) {
  .product-page .top { grid-template-columns: 1fr; gap: 32px; }
}

.product-page .info .series {
  font-family: var(--font-mono); font-size: 11px;
  letter-spacing: 0.24em; text-transform: uppercase;
  color: var(--accent); margin-bottom: 18px;
}
.product-page .info .display-italic {
  font-family: var(--font-serif); font-style: italic;
  font-weight: 300; font-size: 56px; line-height: 1.05; margin: 0;
}
.product-page .info .subtitle {
  margin-top: 14px; font-family: var(--font-serif);
  font-size: 18px; color: var(--ink-soft);
}
.product-page .info .character-line {
  margin-top: 18px; font-size: 13px; color: var(--ink-mute);
  letter-spacing: 0.04em;
}
.product-page .info .character-line .accent { color: var(--ink-soft); }
.product-page .info .badges { margin-top: 18px; display: flex; gap: 10px; }
.product-page .info .badge {
  display: inline-block; padding: 6px 12px; font-size: 10px;
  letter-spacing: 0.22em; text-transform: uppercase; border: 1px solid var(--line);
}
.product-page .info .badge-new { background: var(--accent); color: var(--accent-ink); border-color: var(--accent); }
.product-page .info .badge-best { background: var(--card); color: var(--ink); }

.product-page .info .price-row {
  margin-top: 28px; display: flex; align-items: baseline; gap: 16px;
  padding-block: 18px; border-top: 1px solid var(--line-soft); border-bottom: 1px solid var(--line-soft);
}
.product-page .info .price { font-family: var(--font-serif); font-size: 32px; color: var(--accent); }
.product-page .info .vol {
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.18em;
  text-transform: uppercase; color: var(--ink-mute);
}

.product-page .info .desc { margin-top: 20px; color: var(--ink-soft); }

.product-page .info .why-block {
  margin-top: 28px; padding: 20px 22px;
  background: var(--bg-2); border-left: 2px solid var(--accent);
}
.product-page .info .why-block .l {
  font-family: var(--font-mono); font-size: 10px;
  letter-spacing: 0.24em; text-transform: uppercase; color: var(--accent);
}
.product-page .info .why-block p { margin: 8px 0 0; }

.product-page .info .specs { margin-top: 32px; display: grid; gap: 8px; }
.product-page .info .specs .row {
  display: grid; grid-template-columns: 160px 1fr; gap: 16px;
  padding-block: 10px; border-bottom: 1px dotted var(--line-soft);
}
.product-page .info .specs .l {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--ink-mute);
}

.product-page .info .cta-row { margin-top: 36px; display: flex; gap: 14px; flex-wrap: wrap; }

.product-page .gallery .thumbs {
  display: flex; gap: 8px; margin-bottom: 12px;
}
.product-page .gallery .thumbs button {
  width: 64px; height: 64px; padding: 0; border: 1px solid var(--line-soft);
  background: var(--card); cursor: pointer;
}
.product-page .gallery .thumbs button.active { border-color: var(--accent); }
.product-page .gallery .thumbs img { width: 100%; height: 100%; object-fit: cover; }
.product-page .gallery .main-img {
  width: 100%; aspect-ratio: 3/4; padding: 0; border: 0;
  background: var(--bg-2); position: relative; cursor: zoom-in;
}
.product-page .gallery .main-img img { width: 100%; height: 100%; object-fit: cover; }
.product-page .gallery .zoom-hint {
  position: absolute; bottom: 12px; left: 12px;
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.18em;
  color: var(--ink-mute);
}
```

- [ ] **Step 6: Wire CSS import**

Add to `resources/css/site/index.css` (after existing page imports):
```css
@import './pages/product.css';
```

- [ ] **Step 7: Update `products/show.blade.php` to use `<x-site.product-info>`**

Replace the `<div class="top">` block:
```blade
<div class="top">
    <x-site.product-gallery :product="$product"/>
    <x-site.product-info :product="$product"/>
</div>
```

- [ ] **Step 8: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS.

- [ ] **Step 9: Commit**

```bash
git add resources/views/components/site/product-info.blade.php \
        resources/css/site/pages/product.css resources/css/site/index.css \
        resources/views/products/show.blade.php \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Public/ProductShowTest.php
git commit -m "product-page: info component + product.css + CTA preorder/order switch"
```

---

## Task 14: `<x-site.product-pyramid>` + `pyramid.css`

**Files:**
- Create: `resources/views/components/site/product-pyramid.blade.php`
- Create: `resources/css/site/components/pyramid.css`
- Modify: `resources/css/site/index.css`
- Modify: `resources/views/products/show.blade.php`
- Modify: `lang/uk/catalogue.php`, `lang/en/catalogue.php`
- Test: extend `tests/Feature/Public/ProductShowTest.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('renders pyramid section when product has notes', function () {
    $p = publishedProductInSeries('luxury');
    $note = \App\Models\Catalogue\Note::factory()->create(['name' => ['uk' => 'Бергамот', 'en' => 'Bergamot']]);
    $p->notes()->attach($note, ['level' => 'top', 'sort_order' => 0]);

    $this->get(route('products.show', $p->slug))
        ->assertSee(__('catalogue.public.product.pyramid.title'))
        ->assertSee('Бергамот');
});

it('hides pyramid when product has no notes', function () {
    $p = publishedProductInSeries('luxury');
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.pyramid.title'));
});
```

- [ ] **Step 2: Run, verify fail**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL.

- [ ] **Step 3: Create pyramid component**

Create `resources/views/components/site/product-pyramid.blade.php`:
```blade
@props(['product'])

@php
    use App\Enums\NoteLevel;
    $top = $product->notesByLevel(NoteLevel::Top)->get();
    $heart = $product->notesByLevel(NoteLevel::Heart)->get();
    $base = $product->notesByLevel(NoteLevel::Base)->get();
@endphp

<div class="pyramid">
    <div>
        <div class="eyebrow">{{ __('catalogue.public.product.pyramid.title') }}</div>
        <h2 style="margin-top:16px">{{ __('catalogue.public.product.pyramid.subtitle') }}</h2>
    </div>
    <div class="levels">
        @if($top->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.top') }}</div>
                <div class="notes">
                    @foreach($top as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
        @if($heart->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.heart') }}</div>
                <div class="notes">
                    @foreach($heart as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
        @if($base->isNotEmpty())
            <div class="level">
                <div class="lbl">{{ __('catalogue.public.product.pyramid.base') }}</div>
                <div class="notes">
                    @foreach($base as $n)<span class="note">{{ $n->name }}</span>@endforeach
                </div>
            </div>
        @endif
    </div>
</div>
```

- [ ] **Step 4: Add translation keys**

In `lang/uk/catalogue.php` under `public.product` add:
```php
'pyramid' => [
    'title' => 'Піраміда нот',
    'subtitle' => 'Як аромат розкривається на шкірі',
    'top' => 'Верхні',
    'heart' => 'Серцеві',
    'base' => 'Базові',
],
```

Mirror in `lang/en/catalogue.php`:
```php
'pyramid' => [
    'title' => 'The pyramid',
    'subtitle' => 'How the scent unfolds on skin',
    'top' => 'Top',
    'heart' => 'Heart',
    'base' => 'Base',
],
```

- [ ] **Step 5: Create `resources/css/site/components/pyramid.css`**

```css
.pyramid {
  margin-top: 80px; display: grid; gap: 48px;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1.6fr);
  padding-block: 56px; border-top: 1px solid var(--line-soft);
}
@media (max-width: 880px) { .pyramid { grid-template-columns: 1fr; gap: 32px; } }
.pyramid h2 { font-family: var(--font-serif); font-weight: 300; font-size: 32px; }
.pyramid .levels { display: grid; gap: 36px; }
.pyramid .level .lbl {
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.24em;
  text-transform: uppercase; color: var(--accent); margin-bottom: 12px;
}
.pyramid .notes { display: flex; flex-wrap: wrap; gap: 8px; }
.pyramid .note {
  padding: 8px 14px; border: 1px solid var(--line);
  font-size: 13px; background: var(--card);
}
```

- [ ] **Step 6: Wire CSS + add component to `show.blade.php`**

Add to `resources/css/site/index.css`:
```css
@import './components/pyramid.css';
```

In `resources/views/products/show.blade.php`, after `<div class="top">…</div>`, add:
```blade
@if($product->notes->isNotEmpty())
    <x-site.product-pyramid :product="$product"/>
@endif
```

- [ ] **Step 7: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/site/product-pyramid.blade.php \
        resources/css/site/components/pyramid.css resources/css/site/index.css \
        resources/views/products/show.blade.php \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Public/ProductShowTest.php
git commit -m "product-page: pyramid component (top/heart/base notes)"
```

---

## Task 15: `<x-site.product-character>` + `character-bars.css`

**Files:**
- Create: `resources/views/components/site/product-character.blade.php`
- Create: `resources/css/site/components/character-bars.css`
- Modify: `resources/css/site/index.css`
- Modify: `resources/views/products/show.blade.php`
- Modify: `lang/uk/catalogue.php`, `lang/en/catalogue.php`
- Test: extend `tests/Feature/Public/ProductShowTest.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('renders sillage bar when sillage_score set', function () {
    $p = publishedProductInSeries('luxury', ['sillage_score' => 4]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.character.sillage_label'));
    $r->assertSee(__('catalogue.public.product.character.sillage_words.4'));
});

it('renders longevity bar when longevity_hours set', function () {
    $p = publishedProductInSeries('luxury', ['longevity_hours' => 10]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.character.longevity_label'));
});

it('hides character section when both sillage and longevity are null', function () {
    $p = publishedProductInSeries('luxury', ['sillage_score' => null, 'longevity_hours' => null]);
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.character.sillage_label'))
        ->assertDontSee(__('catalogue.public.product.character.longevity_label'));
});
```

- [ ] **Step 2: Run, verify fail**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL.

- [ ] **Step 3: Create character component**

Create `resources/views/components/site/product-character.blade.php`:
```blade
@props(['product'])

<div class="character">
    @if($product->sillage_score)
        @php($s = (int) $product->sillage_score)
        <div class="bar-row">
            <div class="top">
                <span class="l">{{ __('catalogue.public.product.character.sillage_label') }}</span>
                <span class="v">{{ __("catalogue.public.product.character.sillage_words.{$s}") }}</span>
            </div>
            <div class="bar"><div class="fill" style="width: {{ ($s / 5) * 100 }}%"></div></div>
            <div class="ticks">
                @for($i = 1; $i <= 5; $i++)<span>{{ $i }}</span>@endfor
            </div>
        </div>
    @endif

    @if($product->longevity_hours)
        @php($h = (int) $product->longevity_hours)
        <div class="bar-row">
            <div class="top">
                <span class="l">{{ __('catalogue.public.product.character.longevity_label') }}</span>
                <span class="v">{{ $h }}+ {{ __('catalogue.public.product.character.longevity_word_h') }}</span>
            </div>
            <div class="bar"><div class="fill" style="width: {{ ($h / 12) * 100 }}%"></div></div>
            <div class="ticks">
                <span>2h</span><span>4h</span><span>6h</span><span>8h</span><span>10h</span><span>12h</span>
            </div>
        </div>
    @endif
</div>
```

- [ ] **Step 4: Add translation keys**

In `lang/uk/catalogue.php` under `public.product` add:
```php
'character' => [
    'sillage_label' => 'Шлейф',
    'longevity_label' => 'Стійкість',
    'longevity_word_h' => 'год',
    'sillage_words' => [
        1 => 'Поряд зі шкірою',
        2 => 'Близько',
        3 => 'Помірно',
        4 => 'Сильно',
        5 => 'Тяжко',
    ],
],
```

Mirror in `lang/en/catalogue.php`:
```php
'character' => [
    'sillage_label' => 'Sillage',
    'longevity_label' => 'Longevity',
    'longevity_word_h' => 'h',
    'sillage_words' => [
        1 => 'Skin',
        2 => 'Close',
        3 => 'Moderate',
        4 => 'Strong',
        5 => 'Heavy',
    ],
],
```

- [ ] **Step 5: Create `resources/css/site/components/character-bars.css`**

```css
.character {
  margin-top: 64px; padding-block: 56px; border-top: 1px solid var(--line-soft);
  display: grid; gap: 36px;
}
.character .bar-row { display: grid; gap: 8px; }
.character .bar-row .top {
  display: flex; justify-content: space-between; align-items: baseline;
}
.character .bar-row .l {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.24em;
  text-transform: uppercase; color: var(--ink-mute);
}
.character .bar-row .v {
  font-family: var(--font-serif); font-size: 18px; color: var(--accent);
}
.character .bar {
  height: 2px; background: var(--line-soft); position: relative;
}
.character .bar .fill { height: 100%; background: var(--accent); }
.character .ticks {
  display: flex; justify-content: space-between;
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.18em;
  color: var(--ink-mute);
}
```

- [ ] **Step 6: Wire CSS + add component to show.blade.php**

Add to `resources/css/site/index.css`:
```css
@import './components/character-bars.css';
```

In `resources/views/products/show.blade.php`, after the pyramid `@if`, add:
```blade
@if($product->sillage_score || $product->longevity_hours)
    <x-site.product-character :product="$product"/>
@endif
```

- [ ] **Step 7: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/site/product-character.blade.php \
        resources/css/site/components/character-bars.css resources/css/site/index.css \
        resources/views/products/show.blade.php \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Public/ProductShowTest.php
git commit -m "product-page: character bars (sillage + longevity)"
```

---

## Task 16: `order.blade.php` redesign + `order-form.css` + `qty-stepper.css`

**Files:**
- Modify: `resources/views/forms/order.blade.php`
- Create: `resources/css/site/components/order-form.css`
- Create: `resources/css/site/components/qty-stepper.css`
- Modify: `resources/css/site/index.css`
- Modify: `resources/views/products/show.blade.php`
- Modify: `lang/uk/forms.php`, `lang/en/forms.php`
- Test: extend `tests/Feature/Public/ProductShowTest.php` and `OrderFormTest.php`

- [ ] **Step 1: Add failing tests**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('mounts Livewire order-form with product as subject', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSeeLivewire(\App\Forms\Livewire\OrderForm::class);
});

it('order form section is anchorable via #order', function () {
    $p = publishedProductInSeries('luxury');
    $this->get(route('products.show', $p->slug))->assertSee('id="order"', false);
});
```

Append to `tests/Feature/Forms/OrderFormTest.php`:
```php
it('thank-you state shows LV-{padded id} after submit', function () {
    $product = Product::factory()->create();
    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set(validOrderPayload())
        ->call('submit')
        ->assertSee('LV-'); // exact id format covered indirectly; safe to assert prefix
});
```

- [ ] **Step 2: Run, verify fail**

```bash
php artisan test --filter='ProductShowTest|OrderFormTest'
```
Expected: FAIL.

- [ ] **Step 3: Replace `resources/views/forms/order.blade.php`**

```blade
@php
    $isPreorder = ! ($subject?->in_stock ?? true);
    $titleKey = $isPreorder ? 'preorder' : 'order';
@endphp

@if (session('forms.success.order'))
    @php($latestId = \App\Forms\Models\FormSubmission::query()->latest('id')->value('id'))
    <div class="order-thanks">
        <div class="ok">✓</div>
        <h3>{{ trans("forms.order.thanks.{$titleKey}") }}</h3>
        @if($latestId)
            <p class="number">LV-{{ str_pad((string) $latestId, 4, '0', STR_PAD_LEFT) }}</p>
        @endif
    </div>
@else
    <form class="order-form" wire:submit="submit">
        <x-forms.honeypot wire:model="hp" />

        @error('form') <div class="alert" data-testid="form-error">{{ $message }}</div> @enderror

        <div class="intro">
            <div class="eyebrow">{{ trans("forms.order.eyebrow.{$titleKey}") }}</div>
            <h2>{{ trans("forms.order.title.{$titleKey}") }}</h2>
            <p>{{ trans("forms.order.intro.{$titleKey}") }}</p>

            @if($subject)
                <div class="summary">
                    @if($subject->getFirstMediaUrl('primary', 'thumb'))
                        <div class="img"><img src="{{ $subject->getFirstMediaUrl('primary', 'thumb') }}" alt=""></div>
                    @endif
                    <div>
                        <div class="title" data-testid="subject-name">{{ $subject->name }}</div>
                        <div class="meta">{{ $subject->volume_ml }} ml · eau de parfum</div>
                        @php($price = $subject->displayPrice())
                        <div class="price">{{ number_format((float) $price['amount'], 0, ',', ' ') }} {{ $price['currency'] }}</div>

                        <div class="qty-stepper">
                            <span class="l">{{ trans('forms.fields.qty') }}</span>
                            <button type="button" wire:click="decrement" aria-label="−">−</button>
                            <span class="v">{{ $qty }}</span>
                            <button type="button" wire:click="increment" aria-label="+">+</button>
                        </div>

                        <div class="subtotal">
                            <span class="l">{{ trans('forms.order.subtotal') }}</span>
                            <span class="v">{{ number_format($this->subtotal, 0, ',', ' ') }} {{ $price['currency'] }}</span>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <div class="fields">
            <label class="field full">
                <span>{{ trans('forms.fields.name') }} *</span>
                <input type="text" wire:model="name" required>
                @error('name') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.phone') }} *</span>
                <input type="tel" wire:model="phone" required>
                @error('phone') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.email') }} *</span>
                <input type="email" wire:model="email" required>
                @error('email') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.city') }} *</span>
                <input type="text" wire:model="city" required>
                @error('city') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field">
                <span>{{ trans('forms.fields.np_office') }} *</span>
                <input type="text" wire:model="np_office" required>
                @error('np_office') <span class="err">{{ $message }}</span> @enderror
            </label>
            <label class="field full">
                <span>{{ trans('forms.fields.comment') }}</span>
                <textarea wire:model="comment" rows="3"></textarea>
            </label>

            <div class="actions">
                <button type="submit" class="btn">{{ trans("forms.order.submit.{$titleKey}") }}</button>
            </div>
        </div>
    </form>
@endif
```

- [ ] **Step 4: Add translation keys**

Append to `lang/uk/forms.php` `order` block:
```php
'order' => [
    'preorder_admin_notice' => 'Товар тимчасово відсутній на складі — це передзамовлення.',
    'preorder_client_notice' => 'Замовлений товар тимчасово відсутній. Ми зарезервуємо ваше передзамовлення.',
    'eyebrow' => ['order' => 'Замовлення', 'preorder' => 'Передзамовлення'],
    'title' => ['order' => 'Оформити замовлення', 'preorder' => 'Оформити передзамовлення'],
    'intro' => [
        'order' => 'Залиште контакти — і ми звʼяжемось протягом дня для підтвердження.',
        'preorder' => 'Залиште контакти — ми зарезервуємо позицію та повідомимо про термін.',
    ],
    'submit' => ['order' => 'Замовити', 'preorder' => 'Передзамовити'],
    'thanks' => ['order' => 'Дякуємо за замовлення', 'preorder' => 'Дякуємо за передзамовлення'],
    'subtotal' => 'Разом',
],
```

Mirror in `lang/en/forms.php`:
```php
'order' => [
    'preorder_admin_notice' => 'Item is temporarily out of stock — this is a preorder.',
    'preorder_client_notice' => 'Item is temporarily out of stock. We will reserve your preorder.',
    'eyebrow' => ['order' => 'Order', 'preorder' => 'Preorder'],
    'title' => ['order' => 'Place an order', 'preorder' => 'Place a preorder'],
    'intro' => [
        'order' => 'Leave your contacts — we will reach out within the day to confirm.',
        'preorder' => 'Leave your contacts — we will reserve the item and contact you about timing.',
    ],
    'submit' => ['order' => 'Order', 'preorder' => 'Preorder'],
    'thanks' => ['order' => 'Thank you for your order', 'preorder' => 'Thank you for your preorder'],
    'subtotal' => 'Subtotal',
],
```

- [ ] **Step 5: Create `resources/css/site/components/qty-stepper.css`**

```css
.qty-stepper {
  display: inline-flex; align-items: center; gap: 8px;
  margin-top: 10px;
}
.qty-stepper .l {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--ink-mute); margin-right: 8px;
}
.qty-stepper button {
  width: 28px; height: 28px; border: 1px solid var(--line);
  background: var(--card); cursor: pointer; font-size: 16px;
}
.qty-stepper .v {
  min-width: 24px; text-align: center; font-family: var(--font-serif); font-size: 18px;
}
```

- [ ] **Step 6: Create `resources/css/site/components/order-form.css`**

```css
.order-form {
  margin-top: 64px; padding-block: 56px;
  border-top: 1px solid var(--line-soft);
  display: grid; gap: 48px;
  grid-template-columns: minmax(0, 0.8fr) minmax(0, 1.2fr);
}
@media (max-width: 880px) { .order-form { grid-template-columns: 1fr; gap: 32px; } }

.order-form .intro .eyebrow {
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.24em;
  text-transform: uppercase; color: var(--accent);
}
.order-form .intro h2 {
  margin-top: 12px; font-family: var(--font-serif); font-weight: 300;
  font-size: 28px;
}
.order-form .intro p { margin-top: 12px; color: var(--ink-soft); }
.order-form .summary {
  margin-top: 24px; padding: 20px; background: var(--bg-2);
  display: grid; grid-template-columns: 80px 1fr; gap: 16px;
}
.order-form .summary .img img { width: 80px; height: 100px; object-fit: cover; }
.order-form .summary .title { font-family: var(--font-serif); font-size: 18px; }
.order-form .summary .meta {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.18em;
  text-transform: uppercase; color: var(--ink-mute); margin-top: 4px;
}
.order-form .summary .price { margin-top: 8px; font-family: var(--font-serif); color: var(--accent); }
.order-form .summary .subtotal {
  margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line-soft);
  display: flex; justify-content: space-between; align-items: baseline;
}
.order-form .summary .subtotal .l {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.2em;
  text-transform: uppercase; color: var(--ink-mute);
}
.order-form .summary .subtotal .v { font-family: var(--font-serif); color: var(--accent); font-size: 18px; }

.order-form .fields {
  display: grid; grid-template-columns: 1fr 1fr; gap: 16px;
}
@media (max-width: 540px) { .order-form .fields { grid-template-columns: 1fr; } }
.order-form .field { display: grid; gap: 6px; }
.order-form .field.full { grid-column: 1 / -1; }
.order-form .field span {
  font-family: var(--font-mono); font-size: 10px; letter-spacing: 0.18em;
  text-transform: uppercase; color: var(--ink-mute);
}
.order-form .field input, .order-form .field textarea {
  padding: 10px 12px; border: 1px solid var(--line); background: var(--field-bg);
  font-family: var(--font-sans); font-size: 14px; color: var(--ink);
}
.order-form .field .err { color: #c0392b; font-size: 12px; }
.order-form .actions { grid-column: 1 / -1; display: flex; justify-content: flex-end; margin-top: 8px; }
.order-form .alert { grid-column: 1 / -1; padding: 12px; background: #fdecea; color: #c0392b; }

.order-thanks {
  margin-top: 64px; padding: 48px; text-align: center;
  background: var(--bg-2);
}
.order-thanks .ok {
  width: 48px; height: 48px; margin: 0 auto 18px;
  display: flex; align-items: center; justify-content: center;
  border-radius: 50%; background: var(--accent); color: var(--accent-ink);
  font-size: 24px;
}
.order-thanks h3 { font-family: var(--font-serif); font-weight: 300; }
.order-thanks .number {
  margin-top: 14px; font-family: var(--font-mono); font-size: 13px;
  letter-spacing: 0.2em; color: var(--ink-mute);
}
```

- [ ] **Step 7: Wire CSS imports**

Add to `resources/css/site/index.css`:
```css
@import './components/qty-stepper.css';
@import './components/order-form.css';
```

- [ ] **Step 8: Mount the form on product page**

In `resources/views/products/show.blade.php`, before the closing `</div>` of `.container`, add:
```blade
<section id="order">
    <livewire:order-form :subject="$product"/>
</section>
```

- [ ] **Step 9: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest|OrderFormTest'
```
Expected: PASS.

- [ ] **Step 10: Commit**

```bash
git add resources/views/forms/order.blade.php \
        resources/css/site/components/order-form.css \
        resources/css/site/components/qty-stepper.css \
        resources/css/site/index.css \
        resources/views/products/show.blade.php \
        lang/uk/forms.php lang/en/forms.php \
        tests/Feature/Public/ProductShowTest.php \
        tests/Feature/Forms/OrderFormTest.php
git commit -m "forms/order: full design redesign + qty stepper + preorder thank-you"
```

---

## Task 17: `<x-site.product-slider>` + `product-slider.css` + related products query

**Files:**
- Create: `resources/views/components/site/product-slider.blade.php`
- Create: `resources/css/site/components/product-slider.css`
- Modify: `resources/css/site/index.css`
- Modify: `resources/views/products/show.blade.php`
- Modify: `lang/uk/catalogue.php`, `lang/en/catalogue.php`
- Test: extend `tests/Feature/Public/ProductShowTest.php`

- [ ] **Step 1: Write failing tests**

Append to `tests/Feature/Public/ProductShowTest.php`:
```php
it('shows up to 6 related products from same series', function () {
    $main = publishedProductInSeries('luxury', ['slug' => 'lux-main']);
    for ($i = 1; $i <= 8; $i++) {
        publishedProductInSeries('luxury', ['slug' => "lux-related-{$i}"]);
    }

    $r = $this->get(route('products.show', $main->slug));
    $r->assertSee(__('catalogue.public.product.related.title'));
    $r->assertSee('lux-related-1');
});

it('fills with cross-series related when same-series count under 4', function () {
    $main = publishedProductInSeries('luxury', ['slug' => 'lux-main']);
    publishedProductInSeries('luxury', ['slug' => 'lux-only-buddy']);
    for ($i = 1; $i <= 5; $i++) {
        publishedProductInSeries('onyx', ['slug' => "onyx-fill-{$i}"]);
    }

    $r = $this->get(route('products.show', $main->slug));
    $r->assertSee('lux-only-buddy');
    $r->assertSee('onyx-fill-1');
});
```

- [ ] **Step 2: Run, verify fail**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: FAIL.

- [ ] **Step 3: Create slider component**

Create `resources/views/components/site/product-slider.blade.php`:
```blade
@props(['products'])

@if($products->isNotEmpty())
<section class="product-slider">
    <div class="container">
        <div class="head">
            <div class="eyebrow">{{ __('catalogue.public.product.related.eyebrow') }}</div>
            <h2>{{ __('catalogue.public.product.related.title') }}</h2>
            <a href="{{ route('products.index') }}" class="lnk">{{ __('catalogue.public.product.related.all_label') }} →</a>
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

- [ ] **Step 4: Add translation keys**

In `lang/uk/catalogue.php` under `public.product` add:
```php
'related' => [
    'eyebrow' => 'Інше з нашого дому',
    'title' => 'Інші композиції',
    'all_label' => 'Усі парфуми',
],
```

Mirror in `lang/en/catalogue.php`:
```php
'related' => [
    'eyebrow' => 'More from our house',
    'title' => 'Other compositions',
    'all_label' => 'All perfumes',
],
```

- [ ] **Step 5: Create `resources/css/site/components/product-slider.css`**

```css
.product-slider {
  margin-top: 80px; padding-block: 72px;
  background: var(--bg-2);
  border-top: 1px solid var(--line-soft);
}
.product-slider .head {
  display: grid; grid-template-columns: 1fr auto; align-items: end; margin-bottom: 32px;
}
.product-slider .eyebrow {
  grid-column: 1 / -1;
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.24em;
  text-transform: uppercase; color: var(--accent); margin-bottom: 12px;
}
.product-slider h2 {
  font-family: var(--font-serif); font-weight: 300; font-size: 32px; margin: 0;
}
.product-slider .lnk {
  font-family: var(--font-mono); font-size: 11px; letter-spacing: 0.24em;
  text-transform: uppercase; color: var(--accent); justify-self: end;
}
.product-slider .track {
  display: grid; grid-auto-flow: column; grid-auto-columns: minmax(220px, 280px);
  gap: 24px; overflow-x: auto; scroll-snap-type: x mandatory;
  padding-bottom: 12px;
}
.product-slider .track > * { scroll-snap-align: start; }
```

- [ ] **Step 6: Wire CSS + add component to show.blade.php**

Add to `resources/css/site/index.css`:
```css
@import './components/product-slider.css';
```

In `resources/views/products/show.blade.php`, after the closing `</div>` of `.container`, add (still inside `.product-page`):
```blade
<x-site.product-slider :products="$related"/>
```

- [ ] **Step 7: Run tests, verify pass**

```bash
php artisan test --filter='ProductShowTest'
```
Expected: PASS.

- [ ] **Step 8: Commit**

```bash
git add resources/views/components/site/product-slider.blade.php \
        resources/css/site/components/product-slider.css resources/css/site/index.css \
        resources/views/products/show.blade.php \
        lang/uk/catalogue.php lang/en/catalogue.php \
        tests/Feature/Public/ProductShowTest.php
git commit -m "product-page: related products slider (scroll-snap)"
```

---

## Task 18: Final assembly — full test run + Pint + manual QA notes

**Files:**
- None new. Verification only.

- [ ] **Step 1: Run full Pest suite**

```bash
php artisan test
```
Expected: all green. Investigate any regression.

- [ ] **Step 2: Format with Pint**

```bash
./vendor/bin/pint
```
Expected: clean formatting; commit any changes.

- [ ] **Step 3: Build frontend assets**

```bash
npm run build
```
Expected: no errors. If a CSS file referenced in `index.css` doesn't exist, build will fail — fix imports.

- [ ] **Step 4: Manual QA checklist**

Run `composer dev` (parallel serve + queue + pail + vite) and walk through:

1. `/uk/products/luxury-1` — cream theme, full layout (gallery, info, pyramid, character, order-form, related slider).
2. `/uk/products/onyx-1` — onyx theme, hero changes color; header, footer, announcement use dark palette.
3. `/en/products/luxury-1` — price in EUR, English labels, same theme.
4. Language switch UA↔EN preserves slug + theme.
5. Click on thumbnail — active swaps; click main image — lightbox opens; `Esc` / `←` / `→` / click-outside work; counter updates.
6. In-stock product CTA = «Замовити»; submit form with valid data → thank-you with `LV-{padded-id}`; admin email subject contains «замовлення».
7. In Filament `/admin`, mark `luxury-2` as `in_stock=false`; reload product page — CTA becomes «Передзамовити», form thank-you and email subject use preorder strings; form-submissions table shows `PRE-ORDER` badge.
8. Visual sweep: header/footer/announcement colors do not have hardcoded values bypassing CSS variables. If found, patch the offending stylesheet.

- [ ] **Step 5: Commit any Pint formatting changes**

```bash
git status
# If files were reformatted:
git add -A && git commit -m "style: Pint formatting"
```

- [ ] **Step 6: Final summary commit (optional)**

If everything is clean and no extra commits needed, this task is just verification — no commit.

---

## Self-review checklist (done before handoff)

- All 18 tasks have exact file paths, complete code blocks, and explicit pass criteria.
- TDD discipline: every code-introducing task starts with a failing test.
- No `TBD` / `TODO` / `implement later` strings.
- Translation keys used in tests (`__('catalogue.public.product.*')`, `__('forms.order.*')`) are added in the same task as the code that reads them.
- Theme switching is data-driven (`series.theme_class`), no `match(slug)` in any task.
- Preorder snapshot is captured via `FormType::metadata()` hook — consistent between OrderFormType, mailables, view, Filament badge.
- Lightbox trigger attribute (`data-lightbox-trigger`) is added to the gallery markup AND read by the JS module — wired both sides.
- `NoteLevel::Heart` used in pyramid component (not `::Mid`, which doesn't exist).
- `sillage_words` keys are `1..5` and read by `$score` directly (no off-by-one).
- Body class change in `layouts/site.blade.php` is an explicit step.
- Spec coverage: every section of `2026-05-24-product-page-design.md` maps to at least one task — §1 theme → Tasks 1-3, §2 fields → Tasks 1, 5, §3 order schema → Tasks 7, 16, §4 preorder → Tasks 6, 8, 9, 13, 16, §5 page/components → Tasks 10-15, 17, §6 CSS/JS → woven through, §7 translations → woven through, Verification → Task 18.
