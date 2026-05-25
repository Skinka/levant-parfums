# Philosophy Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a standalone Philosophy page (`/uk/filosofiia`, `/en/philosophy`) backed by the existing `Page` + JSON-blocks CMS, introducing one new block type (`about_hero`) and reusing `text` (manifesto) and `brand_story` (three points). Header and footer gain a Philosophy nav entry.

**Architecture:** A single seeded `Page` row, template `landing`, with three blocks composed in `PageSeeder`. The new `about_hero` block type has its own Filament admin form, Blade partial, and CSS component. Navigation links resolve via `route('page.show', ['slug' => …])` against a slug map in `config/content.php`. No new routes — the existing `/{slug}` catch-all handles it.

**Tech Stack:** Laravel 13, Filament 5, Spatie Translatable, Tailwind CSS v4, Pest 4.

---

## File Structure

**Files created:**

- `app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php` — Filament Builder block schema for the new block type.
- `resources/views/pages/blocks/about_hero.blade.php` — Blade partial rendered by the landing template.
- `resources/css/site/components/about-hero.css` — CSS for `.about-hero` and `.about-stats`.
- `tests/Feature/Content/PhilosophyPageTest.php` — Pest feature tests.

**Files modified:**

- `app/Enums/BlockType.php` — add `AboutHero` case.
- `app/Filament/Resources/Pages/Schemas/PageForm.php` — register `AboutHeroBlock` in the Builder.
- `config/content.php` — add `philosophy_slug` map.
- `database/seeders/Content/PageSeeder.php` — seed the Philosophy `Page`.
- `lang/uk/content.php`, `lang/en/content.php` — labels for the new block (`blocks.about_hero.*`, `blocks.fields.stats`).
- `lang/uk/site.php`, `lang/en/site.php` — `nav.philosophy` string.
- `resources/css/site/index.css` — import `components/about-hero.css`.
- `resources/views/components/site/header.blade.php` — Philosophy nav entry.
- `resources/views/components/site/footer.blade.php` — Philosophy footer link.

---

## Task 1: Add the `AboutHero` BlockType enum case + Filament lang labels

**Files:**
- Modify: `app/Enums/BlockType.php`
- Modify: `lang/uk/content.php`
- Modify: `lang/en/content.php`

- [ ] **Step 1: Add the enum case**

Edit `app/Enums/BlockType.php`. After the existing `case Hero = 'hero';` line, insert:

```php
case AboutHero = 'about_hero';
```

The final enum block (excluding methods) should read:

```php
enum BlockType: string
{
    case Hero = 'hero';
    case AboutHero = 'about_hero';
    case Text = 'text';
    case Products = 'products';
    case BrandStory = 'brand_story';
    case SeriesDuo = 'series_duo';
    case Pillars = 'pillars';
    case Testimonials = 'testimonials';
    case Articles = 'articles';
    // ...
}
```

The existing `label()` method already resolves `trans("content.blocks.{$this->value}.label")`, so it will pick up the new translation key automatically (added in step 2).

- [ ] **Step 2: Add Ukrainian translations**

Edit `lang/uk/content.php`. Inside the `'blocks' => [ ... ]` array, after the `'hero' => ['label' => 'Hero-блок'],` entry, insert:

```php
        'about_hero' => [
            'label' => 'Шапка «Про дім»',
            'add_stat' => 'Додати статистику',
        ],
```

Inside the same file's `'blocks' => ['fields' => [ ... ]]` array (after the `'meta_label' => 'Підпис',` line), add a new key:

```php
            'stats' => 'Статистика',
```

- [ ] **Step 3: Add English translations**

Edit `lang/en/content.php`. After the `'hero' => ['label' => 'Hero block'],` entry, insert:

```php
        'about_hero' => ['label' => 'About-hero', 'add_stat' => 'Add stat'],
```

Inside the same file's `'fields' => [ ... ]` array, add:

```php
            'stats' => 'Stats',
```

- [ ] **Step 4: Verify nothing is broken**

Run: `php artisan config:clear && php artisan view:clear`
Then: `composer test -- --filter=PageRoutingTest`
Expected: PASS (the existing tests should keep working; we have only added enum cases and lang keys, nothing references them yet).

- [ ] **Step 5: Commit**

```bash
git add app/Enums/BlockType.php lang/uk/content.php lang/en/content.php
git commit -m "feat(content): introduce about_hero BlockType case and labels"
```

---

## Task 2: Filament `AboutHeroBlock` schema + register in PageForm

**Files:**
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php`
- Modify: `app/Filament/Resources/Pages/Schemas/PageForm.php`

- [ ] **Step 1: Create the block schema class**

Create `app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php` with the following contents:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class AboutHeroBlock
{
    public static function make(): Block
    {
        return Block::make('about_hero')
            ->label(trans('content.blocks.about_hero.label'))
            ->icon('heroicon-o-identification')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('lead', component: Textarea::class),
                TranslatableTabs::make('body', component: Textarea::class),

                FileUpload::make('image_path')
                    ->label(trans('content.blocks.fields.image_path'))
                    ->disk('public')
                    ->directory('pages/blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(4096),

                Repeater::make('stats')
                    ->label(trans('content.blocks.fields.stats'))
                    ->schema([
                        TextInput::make('num')
                            ->label(trans('content.blocks.fields.meta_num'))
                            ->required()
                            ->maxLength(8),
                        TranslatableTabs::make('meta_label', required: true),
                    ])
                    ->minItems(0)
                    ->maxItems(4)
                    ->defaultItems(4)
                    ->addActionLabel(trans('content.blocks.about_hero.add_stat'))
                    ->reorderable(),
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

Pattern is copied from `HeroBlock.php` — same `commonFields()`, `TranslatableTabs` for translatable inputs, same `FileUpload` config, a `Repeater` for the stats grid (reusing the `meta_num` / `meta_label` lang keys since they are semantically identical to hero's three-metric repeater).

- [ ] **Step 2: Register the block in PageForm**

Edit `app/Filament/Resources/Pages/Schemas/PageForm.php`. In the `use` block at the top, add:

```php
use App\Filament\Resources\Pages\Schemas\Blocks\AboutHeroBlock;
```

Within the `Builder::make('blocks')->blocks([...])` call (around line 87), insert `AboutHeroBlock::make(),` between `HeroBlock::make(),` and `TextBlock::make(),`. The relevant slice should read:

```php
                ->blocks([
                    HeroBlock::make(),
                    AboutHeroBlock::make(),
                    TextBlock::make(),
                    ProductsBlock::make(),
                    BrandStoryBlock::make(),
                    SeriesDuoBlock::make(),
                    PillarsBlock::make(),
                    TestimonialsBlock::make(),
                    ArticlesBlock::make(),
                ])
```

- [ ] **Step 3: Verify admin form loads**

Run: `composer test -- --filter=PageBuilderTest`
Expected: PASS. Tests that exercise the Filament Builder must not break — the new block is additive.

- [ ] **Step 4: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php app/Filament/Resources/Pages/Schemas/PageForm.php
git commit -m "feat(filament): about_hero block schema and Builder registration"
```

---

## Task 3: Blade partial for `about_hero` + CSS component

**Files:**
- Create: `resources/views/pages/blocks/about_hero.blade.php`
- Create: `resources/css/site/components/about-hero.css`
- Modify: `resources/css/site/index.css`

- [ ] **Step 1: Write the Blade partial**

Create `resources/views/pages/blocks/about_hero.blade.php`:

```blade
@php
    use Illuminate\Support\Facades\Storage;

    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };
    $eyebrow = $t('eyebrow');
    $title = $t('title');
    $lead = $t('lead');
    $body = $t('body');
    $stats = $data['stats'] ?? [];
    $imageUrl = ! empty($data['image_path']) ? Storage::disk('public')->url($data['image_path']) : null;
    $paragraphs = collect(preg_split("/\r?\n\r?\n/", trim((string) $body)))->filter()->values();
@endphp

<section class="about-hero reveal" @if(! empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <x-site.breadcrumbs :items="[
            ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
            ['label' => $page->title],
        ]"/>

        <div class="grid">
            <div class="copy">
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h1>{{ $title }}</h1>@endif
                @if($lead)<p class="lead">{{ $lead }}</p>@endif
                @foreach($paragraphs as $p)
                    <p class="body">{!! nl2br(e($p)) !!}</p>
                @endforeach
            </div>
            <div class="img">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $title }}"
                         width="800" height="800"
                         loading="eager" fetchpriority="high">
                @endif
            </div>
        </div>

        @if(! empty($stats))
            <div class="about-stats">
                @foreach($stats as $stat)
                    @php
                        $statLabel = ($stat['meta_label'][$locale] ?? null) ?: ($stat['meta_label']['uk'] ?? '');
                    @endphp
                    <div class="stat">
                        <div class="num">{{ $stat['num'] ?? '' }}</div>
                        <div class="lbl">{{ $statLabel }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
```

Note: breadcrumbs live inside the block so they appear only when the block is visible and disappear cleanly with the rest of the hero. `loading="eager"` and `fetchpriority="high"` on the image follow the LCP optimization pattern from commit `ff2c7e7`.

- [ ] **Step 2: Write the CSS component**

Create `resources/css/site/components/about-hero.css`:

```css
/* About-hero — editorial intro with crumbs, italic h1, lead, body, square image, and a 4-cell stats row. */

.about-hero { padding: 80px 0 100px; }
.about-hero .crumbs { padding: 0 0 32px; }
.about-hero .grid {
  display: grid; grid-template-columns: 1.1fr 1fr; gap: 80px;
  align-items: end;
}
.about-hero h1 {
  font-style: italic;
  margin-top: 18px;
}
.about-hero .lead { margin-top: 28px; }
.about-hero .body {
  margin-top: 24px;
  color: var(--ink-soft);
  max-width: 52ch;
  line-height: 1.7;
}
.about-hero .img { aspect-ratio: 1; overflow: hidden; }
.about-hero .img img {
  width: 100%; height: 100%; object-fit: cover;
  transition: transform 1.4s var(--ease-out);
}
.about-hero .img:hover img { transform: scale(1.04); }

.about-stats {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 0;
  border-top: 1px solid var(--line-soft);
  border-bottom: 1px solid var(--line-soft);
  margin-top: 60px;
}
.about-stats .stat {
  padding: 40px 32px;
  border-right: 1px solid var(--line-soft);
}
.about-stats .stat:last-child { border-right: none; }
.about-stats .stat .num {
  font-family: var(--font-serif);
  font-size: 56px;
  color: var(--accent);
  line-height: 1;
}
.about-stats .stat .lbl {
  font-size: 11px;
  letter-spacing: 0.18em;
  text-transform: uppercase;
  color: var(--ink-mute);
  margin-top: 12px;
}

@media (max-width: 900px) {
  .about-stats { grid-template-columns: 1fr 1fr; }
  .about-stats .stat:nth-child(2) { border-right: none; }
  .about-stats .stat:nth-child(-n+2) { border-bottom: 1px solid var(--line-soft); }
}

@media (max-width: 800px) {
  .about-hero .grid { grid-template-columns: 1fr; gap: 32px; align-items: start; }
}
```

The two media queries reproduce the source design's stats stacking and add an explicit `.about-hero .grid` stacking rule (the source omitted it — see the spec's "Mobile breakpoints" subsection).

- [ ] **Step 3: Import the CSS**

Edit `resources/css/site/index.css`. Find the section that imports component CSS files (each line is `@import "./components/<name>.css";`). Add a line in alphabetical order:

```css
@import "./components/about-hero.css";
```

Place it before `@import "./components/announcement.css";`.

- [ ] **Step 4: Verify the build still compiles**

Run: `npm run build`
Expected: build completes without CSS errors. (If `npm run build` is unavailable in your environment, skip and rely on the rendering check in Task 6.)

- [ ] **Step 5: Commit**

```bash
git add resources/views/pages/blocks/about_hero.blade.php resources/css/site/components/about-hero.css resources/css/site/index.css
git commit -m "feat(views): about_hero block partial and CSS"
```

---

## Task 4: Seed the Philosophy page

**Files:**
- Modify: `config/content.php`
- Modify: `database/seeders/Content/PageSeeder.php`

- [ ] **Step 1: Add the slug map to config**

Edit `config/content.php`. After the `'help_pages' => [ ... ],` array, add:

```php
    'philosophy_slug' => [
        'uk' => 'filosofiia',
        'en' => 'philosophy',
    ],
```

Do **not** add the slugs to `reserved_slugs` — `Page::booted()` would then refuse to save the Philosophy page itself. Uniqueness is already enforced by the functional JSON indexes on `slug->uk` and `slug->en` from migration `2026_05_23_055707_create_pages_table.php`.

- [ ] **Step 2: Add the seeder logic**

Edit `database/seeders/Content/PageSeeder.php`. After the foreach-loop that seeds simple help pages (around the closing of the homepage section), append a new block that seeds the philosophy page. Place the new code after the `foreach ($this->simplePages() as $page) { ... }` block in `run()`, so it runs after help pages:

```php
        $this->seedPhilosophyPage();
```

Then add the new method to the class (alongside `simplePages()` and `buildBlocks()`):

```php
    private function seedPhilosophyPage(): void
    {
        $slug = config('content.philosophy_slug');

        $blocks = $this->buildPhilosophyBlocks();

        $existing = Page::query()->whereJsonContains('slug->uk', $slug['uk'])->first();

        $data = [
            'slug' => $slug,
            'title' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
            'intro' => ['uk' => '', 'en' => ''],
            'content' => null,
            'seo_title' => [
                'uk' => 'Філософія · Levant Parfums',
                'en' => 'Philosophy · Levant Parfums',
            ],
            'seo_description' => [
                'uk' => 'Парфумерний дім на перетині трьох світів: розроблено в Іспанії, розлито в Туреччині, серце ринку — в Україні.',
                'en' => 'A perfume house at the crossing of three worlds: composed in Spain, bottled in Turkey, with its market and soul in Ukraine.',
            ],
            'is_published' => true,
            'is_homepage' => false,
            'template' => PageTemplate::Landing,
            'blocks' => $blocks,
        ];

        if ($existing) {
            $existing->fill($data)->save();
        } else {
            Page::query()->create($data);
        }
    }

    private function buildPhilosophyBlocks(): array
    {
        return [
            [
                'type' => 'about_hero',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => 'Про дім', 'en' => 'About the house'],
                    'title' => [
                        'uk' => 'Парфумерний дім на перетині трьох світів',
                        'en' => 'A perfume house at the crossing of three worlds',
                    ],
                    'lead' => [
                        'uk' => 'Levant Parfums — це 22 композиції, 20 років досвіду парфумерної школи та три країни в одному підписі. Без переплати за логотип.',
                        'en' => 'Levant Parfums — 22 compositions, twenty years of perfumery school, three countries in one signature. No premium for a logo.',
                    ],
                    'body' => [
                        'uk' => 'Levant — давня назва регіону, де зустрічаються Схід і Захід, де торгівля, культура та аромати знаходили одне одного тисячоліттями. Наш дім — це продовження цієї історії: ідея народжується в Іспанії, флакон збирається у Туреччині, серце ринку — в Україні. Три точки. Один підпис.',
                        'en' => 'Levant is the ancient name of a region where East and West meet, where trade, culture and scent have found each other for millennia. Our house is the continuation of that story: the idea is born in Spain, the bottle is assembled in Turkey, the market and the soul are in Ukraine. Three points. One signature.',
                    ],
                    'image_path' => 'pages/blocks/levant-luxury-bottle.jpg',
                    'stats' => [
                        ['num' => '22', 'meta_label' => ['uk' => 'композиції',  'en' => 'compositions']],
                        ['num' => '2',  'meta_label' => ['uk' => 'колекції',    'en' => 'collections']],
                        ['num' => '3',  'meta_label' => ['uk' => 'країни',      'en' => 'countries']],
                        ['num' => '20', 'meta_label' => ['uk' => 'років школи', 'en' => 'years of school']],
                    ],
                ],
            ],

            [
                'type' => 'text',
                'data' => [
                    'is_visible' => true,
                    'anchor' => 'manifesto',
                    'eyebrow' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                    'title' => [
                        'uk' => "Якщо вам потрібне ім'я і красива коробка — вам у дьюті-фрі.",
                        'en' => 'If you need a name and a beautiful box — go to duty-free.',
                    ],
                    'body' => [
                        'uk' => "Ми ж — для тих, хто хоче аромат, а не бирку. 20 років у парфумерії дають нам знати, де купувати найкращі інгредієнти, — і ми це робимо.\n\nРозроблено в Іспанії. Розлито в Туреччині. Зібрано тут — в Україні. Без переплати за логотип, без подвоєної ціни за порожній флакон.\n\nДорога коробка не робить аромат кращим. Ми вкладаємо у склянку те, у що інші вкладають у логотип. Кінцевий результат — нішевий характер за чесну ціну.",
                        'en' => "We are for those who want the scent, not the tag. Twenty years in perfumery taught us where to source the best ingredients — and we do.\n\nComposed in Spain. Bottled in Turkey. Assembled here, in Ukraine. No premium for a logo, no doubled price for an empty bottle.\n\nAn expensive box does not make a better scent. We invest in the bottle what others invest in the logo. The result — niche character at an honest price.",
                    ],
                    'signature' => ['uk' => '— Команда Levant', 'en' => '— The Levant team'],
                ],
            ],

            [
                'type' => 'brand_story',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => 'Три точки. Один путь.', 'en' => 'Three points. One signature.'],
                    'title' => [
                        'uk' => 'Levant — перетин трьох світів',
                        'en' => 'Levant — a crossing of three worlds',
                    ],
                    'body' => [
                        'uk' => 'Levant — давня назва регіону, де зустрічаються Схід і Захід, де торгівля, культура та аромати знаходили одне одного тисячоліттями.',
                        'en' => 'Levant is the ancient name of a region where East and West meet, where trade, culture and scent have found each other for millennia.',
                    ],
                    'pillars' => [
                        [
                            'pillar_label' => ['uk' => 'Іспанія', 'en' => 'Spain'],
                            'pillar_caption' => ['uk' => 'Народження ідеї', 'en' => 'Where the idea is born'],
                        ],
                        [
                            'pillar_label' => ['uk' => 'Туреччина', 'en' => 'Turkey'],
                            'pillar_caption' => ['uk' => 'Розливається тут', 'en' => 'Where it is bottled'],
                        ],
                        [
                            'pillar_label' => ['uk' => 'Україна', 'en' => 'Ukraine'],
                            'pillar_caption' => ['uk' => 'Ринок і душа', 'en' => 'The market and the soul'],
                        ],
                    ],
                ],
            ],
        ];
    }
```

The image `levant-luxury-bottle.jpg` is already copied to `storage/app/public/pages/blocks/` by the existing `copyAsset('levant-luxury-bottle.jpg')` call at the top of `run()` — no asset work needed.

- [ ] **Step 3: Run the seeder and verify the page exists**

Run: `php artisan migrate:fresh --seed`
Expected: completes without errors.

Then: `php artisan tinker --execute="echo App\\Models\\Content\\Page::query()->whereJsonContains('slug->uk', 'filosofiia')->value('id');"`
Expected: prints a numeric ID (the seeded page).

- [ ] **Step 4: Commit**

```bash
git add config/content.php database/seeders/Content/PageSeeder.php
git commit -m "feat(content): seed Philosophy page with about_hero, text, brand_story"
```

---

## Task 5: Add Philosophy to header + footer nav

**Files:**
- Modify: `lang/uk/site.php`
- Modify: `lang/en/site.php`
- Modify: `resources/views/components/site/header.blade.php`
- Modify: `resources/views/components/site/footer.blade.php`

- [ ] **Step 1: Add Ukrainian nav string**

Edit `lang/uk/site.php`. Inside the `'nav' => [ ... ]` array, add a `philosophy` key between `'catalog'` and `'articles'` so the visual order matches the design:

```php
    'nav' => [
        'aria' => 'Головне меню',
        'home' => 'Головна',
        'catalog' => 'Каталог',
        'philosophy' => 'Філософія',
        'articles' => 'Статті',
    ],
```

- [ ] **Step 2: Add English nav string**

Edit `lang/en/site.php`. Inside `'nav' => [ ... ]`, add the same key. The final block should read:

```php
    'nav' => [
        'aria' => 'Main menu',
        'home' => 'Home',
        'catalog' => 'Catalogue',
        'philosophy' => 'Philosophy',
        'articles' => 'Articles',
    ],
```

(If any `nav` key currently has different text, keep that text — only add the `'philosophy'` line.)

- [ ] **Step 3: Add Philosophy to the header nav array**

Edit `resources/views/components/site/header.blade.php`. Replace the `@php ... @endphp` block at the top (lines 3–17 currently) with:

```blade
@php
    $philosophySlug = config('content.philosophy_slug')[$locale] ?? config('content.philosophy_slug')['uk'];
    $philosophyUrl = route('page.show', ['slug' => $philosophySlug]);

    $nav = [
        ['key' => 'home',       'url' => LaravelLocalization::localizeURL('/'),         'match' => fn ($r) => $r === '/' || $r === ''],
        ['key' => 'catalog',    'url' => route('products.index'),                       'match' => fn ($r) => str_starts_with($r, '/products')],
        ['key' => 'philosophy', 'url' => $philosophyUrl,                                'match' => fn ($r) => $r === '/' . $philosophySlug],
        ['key' => 'articles',   'url' => route('articles.index', [], false),            'match' => fn ($r) => str_starts_with($r, '/articles')],
    ];
    $path = '/' . trim(request()->path(), '/');
    foreach (config('catalogue.locales', []) as $loc) {
        if (str_starts_with($path, "/$loc/") || $path === "/$loc") {
            $path = '/' . trim(substr($path, strlen("/$loc")), '/');
            break;
        }
    }
@endphp
```

Only the `$nav` array is modified compared to the existing file — the surrounding `$path` derivation is unchanged.

- [ ] **Step 4: Add Philosophy to the footer nav column**

Edit `resources/views/components/site/footer.blade.php`. In the nav column (currently lines 18–25), insert a new `<li>` between Catalog and Articles. The resulting block should read:

```blade
            <div>
                <h4>{{ __('site.footer.columns.nav') }}</h4>
                <ul>
                    <li><a href="{{ LaravelLocalization::localizeURL('/') }}">{{ __('site.nav.home') }}</a></li>
                    <li><a href="{{ route('products.index') }}">{{ __('site.nav.catalog') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => config('content.philosophy_slug')[$locale] ?? 'filosofiia']) }}">{{ __('site.nav.philosophy') }}</a></li>
                    <li><a href="{{ route('articles.index', [], false) }}">{{ __('site.nav.articles') }}</a></li>
                </ul>
            </div>
```

- [ ] **Step 5: Smoke-test the routes by hand**

Run: `php artisan route:clear && php artisan view:clear`
Then start the dev server (`composer dev` if not already up) and visit:
- `http://localhost:8000/uk` — header nav contains "Філософія" linking to `/uk/filosofiia`.
- `http://localhost:8000/en` — header nav contains "Philosophy" linking to `/en/philosophy`.
- Click the Philosophy link in each — the page renders with the three sections.

If the dev server isn't running in this environment, defer the visual check to Task 7.

- [ ] **Step 6: Commit**

```bash
git add lang/uk/site.php lang/en/site.php resources/views/components/site/header.blade.php resources/views/components/site/footer.blade.php
git commit -m "feat(nav): link to the Philosophy page from header and footer"
```

---

## Task 6: Feature tests for the Philosophy page

**Files:**
- Create: `tests/Feature/Content/PhilosophyPageTest.php`

- [ ] **Step 1: Write the failing tests**

Create `tests/Feature/Content/PhilosophyPageTest.php`:

```php
<?php

use App\Enums\PageTemplate;
use App\Models\Content\Page;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

function makePhilosophyPage(array $overrides = []): Page
{
    $blocks = $overrides['blocks'] ?? [
        [
            'type' => 'about_hero',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Про дім', 'en' => 'About the house'],
                'title' => [
                    'uk' => 'Парфумерний дім на перетині трьох світів',
                    'en' => 'A perfume house at the crossing of three worlds',
                ],
                'lead' => ['uk' => 'Коротко про нас.', 'en' => 'About us in short.'],
                'body' => ['uk' => 'Levant — давня назва регіону.', 'en' => 'Levant is the ancient name of a region.'],
                'image_path' => null,
                'stats' => [
                    ['num' => '22', 'meta_label' => ['uk' => 'композиції',  'en' => 'compositions']],
                    ['num' => '2',  'meta_label' => ['uk' => 'колекції',    'en' => 'collections']],
                    ['num' => '3',  'meta_label' => ['uk' => 'країни',      'en' => 'countries']],
                    ['num' => '20', 'meta_label' => ['uk' => 'років школи', 'en' => 'years of school']],
                ],
            ],
        ],
        [
            'type' => 'text',
            'data' => [
                'is_visible' => true,
                'anchor' => 'manifesto',
                'eyebrow' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                'title' => ['uk' => 'Манифест-заголовок', 'en' => 'Manifesto title'],
                'body' => ['uk' => 'Текст манифеста.', 'en' => 'Manifesto body.'],
            ],
        ],
        [
            'type' => 'brand_story',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Три точки', 'en' => 'Three points'],
                'title' => ['uk' => 'Перетин трьох світів', 'en' => 'Crossing of three worlds'],
                'pillars' => [
                    ['pillar_label' => ['uk' => 'Іспанія',   'en' => 'Spain'],   'pillar_caption' => ['uk' => 'Ідея',  'en' => 'Idea']],
                    ['pillar_label' => ['uk' => 'Туреччина', 'en' => 'Turkey'],  'pillar_caption' => ['uk' => 'Розлив', 'en' => 'Bottling']],
                    ['pillar_label' => ['uk' => 'Україна',   'en' => 'Ukraine'], 'pillar_caption' => ['uk' => 'Душа',  'en' => 'Soul']],
                ],
            ],
        ],
    ];

    return Page::factory()->create(array_merge([
        'template' => PageTemplate::Landing,
        'is_homepage' => false,
        'is_published' => true,
        'slug' => ['uk' => 'filosofiia', 'en' => 'philosophy'],
        'title' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
        'content' => null,
        'blocks' => $blocks,
    ], array_diff_key($overrides, ['blocks' => true])));
}

it('renders the Philosophy page at the uk slug', function () {
    makePhilosophyPage();

    $this->get('/filosofiia')
        ->assertOk()
        ->assertSee('Парфумерний дім на перетині трьох світів')
        ->assertSee('Іспанія')
        ->assertSee('Манифест-заголовок');
});

it('renders the Philosophy page at the en slug', function () {
    makePhilosophyPage();

    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/en/philosophy')
        ->assertOk()
        ->assertSee('A perfume house at the crossing of three worlds')
        ->assertSee('Spain')
        ->assertSee('Manifesto title');
});

it('hides the about_hero block when is_visible is false', function () {
    makePhilosophyPage([
        'blocks' => [
            [
                'type' => 'about_hero',
                'data' => [
                    'is_visible' => false,
                    'title' => ['uk' => 'HIDDEN-HERO-TITLE', 'en' => 'HIDDEN-HERO-TITLE'],
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'is_visible' => true,
                    'body' => ['uk' => 'VISIBLE-MANIFESTO', 'en' => 'VISIBLE-MANIFESTO'],
                ],
            ],
        ],
    ]);

    $this->get('/filosofiia')
        ->assertOk()
        ->assertDontSee('HIDDEN-HERO-TITLE')
        ->assertSee('VISIBLE-MANIFESTO');
});

it('renders all four stats from the about_hero block', function () {
    makePhilosophyPage();

    $response = $this->get('/filosofiia');
    $response->assertOk();

    foreach (['22', '2', '3', '20'] as $num) {
        $response->assertSee($num);
    }
    $response->assertSee('композиції');
});

it('exposes a Philosophy link in the header nav', function () {
    makePhilosophyPage();

    $expectedUrl = route('page.show', ['slug' => config('content.philosophy_slug')['uk']]);

    $this->get('/')
        ->assertOk()
        ->assertSee($expectedUrl, escape: false)
        ->assertSee('Філософія');
});
```

- [ ] **Step 2: Run the tests to verify they fail in the expected way**

Run: `composer test -- --filter=PhilosophyPageTest`
Expected: FAIL — the tests should at minimum execute. If `makePhilosophyPage` errors with `class 'App\Models\Content\Page' not found` something is wrong with autoload; otherwise they should fail because the page is created fresh in each test (no leftover seeded data) and rendering may surface formatting differences between block rendering and assertions.

If everything in Tasks 1–5 was completed correctly, these tests should **PASS** on first run — they exercise the same code paths already implemented. If any test fails:
- Re-read the failure message.
- Check that the block-type name in the test data matches `BlockType::AboutHero->value` (`'about_hero'`).
- Check that `Page` factory exists at `Database\Factories\Content\PageFactory` and that the model has `HasFactory` (it does).

- [ ] **Step 3: Run the full test suite**

Run: `composer test`
Expected: green. The new tests pass; no regressions in existing tests.

- [ ] **Step 4: Commit**

```bash
git add tests/Feature/Content/PhilosophyPageTest.php
git commit -m "test(content): feature tests for the Philosophy page"
```

---

## Task 7: Manual UI verification and final polish

**Files:** none expected; one of the artefacts below may surface a small fix.

- [ ] **Step 1: Reseed the database**

Run: `php artisan migrate:fresh --seed`
Expected: no errors.

- [ ] **Step 2: Start the dev server**

Run: `composer dev`
This boots `artisan serve`, the queue, Pail, and Vite in parallel.

- [ ] **Step 3: Visual check — uk locale**

In a browser, visit `http://localhost:8000/uk/filosofiia`.

Expected (each item is a separate visual check):
- Header navigation shows: Головна / Каталог / **Філософія** / Статті.
- Breadcrumbs at the top: "Головна / Філософія".
- Hero: italic h1 "Парфумерний дім на перетині трьох світів"; subtitle paragraph; long body paragraph; square image on the right.
- 4-cell stats row directly below the hero, gold numbers (22, 2, 3, 20) in the serif font, uppercase tracked labels.
- Manifesto section on tinted background with italic open-quote and signature "— Команда Levant".
- Three-points section: gold diamond markers, "Іспанія" / "Туреччина" / "Україна" connected by gold lines with arrowheads.
- Footer nav column includes "Філософія".

- [ ] **Step 4: Visual check — en locale**

Visit `http://localhost:8000/en/philosophy`. Same checks with English content (Home / Catalogue / Philosophy / Articles, etc.).

- [ ] **Step 5: Mobile breakpoint check**

Open DevTools → toggle device emulation. Resize the viewport to 600px width.

Expected:
- About-hero grid stacks to a single column with 32px gap (copy on top, image below).
- About-stats grid collapses to 2x2 (the `:nth-child(2)` border-right disappears; first row has a bottom border).
- Manifesto and three-points sections stack per their existing breakpoints.

- [ ] **Step 6: LCP sanity check (optional but recommended)**

DevTools → Performance tab → record a fresh load of `/uk/filosofiia`. The largest contentful paint should be the hero image, and it should have `fetchpriority="high"` in the request waterfall. If the LCP fires on a later element, double-check the `loading="eager" fetchpriority="high"` attributes in the Blade partial.

- [ ] **Step 7: Lint and tests**

Run: `./vendor/bin/pint`
Expected: no issues, or auto-fixed in place.

Run: `composer test`
Expected: green.

- [ ] **Step 8: Final commit (if any small fixes were made)**

If the visual checks turned up any tweaks (e.g., spacing, missing attribute), make them and commit with a focused message. If everything is clean, skip this step.

---

## Verification checklist (against the spec)

- [x] **Page seeded** with `template=landing`, `slug={uk:'filosofiia', en:'philosophy'}`, `is_published=true`, three blocks. (Task 4)
- [x] **No new route**; resolved via existing `/{slug}` catch-all. (no code touched in `routes/web.php`)
- [x] **`reserved_slugs` untouched**, per the receiving-code-review fix. (Task 4 Step 1)
- [x] **`about_hero` block** has Filament admin form, Blade partial, CSS, and renders eyebrow + title + lead + body + image + stats. (Tasks 2, 3)
- [x] **`text` and `brand_story` blocks** reused with content matching the spec. (Task 4 Step 2)
- [x] **Header + footer nav** include Philosophy. (Task 5)
- [x] **Mobile breakpoints** explicit for `.about-hero .grid` and `.about-stats`. (Task 3 Step 2)
- [x] **Five feature tests** covering uk slug, en slug, hidden block, stats, and nav link via `route()`. (Task 6)
- [x] **LCP-friendly hero image** with explicit dimensions + `loading="eager"` + `fetchpriority="high"`. (Task 3 Step 1)
- [x] **Team section** intentionally not built. (out of scope per spec)

End of plan.
