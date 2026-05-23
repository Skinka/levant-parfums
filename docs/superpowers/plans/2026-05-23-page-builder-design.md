# Page Builder для главной (и будущих лендингов) — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Расширить модель `Page` полями `template`/`blocks`/`is_homepage`, добавить Filament `Builder` с 4 типами блоков (hero/products/text/articles), завести роутинг главной и страниц через `PageController`, и набор placeholder-Blade-партиалов для рендера. Финальная вёрстка вне scope.

**Architecture:** Одна сущность `Page` обслуживает и статические страницы (`template=simple` → markdown `content`), и лендинги (`template=landing` → JSON `blocks`). Translatable стратегия гибридная: колонка `blocks` НЕ translatable, а текстовые поля внутри блока хранятся как `{uk, en}` через явные мини-табы (хелпер `TranslatableTabs`). Главная находится по флагу `is_homepage` (один partial/functional unique индекс гарантирует одну запись), slug-роут отдаёт остальные страницы. Каждому `template` соответствует Blade-файл `pages.templates.{template}`, каждому типу блока — `pages.blocks.{type}`. Медиа в блоках — обычный `FileUpload` с диском `public` (не Spatie MediaLibrary — см. спецификацию, p. 149).

**Tech Stack:** Laravel 13, PHP 8.3, MySQL 8.0+ (prod) / SQLite (тесты), Filament 5 (`Builder` field), Spatie Translatable 6, mcamara/laravel-localization, Pest 4 + Livewire тесты.

**Spec:** `docs/superpowers/specs/2026-05-23-page-builder-design.md`

**Conventions used throughout:**
- All Bash commands assume `cwd = /Users/romanroman/Projects/LevantParfums`.
- Tests run on SQLite `:memory:` (`phpunit.xml`); admin/manual checks run on MySQL via Sail.
- Migration timestamps in this plan are placeholders (`2026_05_23_HHMMSS_*`); use `php artisan make:migration` to get the actual timestamp.
- Commits use the same prefix style as recent history (`content: …`).
- "Supported locales" config — используем существующий `config('catalogue.locales', ['uk', 'en'])`, не вводим новый ключ.
- После каждого "Run tests" шаг "Commit" предполагает чистый рабочий каталог (только файлы из текущей задачи). Не накапливать коммиты между задачами.

---

## File Structure

Files this plan will create or modify, grouped by task:

```
app/Enums/PageTemplate.php                                              [Task 1, create]
app/Enums/BlockType.php                                                 [Task 1, create]
config/content.php                                                      [Task 1, modify]

database/migrations/YYYY_MM_DD_HHMMSS_add_template_blocks_to_pages_table.php  [Task 2, create]
app/Models/Content/Page.php                                             [Task 2, modify]
database/factories/Content/PageFactory.php                              [Task 2, modify]
tests/Feature/Content/PageBuilderTest.php                               [Task 2, create]

lang/uk/content.php                                                     [Task 3, modify]
lang/en/content.php                                                     [Task 3, modify]

app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php  [Task 4, create]
app/Filament/Resources/Pages/Schemas/Blocks/HeroBlock.php               [Task 5, create]
app/Filament/Resources/Pages/Schemas/Blocks/TextBlock.php               [Task 5, create]
app/Filament/Resources/Pages/Schemas/Blocks/ProductsBlock.php           [Task 6, create]
app/Filament/Resources/Pages/Schemas/Blocks/ArticlesBlock.php           [Task 6, create]

app/Filament/Resources/Pages/Schemas/PageForm.php                       [Task 7, modify]
tests/Feature/Content/Filament/PageBuilderResourceTest.php              [Task 7, create]

app/Http/Controllers/PageController.php                                 [Task 8, create]
routes/web.php                                                          [Task 8, modify]

resources/views/pages/layouts/base.blade.php                            [Task 9, create]
resources/views/pages/templates/simple.blade.php                        [Task 9, create]
resources/views/pages/templates/landing.blade.php                       [Task 9, create]
resources/views/pages/blocks/hero.blade.php                             [Task 9, create]
resources/views/pages/blocks/products.blade.php                         [Task 9, create]
resources/views/pages/blocks/text.blade.php                             [Task 9, create]
resources/views/pages/blocks/articles.blade.php                         [Task 9, create]
tests/Feature/Content/PageRoutingTest.php                               [Task 9, create]

database/seeders/Content/PageSeeder.php                                 [Task 10, modify]
```

Each file has one clear responsibility. The four Block classes are split into two tasks because `ProductsBlock`/`ArticlesBlock` use a different pattern (Repeater) than `HeroBlock`/`TextBlock` (translatable text fields). Splitting prevents cross-contamination of failures.

---

## Task 1: Enums + reserved slug

**Files:**
- Create: `app/Enums/PageTemplate.php`
- Create: `app/Enums/BlockType.php`
- Modify: `config/content.php`

- [ ] **Step 1: Create `PageTemplate` enum**

Write `app/Enums/PageTemplate.php`:

```php
<?php

namespace App\Enums;

enum PageTemplate: string
{
    case Simple = 'simple';
    case Landing = 'landing';

    public function label(): string
    {
        return trans("content.template.{$this->value}");
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $t) => [$t->value => $t->label()])
            ->all();
    }
}
```

- [ ] **Step 2: Create `BlockType` enum**

Write `app/Enums/BlockType.php`:

```php
<?php

namespace App\Enums;

enum BlockType: string
{
    case Hero = 'hero';
    case Products = 'products';
    case Text = 'text';
    case Articles = 'articles';

    public function label(): string
    {
        return trans("content.blocks.{$this->value}.label");
    }
}
```

- [ ] **Step 3: Add `home` to reserved slugs**

Edit `config/content.php`. Replace its contents with:

```php
<?php

return [
    'reserved_slugs' => [
        'admin', 'api', 'assets', 'storage', 'login', 'register', 'logout',
        'blog', 'articles', 'pages', 'sitemap', 'feed',
        'uk', 'en',
        'home',
    ],
];
```

- [ ] **Step 4: Run lints to verify syntax**

Run: `./vendor/bin/pint --test app/Enums/PageTemplate.php app/Enums/BlockType.php config/content.php`
Expected: PASS (no formatting violations). If it fails on style, run without `--test` and re-run with `--test`.

- [ ] **Step 5: Commit**

```bash
git add app/Enums/PageTemplate.php app/Enums/BlockType.php config/content.php
git commit -m "content: add PageTemplate/BlockType enums and reserve 'home' slug"
```

---

## Task 2: Migration + Page model updates + factory + model tests

**Files:**
- Create: `database/migrations/YYYY_MM_DD_HHMMSS_add_template_blocks_to_pages_table.php`
- Modify: `app/Models/Content/Page.php`
- Modify: `database/factories/Content/PageFactory.php`
- Create: `tests/Feature/Content/PageBuilderTest.php`

- [ ] **Step 1: Write the failing model test**

Create `tests/Feature/Content/PageBuilderTest.php`:

```php
<?php

use App\Enums\PageTemplate;
use App\Models\Content\Page;
use Illuminate\Database\QueryException;

it('casts template to PageTemplate enum', function () {
    $page = Page::factory()->create(['template' => 'landing']);

    expect($page->refresh()->template)->toBe(PageTemplate::Landing);
});

it('casts blocks to array', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => 'A', 'en' => 'A']]],
        ],
    ]);

    expect($page->refresh()->blocks)->toBeArray()->toHaveCount(1)
        ->and($page->blocks[0]['type'])->toBe('hero');
});

it('visibleBlocks filters is_visible=false and preserves order', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => '1', 'en' => '1']]],
            ['type' => 'text', 'data' => ['is_visible' => false, 'body' => ['uk' => '2', 'en' => '2']]],
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => '3', 'en' => '3']]],
        ],
    ]);

    $visible = $page->visibleBlocks();

    expect($visible)->toHaveCount(2)
        ->and($visible[0]['data']['title']['uk'])->toBe('1')
        ->and($visible[1]['data']['body']['uk'])->toBe('3');
});

it('visibleBlocks returns empty array when blocks is null', function () {
    $page = Page::factory()->create(['template' => 'simple', 'blocks' => null]);

    expect($page->visibleBlocks())->toBe([]);
});

it('homepage scope returns only is_homepage=true', function () {
    Page::factory()->create(['is_homepage' => false]);
    Page::factory()->homepage()->create();

    expect(Page::query()->homepage()->count())->toBe(1);
});

it('DB rejects a second is_homepage=true page', function () {
    Page::factory()->homepage()->create();

    expect(fn () => Page::factory()->homepage()->create())
        ->toThrow(QueryException::class);
});

it('allows landing page with null content', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'content' => null,
        'blocks' => [],
    ]);

    expect($page->refresh()->content)->toBeNull()
        ->and($page->template)->toBe(PageTemplate::Landing);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=PageBuilderTest`
Expected: FAIL. Errors will mention unknown column `template`/`blocks`/`is_homepage`, unknown method `homepage()`/`visibleBlocks()`, factory state `homepage` not defined.

- [ ] **Step 3: Create the migration**

Run: `php artisan make:migration add_template_blocks_to_pages_table --table=pages`

Then overwrite the generated file with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('template', 32)->default('simple')->after('content')->index();
            $table->json('blocks')->nullable()->after('template');
            $table->boolean('is_homepage')->default(false)->after('is_published');
        });

        // content is no longer required (landing pages do not use it).
        Schema::table('pages', function (Blueprint $table) {
            $table->json('content')->nullable()->change();
        });

        // Exactly one homepage. MySQL has no partial-index syntax → use CASE
        // expression (NULLs are not considered duplicates by UNIQUE).
        // SQLite supports partial unique indexes natively via WHERE.
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                'CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages '
                .'((CASE WHEN is_homepage = 1 THEN 1 ELSE NULL END))'
            );
        } elseif (DB::getDriverName() === 'sqlite') {
            DB::statement(
                'CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages (is_homepage) '
                .'WHERE is_homepage = 1'
            );
        }
    }

    public function down(): void
    {
        // Drop the unique index BEFORE removing the column it references.
        if (in_array(DB::getDriverName(), ['mysql', 'sqlite'], true)) {
            DB::statement('DROP INDEX pages_is_homepage_uniq ON pages')
                // ↑ MySQL syntax. SQLite needs plain `DROP INDEX <name>`.
                ;
        }

        // The above ON-pages syntax is MySQL-only. For SQLite, restate:
        if (DB::getDriverName() === 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS pages_is_homepage_uniq');
        }

        Schema::table('pages', function (Blueprint $table) {
            $table->dropIndex(['template']);
            $table->dropColumn(['template', 'blocks', 'is_homepage']);
        });

        // Restore content as NOT NULL (best-effort; will fail if any row has NULL).
        Schema::table('pages', function (Blueprint $table) {
            $table->json('content')->nullable(false)->change();
        });
    }
};
```

> Note on `down()`: the two `DROP INDEX` styles above are awkward because MySQL requires `DROP INDEX <name> ON <table>` and SQLite uses `DROP INDEX <name>`. Simpler version that works on both drivers, replace the index-drop block with:

```php
$driver = DB::getDriverName();
if ($driver === 'mysql') {
    DB::statement('DROP INDEX pages_is_homepage_uniq ON pages');
} elseif ($driver === 'sqlite') {
    DB::statement('DROP INDEX IF EXISTS pages_is_homepage_uniq');
}
```

Use that simpler form — it's the canonical version.

- [ ] **Step 4: Update the `Page` model**

Edit `app/Models/Content/Page.php`. Replace the file contents with:

```php
<?php

namespace App\Models\Content;

use App\Enums\PageTemplate;
use Database\Factories\Content\PageFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Translatable\HasTranslations;

class Page extends Model implements HasMedia
{
    /** @use HasFactory<PageFactory> */
    use HasFactory;
    use HasTranslations;
    use InteractsWithMedia;

    protected $fillable = [
        'slug', 'title', 'intro', 'content',
        'seo_title', 'seo_description', 'is_published',
        'template', 'blocks', 'is_homepage',
    ];

    public array $translatable = [
        'slug', 'title', 'intro', 'content', 'seo_title', 'seo_description',
    ];

    protected function casts(): array
    {
        return [
            'is_published' => 'boolean',
            'is_homepage' => 'boolean',
            'template' => PageTemplate::class,
            'blocks' => 'array',
        ];
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('is_published', true);
    }

    public function scopeHomepage(Builder $q): Builder
    {
        return $q->where('is_homepage', true);
    }

    public function visibleBlocks(): array
    {
        return array_values(array_filter(
            $this->blocks ?? [],
            fn (array $block) => ($block['data']['is_visible'] ?? true) !== false,
        ));
    }

    protected static function booted(): void
    {
        static::saving(function (Page $page) {
            $reserved = config('content.reserved_slugs', []);
            $slugs = $page->getTranslations('slug');
            foreach ($slugs as $locale => $slug) {
                if (in_array($slug, $reserved, true)) {
                    throw new \DomainException("Slug '{$slug}' is reserved (locale: {$locale}).");
                }
            }
        });
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('primary')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')->fit(Fit::Crop, 400, 400)->format('webp')->nonQueued();
        $this->addMediaConversion('card')->fit(Fit::Crop, 1200, 630)->format('webp')->nonQueued();
        $this->addMediaConversion('detail')->fit(Fit::Crop, 1920, 1080)->format('webp')->nonQueued();
    }
}
```

- [ ] **Step 5: Extend the `PageFactory` with new fields and a `homepage()` state**

Edit `database/factories/Content/PageFactory.php`. Replace its contents with:

```php
<?php

namespace Database\Factories\Content;

use App\Enums\PageTemplate;
use App\Models\Content\Page;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Page>
 */
class PageFactory extends Factory
{
    protected $model = Page::class;

    public function definition(): array
    {
        $titleUk = 'Сторінка '.fake()->unique()->numberBetween(1, 99999);
        $titleEn = 'Page '.fake()->unique()->numberBetween(1, 99999);

        return [
            'slug' => [
                'uk' => Str::slug($titleUk).'-'.Str::random(4),
                'en' => Str::slug($titleEn).'-'.Str::random(4),
            ],
            'title' => ['uk' => $titleUk, 'en' => $titleEn],
            'intro' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'content' => ['uk' => fake('uk_UA')->paragraphs(2, true), 'en' => fake()->paragraphs(2, true)],
            'seo_title' => ['uk' => $titleUk, 'en' => $titleEn],
            'seo_description' => ['uk' => fake('uk_UA')->sentence(), 'en' => fake()->sentence()],
            'is_published' => true,
            'template' => PageTemplate::Simple,
            'blocks' => null,
            'is_homepage' => false,
        ];
    }

    public function draft(): static
    {
        return $this->state(['is_published' => false]);
    }

    public function homepage(): static
    {
        return $this->state([
            'is_homepage' => true,
            'template' => PageTemplate::Landing,
            'content' => null,
            'blocks' => [],
        ]);
    }
}
```

- [ ] **Step 6: Run the new test to verify it passes**

Run: `php artisan test --filter=PageBuilderTest`
Expected: PASS — all 7 tests green.

- [ ] **Step 7: Run the existing PageTest to ensure no regressions**

Run: `php artisan test --filter=PageTest`
Expected: PASS — all existing tests still green.

- [ ] **Step 8: Run lint**

Run: `./vendor/bin/pint --test app/Models/Content/Page.php database/factories/Content/PageFactory.php database/migrations tests/Feature/Content/PageBuilderTest.php`
Expected: PASS. If formatting fails, drop `--test`, re-run, then re-test.

- [ ] **Step 9: Commit**

```bash
git add database/migrations app/Models/Content/Page.php database/factories/Content/PageFactory.php tests/Feature/Content/PageBuilderTest.php
git commit -m "content: add template/blocks/is_homepage to pages with single-homepage constraint"
```

---

## Task 3: Translations (uk + en)

**Files:**
- Modify: `lang/uk/content.php`
- Modify: `lang/en/content.php`

These keys back labels used in PageForm, Builder blocks, and the `PageTemplate::label()` accessor. They must exist BEFORE we wire the form (Task 7), otherwise Filament will render literal translation keys.

- [ ] **Step 1: Extend `lang/uk/content.php`**

Edit `lang/uk/content.php`. Replace its contents with:

```php
<?php

return [
    'navigation' => [
        'group' => 'Контент',
        'pages' => 'Сторінки',
        'articles' => 'Статті',
    ],
    'page' => [
        'singular' => 'Сторінка',
        'plural' => 'Сторінки',
    ],
    'article' => [
        'singular' => 'Стаття',
        'plural' => 'Статті',
    ],
    'tabs' => [
        'main' => 'Основне',
        'seo' => 'SEO',
        'images' => 'Зображення',
    ],
    'fields' => [
        'title' => 'Заголовок',
        'slug' => 'URL',
        'intro' => 'Короткий вступ',
        'content' => 'Контент',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Опубліковано',
        'published_at' => 'Дата публікації',
        'primary' => 'Основне зображення',
        'products' => 'Прив’язані товари',
        'add_product' => 'Додати товар',
        'product_id' => 'Товар',
        'template' => 'Шаблон',
        'blocks' => 'Блоки сторінки',
        'add_block' => 'Додати блок',
        'is_homepage' => 'Головна сторінка',
    ],
    'hints' => [
        'published_at' => "Стаття з'явиться на сайті в цей час. Лишіть порожнім — публікація одразу.",
    ],
    'actions' => [
        'publish' => 'Опублікувати',
        'unpublish' => 'Зняти з публікації',
    ],
    'filters' => [
        'scheduled' => 'Заплановані',
    ],
    'template' => [
        'simple' => 'Звичайна сторінка',
        'landing' => 'Лендинг (блоки)',
    ],
    'blocks' => [
        'hero' => [
            'label' => 'Hero-блок',
        ],
        'products' => [
            'label' => 'Список товарів',
            'add_item' => 'Додати товар',
        ],
        'text' => [
            'label' => 'Текстовий блок',
        ],
        'articles' => [
            'label' => 'Список статей',
            'add_item' => 'Додати статтю',
        ],
        'fields' => [
            'is_visible' => 'Показувати блок',
            'anchor' => 'Якір (id у URL)',
            'title' => 'Заголовок',
            'subtitle' => 'Підзаголовок',
            'body' => 'Текст',
            'cta_label' => 'Текст кнопки',
            'cta_url' => 'Посилання кнопки',
            'image_path' => 'Зображення',
            'product_id' => 'Товар',
            'article_id' => 'Стаття',
            'cta_url_helper' => 'Можна вписати внутрішнє "/products" або зовнішнє "https://...".',
        ],
    ],
];
```

- [ ] **Step 2: Extend `lang/en/content.php`**

Edit `lang/en/content.php`. Replace its contents with:

```php
<?php

return [
    'navigation' => [
        'group' => 'Content',
        'pages' => 'Pages',
        'articles' => 'Articles',
    ],
    'page' => [
        'singular' => 'Page',
        'plural' => 'Pages',
    ],
    'article' => [
        'singular' => 'Article',
        'plural' => 'Articles',
    ],
    'tabs' => [
        'main' => 'Main',
        'seo' => 'SEO',
        'images' => 'Images',
    ],
    'fields' => [
        'title' => 'Title',
        'slug' => 'URL',
        'intro' => 'Intro',
        'content' => 'Content',
        'seo_title' => 'SEO title',
        'seo_description' => 'SEO description',
        'is_published' => 'Published',
        'published_at' => 'Publish at',
        'primary' => 'Primary image',
        'products' => 'Related products',
        'add_product' => 'Add product',
        'product_id' => 'Product',
        'template' => 'Template',
        'blocks' => 'Page blocks',
        'add_block' => 'Add block',
        'is_homepage' => 'Homepage',
    ],
    'hints' => [
        'published_at' => 'The article will appear on the site at this time. Leave empty to publish immediately.',
    ],
    'actions' => [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
    ],
    'filters' => [
        'scheduled' => 'Scheduled',
    ],
    'template' => [
        'simple' => 'Simple page',
        'landing' => 'Landing (blocks)',
    ],
    'blocks' => [
        'hero' => [
            'label' => 'Hero block',
        ],
        'products' => [
            'label' => 'Product list',
            'add_item' => 'Add product',
        ],
        'text' => [
            'label' => 'Text block',
        ],
        'articles' => [
            'label' => 'Article list',
            'add_item' => 'Add article',
        ],
        'fields' => [
            'is_visible' => 'Show block',
            'anchor' => 'Anchor (URL id)',
            'title' => 'Title',
            'subtitle' => 'Subtitle',
            'body' => 'Body',
            'cta_label' => 'CTA label',
            'cta_url' => 'CTA URL',
            'image_path' => 'Image',
            'product_id' => 'Product',
            'article_id' => 'Article',
            'cta_url_helper' => 'Internal "/products" or absolute "https://..." both work.',
        ],
    ],
];
```

- [ ] **Step 3: Sanity-check by loading both files**

Run:

```bash
php -r "var_export(array_keys(require 'lang/uk/content.php')); echo PHP_EOL; var_export(array_keys(require 'lang/en/content.php'));"
```

Expected: both arrays print the same top-level keys. If a parse error appears, fix syntax and re-run.

- [ ] **Step 4: Commit**

```bash
git add lang/uk/content.php lang/en/content.php
git commit -m "content: translations for page builder labels and blocks"
```

---

## Task 4: `TranslatableTabs` helper

**Files:**
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php`

This is a pure factory for Filament `Tabs` — no DB, no behaviour to test in isolation. It will be exercised through the resource test in Task 7.

- [ ] **Step 1: Create the helper**

First, create the directory:

```bash
mkdir -p app/Filament/Resources/Pages/Schemas/Blocks/Concerns
```

Then write `app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks\Concerns;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;

class TranslatableTabs
{
    /**
     * Build a tabbed text field that writes to {field}.{locale} keys inside
     * the block's `data` payload. Use TextInput by default; pass Textarea
     * (or MarkdownEditor) via $component for longer fields.
     *
     * @param  class-string  $component  Form component class with a static ::make($name) factory.
     */
    public static function make(string $field, bool $required = false, string $component = TextInput::class): Tabs
    {
        $locales = config('catalogue.locales', ['uk', 'en']);

        return Tabs::make($field)
            ->label(trans("content.blocks.fields.{$field}"))
            ->tabs(collect($locales)
                ->map(fn (string $locale) => Tab::make(strtoupper($locale))
                    ->schema([
                        $component::make("{$field}.{$locale}")
                            ->label(false)
                            ->required($required && $locale === 'uk'),
                    ]))
                ->all());
    }
}
```

- [ ] **Step 2: Verify class loads**

Run:

```bash
php -r "require 'vendor/autoload.php'; \$app = require 'bootstrap/app.php'; \$app->boot(); var_dump(class_exists(App\\Filament\\Resources\\Pages\\Schemas\\Blocks\\Concerns\\TranslatableTabs::class));"
```

Expected: `bool(true)`. If `bool(false)`, autoload missed it — run `composer dump-autoload` and retry.

- [ ] **Step 3: Lint**

Run: `./vendor/bin/pint --test app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php`
Expected: PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php
git commit -m "content: TranslatableTabs helper for per-locale block fields"
```

---

## Task 5: `HeroBlock` + `TextBlock` (translatable text + optional image)

**Files:**
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/HeroBlock.php`
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/TextBlock.php`

These two share `commonFields()`. Rather than introduce a separate base class now (YAGNI — only used in 4 classes, all in this directory), duplicate the two-line method. If a 5th block needs it, refactor then.

- [ ] **Step 1: Create `HeroBlock`**

Write `app/Filament/Resources/Pages/Schemas/Blocks/HeroBlock.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class HeroBlock
{
    public static function make(): Block
    {
        return Block::make('hero')
            ->label(trans('content.blocks.hero.label'))
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('subtitle', component: Textarea::class),
                TranslatableTabs::make('cta_label'),
                TextInput::make('cta_url')
                    ->label(trans('content.blocks.fields.cta_url'))
                    ->maxLength(2048)
                    ->helperText(trans('content.blocks.fields.cta_url_helper')),
                FileUpload::make('image_path')
                    ->label(trans('content.blocks.fields.image_path'))
                    ->disk('public')
                    ->directory('pages/blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(4096),
            ]);
    }

    protected static function commonFields(): array
    {
        return [
            Toggle::make('is_visible')
                ->label(trans('content.blocks.fields.is_visible'))
                ->default(true),
            TextInput::make('anchor')
                ->label(trans('content.blocks.fields.anchor'))
                ->prefix('#')
                ->alphaDash(),
        ];
    }
}
```

- [ ] **Step 2: Create `TextBlock`**

Write `app/Filament/Resources/Pages/Schemas/Blocks/TextBlock.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class TextBlock
{
    public static function make(): Block
    {
        return Block::make('text')
            ->label(trans('content.blocks.text.label'))
            ->icon('heroicon-o-document-text')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('title'),
                TranslatableTabs::make('body', required: true, component: MarkdownEditor::class),
            ]);
    }

    protected static function commonFields(): array
    {
        return [
            Toggle::make('is_visible')
                ->label(trans('content.blocks.fields.is_visible'))
                ->default(true),
            TextInput::make('anchor')
                ->label(trans('content.blocks.fields.anchor'))
                ->prefix('#')
                ->alphaDash(),
        ];
    }
}
```

- [ ] **Step 3: Verify both classes load**

Run:

```bash
php -r "require 'vendor/autoload.php'; var_dump(class_exists(App\\Filament\\Resources\\Pages\\Schemas\\Blocks\\HeroBlock::class), class_exists(App\\Filament\\Resources\\Pages\\Schemas\\Blocks\\TextBlock::class));"
```

Expected: `bool(true) bool(true)`.

- [ ] **Step 4: Lint**

Run: `./vendor/bin/pint --test app/Filament/Resources/Pages/Schemas/Blocks`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/Blocks/HeroBlock.php app/Filament/Resources/Pages/Schemas/Blocks/TextBlock.php
git commit -m "content: HeroBlock and TextBlock factories for page builder"
```

---

## Task 6: `ProductsBlock` + `ArticlesBlock` (Repeater for manual selection)

**Files:**
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/ProductsBlock.php`
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/ArticlesBlock.php`

Both use a Repeater inside the block (pattern from `ProductForm::notesRepeater` and `ArticleForm`'s products repeater). The Repeater stores items as `data.items[]`, where each item is `{product_id: int}` or `{article_id: int}`. Array index = display order.

- [ ] **Step 1: Create `ProductsBlock`**

Write `app/Filament/Resources/Pages/Schemas/Blocks/ProductsBlock.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Models\Catalogue\Product;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ProductsBlock
{
    public static function make(): Block
    {
        return Block::make('products')
            ->label(trans('content.blocks.products.label'))
            ->icon('heroicon-o-shopping-bag')
            ->schema([
                ...self::commonFields(),
                Repeater::make('items')
                    ->schema([
                        Select::make('product_id')
                            ->label(trans('content.blocks.fields.product_id'))
                            ->options(fn () => Product::query()
                                ->orderBy('slug')
                                ->get()
                                ->mapWithKeys(fn (Product $p) => [$p->id => $p->name])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel(trans('content.blocks.products.add_item')),
            ]);
    }

    protected static function commonFields(): array
    {
        return [
            Toggle::make('is_visible')
                ->label(trans('content.blocks.fields.is_visible'))
                ->default(true),
            TextInput::make('anchor')
                ->label(trans('content.blocks.fields.anchor'))
                ->prefix('#')
                ->alphaDash(),
        ];
    }
}
```

- [ ] **Step 2: Create `ArticlesBlock`**

Write `app/Filament/Resources/Pages/Schemas/Blocks/ArticlesBlock.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Models\Content\Article;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ArticlesBlock
{
    public static function make(): Block
    {
        return Block::make('articles')
            ->label(trans('content.blocks.articles.label'))
            ->icon('heroicon-o-newspaper')
            ->schema([
                ...self::commonFields(),
                Repeater::make('items')
                    ->schema([
                        Select::make('article_id')
                            ->label(trans('content.blocks.fields.article_id'))
                            ->options(fn () => Article::query()
                                ->orderBy('id')
                                ->get()
                                ->mapWithKeys(fn (Article $a) => [$a->id => $a->title])
                                ->all())
                            ->searchable()
                            ->required(),
                    ])
                    ->reorderable()
                    ->defaultItems(0)
                    ->addActionLabel(trans('content.blocks.articles.add_item')),
            ]);
    }

    protected static function commonFields(): array
    {
        return [
            Toggle::make('is_visible')
                ->label(trans('content.blocks.fields.is_visible'))
                ->default(true),
            TextInput::make('anchor')
                ->label(trans('content.blocks.fields.anchor'))
                ->prefix('#')
                ->alphaDash(),
        ];
    }
}
```

- [ ] **Step 3: Verify both classes load**

Run:

```bash
php -r "require 'vendor/autoload.php'; var_dump(class_exists(App\\Filament\\Resources\\Pages\\Schemas\\Blocks\\ProductsBlock::class), class_exists(App\\Filament\\Resources\\Pages\\Schemas\\Blocks\\ArticlesBlock::class));"
```

Expected: `bool(true) bool(true)`.

- [ ] **Step 4: Lint**

Run: `./vendor/bin/pint --test app/Filament/Resources/Pages/Schemas/Blocks`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/Blocks/ProductsBlock.php app/Filament/Resources/Pages/Schemas/Blocks/ArticlesBlock.php
git commit -m "content: ProductsBlock and ArticlesBlock factories with item repeater"
```

---

## Task 7: Wire `PageForm` (template select + Builder + is_homepage) + Filament tests

**Files:**
- Modify: `app/Filament/Resources/Pages/Schemas/PageForm.php`
- Create: `tests/Feature/Content/Filament/PageBuilderResourceTest.php`

- [ ] **Step 1: Write the failing Filament test**

Create `tests/Feature/Content/Filament/PageBuilderResourceTest.php`:

```php
<?php

use App\Enums\PageTemplate;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Content\Page;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('creates a simple page (content required, blocks ignored)', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка',
            'slug' => 'dostavka',
            'content' => 'Текст доставки.',
            'is_published' => true,
            'template' => PageTemplate::Simple->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::firstWhere('id', 1);
    expect($page->template)->toBe(PageTemplate::Simple)
        ->and($page->getTranslation('content', 'uk'))->toBe('Текст доставки.')
        ->and($page->blocks)->toBeNull();
});

it('creates a landing page with two blocks preserving order', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Головна',
            'slug' => 'main',
            'is_published' => true,
            'template' => PageTemplate::Landing->value,
            'blocks' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'is_visible' => true,
                        'title' => ['uk' => 'Привіт', 'en' => 'Hi'],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'is_visible' => true,
                        'body' => ['uk' => 'Текст', 'en' => 'Body'],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::firstWhere('id', 1);
    expect($page->blocks)->toHaveCount(2)
        ->and($page->blocks[0]['type'])->toBe('hero')
        ->and($page->blocks[1]['type'])->toBe('text')
        ->and($page->blocks[0]['data']['title']['uk'])->toBe('Привіт')
        ->and($page->blocks[1]['data']['body']['en'])->toBe('Body')
        ->and($page->content)->toBeNull();
});

it('rejects a second is_homepage page at DB level', function () {
    Page::factory()->homepage()->create();
    $other = Page::factory()->create();

    expect(fn () => Livewire::test(EditPage::class, ['record' => $other->getRouteKey()])
        ->fillForm(['is_homepage' => true])
        ->call('save'))
        ->toThrow(QueryException::class);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=PageBuilderResourceTest`
Expected: FAIL. Errors will mention unknown form fields `template`/`blocks`/`is_homepage` or required-field validation on `content` when template is landing.

- [ ] **Step 3: Update `PageForm`**

Edit `app/Filament/Resources/Pages/Schemas/PageForm.php`. Replace its contents with:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas;

use App\Enums\PageTemplate;
use App\Filament\Resources\Pages\Schemas\Blocks\ArticlesBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\HeroBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\ProductsBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\TextBlock;
use Filament\Forms\Components\Builder;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\SpatieMediaLibraryFileUpload;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PageForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('page')
                    ->tabs([
                        Tab::make(trans('content.tabs.main'))
                            ->schema(self::mainTab()),
                        Tab::make(trans('content.tabs.seo'))
                            ->schema(self::seoTab()),
                        Tab::make(trans('content.tabs.images'))
                            ->schema(self::imagesTab()),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected static function mainTab(): array
    {
        $reserved = config('content.reserved_slugs', []);

        return [
            TextInput::make('title')
                ->label(fn () => trans('content.fields.title'))
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, callable $get, callable $set) {
                    if (! $get('slug')) {
                        $set('slug', Str::slug(is_array($state) ? ($state['uk'] ?? '') : $state));
                    }
                }),
            TextInput::make('slug')
                ->label(fn () => trans('content.fields.slug'))
                ->required()
                ->rule('not_in:'.implode(',', $reserved))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->uk')->ignore($record?->id))
                ->rule(fn ($record) => Rule::unique('pages', 'slug->en')->ignore($record?->id)),
            Textarea::make('intro')
                ->label(fn () => trans('content.fields.intro'))
                ->rows(3)
                ->maxLength(300),
            Select::make('template')
                ->label(fn () => trans('content.fields.template'))
                ->options(PageTemplate::options())
                ->default(PageTemplate::Simple->value)
                ->required()
                ->live(),
            MarkdownEditor::make('content')
                ->label(fn () => trans('content.fields.content'))
                ->visible(fn (callable $get) => $get('template') === PageTemplate::Simple->value)
                ->required(fn (callable $get) => $get('template') === PageTemplate::Simple->value)
                ->toolbarButtons([
                    'bold', 'italic', 'link', 'heading',
                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                ]),
            Builder::make('blocks')
                ->label(fn () => trans('content.fields.blocks'))
                ->visible(fn (callable $get) => $get('template') === PageTemplate::Landing->value)
                ->blocks([
                    HeroBlock::make(),
                    ProductsBlock::make(),
                    TextBlock::make(),
                    ArticlesBlock::make(),
                ])
                ->collapsible()
                ->collapsed()
                ->blockNumbers(false)
                ->addActionLabel(trans('content.fields.add_block'))
                ->reorderableWithButtons()
                ->cloneable(),
            Toggle::make('is_homepage')
                ->label(fn () => trans('content.fields.is_homepage')),
            Toggle::make('is_published')
                ->label(fn () => trans('content.fields.is_published')),
        ];
    }

    protected static function seoTab(): array
    {
        return [
            TextInput::make('seo_title')
                ->label(fn () => trans('content.fields.seo_title'))
                ->maxLength(70),
            Textarea::make('seo_description')
                ->label(fn () => trans('content.fields.seo_description'))
                ->rows(3)
                ->maxLength(160),
        ];
    }

    protected static function imagesTab(): array
    {
        return [
            SpatieMediaLibraryFileUpload::make('primary')
                ->label(fn () => trans('content.fields.primary'))
                ->collection('primary')
                ->image()
                ->imageEditor()
                ->maxSize(4096),
        ];
    }
}
```

- [ ] **Step 4: Run the new test to verify it passes**

Run: `php artisan test --filter=PageBuilderResourceTest`
Expected: PASS — all 3 tests green.

If `it('rejects a second is_homepage page...')` does not throw `QueryException` (some Filament/Livewire versions wrap the exception), change the assertion to:

```php
expect(fn () => Livewire::test(EditPage::class, ['record' => $other->getRouteKey()])
    ->fillForm(['is_homepage' => true])
    ->call('save'))
    ->toThrow(\Throwable::class);
```

and re-run.

- [ ] **Step 5: Re-run the entire content test suite for regressions**

Run: `php artisan test tests/Feature/Content`
Expected: PASS — everything green (PageTest, PageBuilderTest, PageBuilderResourceTest, ArticleTest, ArticleResourceTest).

- [ ] **Step 6: Lint**

Run: `./vendor/bin/pint --test app/Filament/Resources/Pages/Schemas/PageForm.php tests/Feature/Content/Filament/PageBuilderResourceTest.php`
Expected: PASS.

- [ ] **Step 7: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/PageForm.php tests/Feature/Content/Filament/PageBuilderResourceTest.php
git commit -m "content: PageForm template select + Builder blocks + is_homepage toggle"
```

---

## Task 8: `PageController` + routes

**Files:**
- Create: `app/Http/Controllers/PageController.php`
- Modify: `routes/web.php`

Routing tests go in Task 9 (after views exist) — otherwise `view()` calls 500 on missing templates and we can't tell whether the route or the view is broken.

- [ ] **Step 1: Create the controller**

Write `app/Http/Controllers/PageController.php`:

```php
<?php

namespace App\Http\Controllers;

use App\Models\Content\Page;

class PageController extends Controller
{
    public function home()
    {
        $page = Page::query()->homepage()->published()->firstOrFail();

        return view("pages.templates.{$page->template->value}", ['page' => $page]);
    }

    public function show(string $slug)
    {
        $locale = app()->getLocale();

        $page = Page::query()
            ->whereJsonContains("slug->{$locale}", $slug)
            ->published()
            ->firstOrFail();

        return view("pages.templates.{$page->template->value}", ['page' => $page]);
    }
}
```

> The base `Controller` class is `app/Http/Controllers/Controller.php` — already in this codebase from the Laravel skeleton. No middleware to add: localization is handled at the route-group level.

- [ ] **Step 2: Update routes**

Edit `routes/web.php`. Replace its contents with:

```php
<?php

use App\Http\Controllers\PageController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-_]+')
        ->name('page.show');
});
```

> The `where('slug', '[A-Za-z0-9\-_]+')` constraint prevents `/{slug}` from swallowing locale-prefixed paths or admin URLs.

- [ ] **Step 3: Verify route list compiles**

Run: `php artisan route:list --path=uk --json` (or just `php artisan route:list`)
Expected: output shows `GET uk/` → `PageController@home` and `GET uk/{slug}` → `PageController@show`, plus the equivalent `en` routes. No exception.

- [ ] **Step 4: Lint**

Run: `./vendor/bin/pint --test app/Http/Controllers/PageController.php routes/web.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/PageController.php routes/web.php
git commit -m "content: PageController + localized routes for / and /{slug}"
```

---

## Task 9: Blade templates + routing tests

**Files:**
- Create: `resources/views/pages/layouts/base.blade.php`
- Create: `resources/views/pages/templates/simple.blade.php`
- Create: `resources/views/pages/templates/landing.blade.php`
- Create: `resources/views/pages/blocks/hero.blade.php`
- Create: `resources/views/pages/blocks/products.blade.php`
- Create: `resources/views/pages/blocks/text.blade.php`
- Create: `resources/views/pages/blocks/articles.blade.php`
- Create: `tests/Feature/Content/PageRoutingTest.php`

- [ ] **Step 1: Write the failing routing test**

Create `tests/Feature/Content/PageRoutingTest.php`:

```php
<?php

use App\Enums\PageTemplate;
use App\Models\Catalogue\Product;
use App\Models\Content\Page;

it('GET /uk returns 200 for the published homepage', function () {
    Page::factory()->homepage()->create([
        'title' => ['uk' => 'Головна', 'en' => 'Home'],
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => 'Привіт', 'en' => 'Hi']]],
        ],
    ]);

    $this->get('/uk')
        ->assertOk()
        ->assertSee('Привіт');
});

it('GET /uk returns 404 if no homepage is configured', function () {
    Page::factory()->create(['is_homepage' => false]);

    $this->get('/uk')->assertNotFound();
});

it('GET /uk returns 404 if homepage is unpublished', function () {
    Page::factory()->homepage()->draft()->create();

    $this->get('/uk')->assertNotFound();
});

it('GET /uk/{slug} returns 200 for a published simple page', function () {
    Page::factory()->create([
        'template' => PageTemplate::Simple,
        'slug' => ['uk' => 'pro-nas', 'en' => 'about-us'],
        'title' => ['uk' => 'Про нас', 'en' => 'About us'],
        'content' => ['uk' => 'Опис компанії.', 'en' => 'Company description.'],
        'is_published' => true,
    ]);

    $this->get('/uk/pro-nas')
        ->assertOk()
        ->assertSee('Опис компанії.', escape: false);
});

it('GET /uk/{slug} returns 200 for a published landing page (not homepage)', function () {
    Page::factory()->create([
        'template' => PageTemplate::Landing,
        'is_homepage' => false,
        'content' => null,
        'slug' => ['uk' => 'aktsii', 'en' => 'promo'],
        'blocks' => [
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => 'Акції зараз', 'en' => 'Promo now']]],
        ],
        'is_published' => true,
    ]);

    $this->get('/uk/aktsii')
        ->assertOk()
        ->assertSee('Акції зараз');
});

it('does not render blocks with is_visible=false', function () {
    Page::factory()->homepage()->create([
        'blocks' => [
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => 'VISIBLE-BLOCK', 'en' => 'V']]],
            ['type' => 'text', 'data' => ['is_visible' => false, 'body' => ['uk' => 'HIDDEN-BLOCK', 'en' => 'H']]],
        ],
    ]);

    $response = $this->get('/uk');
    $response->assertOk()
        ->assertSee('VISIBLE-BLOCK')
        ->assertDontSee('HIDDEN-BLOCK');
});

it('renders product list block with selected products', function () {
    $p1 = Product::factory()->create(['slug' => 'aaa-001']);
    $p2 = Product::factory()->create(['slug' => 'bbb-002']);

    Page::factory()->homepage()->create([
        'blocks' => [
            ['type' => 'products', 'data' => ['is_visible' => true, 'items' => [
                ['product_id' => $p2->id],
                ['product_id' => $p1->id],
            ]]],
        ],
    ]);

    $this->get('/uk')
        ->assertOk()
        ->assertSee($p1->name)
        ->assertSee($p2->name);
});
```

- [ ] **Step 2: Run the test to verify it fails**

Run: `php artisan test --filter=PageRoutingTest`
Expected: FAIL. Errors will mention missing view `pages.layouts.base` or `pages.templates.landing`.

- [ ] **Step 3: Create the base layout**

First, create the directories:

```bash
mkdir -p resources/views/pages/layouts resources/views/pages/templates resources/views/pages/blocks
```

Then write `resources/views/pages/layouts/base.blade.php`:

```blade
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->seo_title ?: $page->title }}</title>
    @if($page->seo_description)
        <meta name="description" content="{{ $page->seo_description }}">
    @endif
</head>
<body>
    @yield('content')
</body>
</html>
```

- [ ] **Step 4: Create the `simple` template**

Write `resources/views/pages/templates/simple.blade.php`:

```blade
@extends('pages.layouts.base')

@section('content')
    <article>
        <h1>{{ $page->title }}</h1>
        {!! Str::markdown($page->content ?? '') !!}
    </article>
@endsection
```

- [ ] **Step 5: Create the `landing` template**

Write `resources/views/pages/templates/landing.blade.php`:

```blade
@extends('pages.layouts.base')

@section('content')
    @foreach($page->visibleBlocks() as $block)
        @includeIf("pages.blocks.{$block['type']}", [
            'data' => $block['data'],
            'page' => $page,
        ])
    @endforeach
@endsection
```

- [ ] **Step 6: Create the `hero` block partial**

Write `resources/views/pages/blocks/hero.blade.php`:

```blade
@php($locale = app()->getLocale())
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <h1>{{ $data['title'][$locale] ?? $data['title']['uk'] ?? '' }}</h1>
    @if(! empty($data['subtitle']))
        <p>{{ $data['subtitle'][$locale] ?? $data['subtitle']['uk'] ?? '' }}</p>
    @endif
    @if($path = ($data['image_path'] ?? null))
        <img src="{{ Storage::disk('public')->url($path) }}" alt="">
    @endif
    @if(! empty($data['cta_url']) && ! empty($data['cta_label']))
        <a href="{{ $data['cta_url'] }}">{{ $data['cta_label'][$locale] ?? $data['cta_label']['uk'] }}</a>
    @endif
</section>
```

- [ ] **Step 7: Create the `text` block partial**

Write `resources/views/pages/blocks/text.blade.php`:

```blade
@php($locale = app()->getLocale())
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    @if(! empty($data['title']))
        <h2>{{ $data['title'][$locale] ?? $data['title']['uk'] ?? '' }}</h2>
    @endif
    <div>{!! Str::markdown($data['body'][$locale] ?? $data['body']['uk'] ?? '') !!}</div>
</section>
```

- [ ] **Step 8: Create the `products` block partial**

Write `resources/views/pages/blocks/products.blade.php`:

```blade
@php
    $ids = collect($data['items'] ?? [])->pluck('product_id')->filter()->all();
    $products = $ids
        ? \App\Models\Catalogue\Product::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();
@endphp
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <ul>
        @foreach($ids as $id)
            @if($product = $products[$id] ?? null)
                <li>{{ $product->name }}</li>
            @endif
        @endforeach
    </ul>
</section>
```

> Preserving the editor's order: we iterate `$ids` (the Repeater's array order), not `$products` (a keyed collection — iteration order is by `keyBy`).

- [ ] **Step 9: Create the `articles` block partial**

Write `resources/views/pages/blocks/articles.blade.php`:

```blade
@php
    $ids = collect($data['items'] ?? [])->pluck('article_id')->filter()->all();
    $articles = $ids
        ? \App\Models\Content\Article::query()->whereIn('id', $ids)->get()->keyBy('id')
        : collect();
@endphp
<section @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <ul>
        @foreach($ids as $id)
            @if($article = $articles[$id] ?? null)
                <li>{{ $article->title }}</li>
            @endif
        @endforeach
    </ul>
</section>
```

- [ ] **Step 10: Run the routing test to verify it passes**

Run: `php artisan test --filter=PageRoutingTest`
Expected: PASS — all 7 tests green.

If a test fails with "no homepage found" on `/uk` cases, check that `Page::factory()->homepage()` is leaving `is_published = true` (it should — the `homepage()` state doesn't override `is_published`, and the default in `definition()` is `true`).

- [ ] **Step 11: Run the full test suite**

Run: `composer test`
Expected: PASS — all tests green project-wide.

- [ ] **Step 12: Lint everything new**

Run: `./vendor/bin/pint --test resources/views tests/Feature/Content/PageRoutingTest.php`
Expected: PASS. (Pint will skip Blade files — that's fine.)

- [ ] **Step 13: Commit**

```bash
git add resources/views/pages tests/Feature/Content/PageRoutingTest.php
git commit -m "content: placeholder Blade templates for page builder + routing tests"
```

---

## Task 10: Seeder

**Files:**
- Modify: `database/seeders/Content/PageSeeder.php`

- [ ] **Step 1: Update `PageSeeder` to seed the homepage with all four block types**

Edit `database/seeders/Content/PageSeeder.php`. Replace its contents with:

```php
<?php

namespace Database\Seeders\Content;

use App\Enums\PageTemplate;
use App\Models\Content\Page;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        // Static pages (About, Delivery, Payment, ...) are added manually
        // in the admin UI — no fixtures here.

        // The homepage placeholder: one block of each type so editors see
        // the page builder working on first boot. Slug is required by the
        // NOT NULL JSON column but is unused (routing finds the homepage
        // via is_homepage).
        Page::query()->updateOrCreate(
            ['is_homepage' => true],
            [
                'slug' => ['uk' => 'home-uk', 'en' => 'home-en'],
                'title' => ['uk' => 'Головна', 'en' => 'Home'],
                'intro' => ['uk' => '', 'en' => ''],
                'content' => null,
                'seo_title' => ['uk' => 'Головна', 'en' => 'Home'],
                'seo_description' => ['uk' => '', 'en' => ''],
                'is_published' => true,
                'template' => PageTemplate::Landing,
                'blocks' => [
                    [
                        'type' => 'hero',
                        'data' => [
                            'is_visible' => true,
                            'title' => ['uk' => 'Левант Парфюми', 'en' => 'Levant Parfums'],
                            'subtitle' => ['uk' => 'Аромати, що надихають', 'en' => 'Scents that inspire'],
                            'cta_label' => ['uk' => 'Каталог', 'en' => 'Shop'],
                            'cta_url' => '/products',
                        ],
                    ],
                    [
                        'type' => 'products',
                        'data' => [
                            'is_visible' => true,
                            'items' => [],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'data' => [
                            'is_visible' => true,
                            'body' => [
                                'uk' => 'Текст «про нас» — замініть у адмінці.',
                                'en' => 'About text — replace in the admin.',
                            ],
                        ],
                    ],
                    [
                        'type' => 'articles',
                        'data' => [
                            'is_visible' => true,
                            'items' => [],
                        ],
                    ],
                ],
            ],
        );
    }
}
```

> `updateOrCreate(['is_homepage' => true], ...)` is idempotent against the unique index — re-running `db:seed` won't fail. The `slug` values `home-uk`/`home-en` are *not* in `reserved_slugs` (we reserved bare `home`, not `home-uk`), so the saving hook will let them through.

- [ ] **Step 2: Run the seeder against a fresh DB**

Run: `php artisan migrate:fresh --seed`
Expected: completes without errors. Verify with:

```bash
php artisan tinker --execute="echo App\\Models\\Content\\Page::homepage()->first()->blocks ? 'OK' : 'EMPTY';"
```

Expected: `OK` (or a JSON-encoded blocks array if tinker prints the value).

- [ ] **Step 3: Re-run the full test suite (RefreshDatabase tests reset the DB; sanity check)**

Run: `composer test`
Expected: PASS.

- [ ] **Step 4: Lint**

Run: `./vendor/bin/pint --test database/seeders/Content/PageSeeder.php`
Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/seeders/Content/PageSeeder.php
git commit -m "content: seed homepage with one block of each type"
```

---

## Task 11: Manual verification

No code changes — this is the smoke-test pass against a running app. Run after all earlier tasks are merged.

- [ ] **Step 1: Reset DB and start the dev stack**

Run:

```bash
php artisan migrate:fresh --seed
composer dev
```

`composer dev` starts artisan serve, queue, pail, and vite in parallel. Wait until `Server running on http://127.0.0.1:8000` appears.

- [ ] **Step 2: Verify `/uk` and `/en` render the seeded landing**

In a browser, open `http://127.0.0.1:8000/uk`. Expected: a minimal HTML page with hero text «Левант Парфюми», subtitle «Аромати, що надихають», a CTA button «Каталог», an empty product list, the about-text block, and an empty articles list.

Then open `http://127.0.0.1:8000/en`. Expected: the same layout, English text («Levant Parfums», «Scents that inspire», «Shop», «About text — replace in the admin.»).

- [ ] **Step 3: Verify admin Page form**

Log in to `/admin` as `admin@levantparfums.test` / `password`. Open the seeded homepage in Pages. Expected behaviour:
- Template select shows «Лендинг (блоки)» selected.
- The MarkdownEditor for `content` is hidden.
- The Builder shows 4 blocks (hero, products, text, articles), each collapsible.
- The «Головна сторінка» toggle is on.

Switch the template to «Звичайна сторінка». Expected:
- Builder disappears.
- Markdown content editor appears and becomes required.

Switch back to «Лендинг» before leaving the page (don't save).

- [ ] **Step 4: Edit a hero block, switch UK/EN, save**

Inside the hero block, switch between UK and EN tabs of the «Заголовок» field. Set «UK title test» and «EN title test». Click Save.

Reload `/uk` → expect to see «UK title test». Reload `/en` → expect «EN title test».

- [ ] **Step 5: Toggle `is_visible=false` on the text block**

Open the homepage in admin again, find the text block, set «Показувати блок» off, save. Reload `/uk`. Expected: the about text disappears from the page; everything else still renders.

Turn the toggle back on, save, reload. Expected: text reappears.

- [ ] **Step 6: Try to mark a second page as homepage**

In admin, create or open any non-homepage page. Switch on «Головна сторінка», try to save. Expected: an error is surfaced — either a Filament error toast, or the request fails with a 500 backed by `QueryException: UNIQUE constraint failed: pages_is_homepage_uniq` in `storage/logs/laravel.log`.

If the form silently swallows the exception and shows a generic error, that's acceptable for this iteration — file a follow-up to surface it cleanly (out of scope here).

- [ ] **Step 7: Try to save a page with reserved slug `home`**

In admin, create a new page with slug `home`. Expected: the form's `not_in:` rule fires before we hit the DomainException in the model. Either way, the page is not saved.

- [ ] **Step 8: Stop the dev stack**

Press Ctrl-C in the `composer dev` terminal.

- [ ] **Step 9: Final commit (only if verification surfaced fixes)**

If verification went clean, there is nothing to commit. If you had to tweak anything (Blade typos, missing translation key, etc.), commit those fixes with a `content: …` message.

---

## Self-review notes (kept for the executor)

- **Spec coverage:** Task 1 → enums + reserved slug. Task 2 → migration + model + factory (covers section "Модель данных" incl. partial unique index, both DB drivers, nullable content). Task 3 → translations. Tasks 4–7 → Filament (TranslatableTabs, 4 block factories, PageForm). Task 8 → controller + routes. Task 9 → all Blade files + routing tests (covers "Frontend — роутинг + рендер", incl. is_visible filtering). Task 10 → seeder (covers «альтернатива — засеять 4 блока»). Task 11 → verification checklist matches the spec's «Верификация» section.
- **Not in plan, intentionally:** orphan file cleanup, Spatie MediaLibrary migration for blocks, internal-or-absolute URL validation, translatable slug for homepage — all listed in spec's "Открытые вопросы".
- **Naming consistency:** `visibleBlocks()` (Task 2) is the only accessor name; used in `landing.blade.php` (Task 9). `homepage()` factory state (Task 2) used in Tasks 7, 9, 10. `PageTemplate::Simple`/`PageTemplate::Landing` used in Tasks 2, 7, 9, 10.
- **Bilingual labels match across uk/en:** every key added in Task 3 exists in both files.
