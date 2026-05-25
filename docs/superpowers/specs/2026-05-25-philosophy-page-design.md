# Philosophy page — design spec

**Date:** 2026-05-25
**Status:** draft
**Source:** `docs/superpowers/design-sources/levant-parfums/project/pages-other.jsx` (AboutPage), `data.js` (about_*, manifesto_*, threepoints_*), `styles.css` (`.about-hero`, `.about-stats`, `.manifesto`, `.threepoints`).

## Why

The handed-off design includes a dedicated `AboutPage` (labelled "Філософія" / "Philosophy" in the navigation) that expands on the brand story currently squeezed into two sections on the homepage. We need a standalone page that:

1. Gives the brand narrative its own dignified surface (hero with stats, manifesto, three-point geography).
2. Backs the "Філософія" header link the design implies but the live site doesn't yet have.
3. Reuses the CMS block system instead of introducing a one-off template.

The homepage keeps its existing manifesto and brand-story sections as teasers; the dedicated page is the long-form version.

## Out of scope

- **Team section.** Design carries three placeholder cards (vendor names with stock bottle photos). Not building — wait until real photos and bios are available.
- **Markdown content.** Page is composed of typed blocks, not a free-form markdown body. The `simple` template is not used.
- **Theme variants.** Page uses the layout default (`theme-cream`); `theme_class` not introduced for `Page`.
- **Filament page-builder UX redesign.** New block fits the existing Builder; no admin chrome changes.

## Architecture

### Page record

A single `Page` row, created/updated idempotently in `database/seeders/Content/PageSeeder.php`:

```
template       = PageTemplate::Landing
is_homepage    = false
is_published   = true
slug           = { uk: 'filosofiia', en: 'philosophy' }
title          = { uk: 'Філософія', en: 'Philosophy' }
intro          = { uk: '', en: '' }
content        = null
seo_title      = { uk: 'Філософія · Levant Parfums',
                   en: 'Philosophy · Levant Parfums' }
seo_description = { uk: 'Парфумерний дім на перетині трьох світів: розроблено в Іспанії, розлито в Туреччині, серце ринку — в Україні.',
                    en: 'A perfume house at the crossing of three worlds: composed in Spain, bottled in Turkey, with its market and soul in Ukraine.' }
blocks         = [about_hero, text, brand_story]   // see Block composition
```

The page is resolved by the existing catch-all route `/{slug}` → `PageController@show`, which does `whereJsonContains("slug->{$locale}", $slug)`. No new route.

### Slug uniqueness — leave reserved_slugs alone

The Philosophy page slugs are **not** added to `config('content.reserved_slugs')`. `Page::booted()` throws `DomainException` for any saved page whose translated slug appears in that list, so adding them would block `PageSeeder` from creating the page itself (the same way it blocks the help pages from being seeded — those slugs are also intentionally absent from the list).

Uniqueness is enforced by the **functional unique JSON indexes** added in migration `2026_05_23_055707_create_pages_table.php` (separate MySQL and SQLite branches on `JSON_EXTRACT(slug, '$.uk')` and `'$.en'`). An admin trying to save another page with `filosofiia` or `philosophy` will hit the DB constraint. No application-level reservation needed.

### Reusable URL config

Introduce a small config key so header, footer, and tests resolve the URL consistently:

```php
// config/content.php
'philosophy_slug' => ['uk' => 'filosofiia', 'en' => 'philosophy'],
```

Header and footer use `route('page.show', ['slug' => config('content.philosophy_slug')[$locale]])` — same pattern as the existing `help_pages` block in `footer.blade.php`.

## Block composition

The page renders three blocks via `Page::visibleBlocks()`, in order. Two blocks already exist; one is new.

### Block 1 — `about_hero` (NEW)

Maps to `.about-hero` + `.about-stats` in `styles.css:819–830` and the JSX in `pages-other.jsx:6–37`.

**BlockType enum.** Add case `AboutHero = 'about_hero'` to `App\Enums\BlockType`.

**Data shape:**

```php
[
    'type' => 'about_hero',
    'data' => [
        'is_visible' => true,
        'anchor' => null,                       // optional, like other blocks
        'eyebrow' => ['uk' => 'Про дім', 'en' => 'About the house'],
        'title' => [
            'uk' => 'Парфумерний дім на перетині трьох світів',
            'en' => 'A perfume house at the crossing of three worlds',
        ],
        'lead' => [                              // short subtitle
            'uk' => 'Levant Parfums — це 22 композиції, 20 років досвіду парфумерної школи та три країни в одному підписі. Без переплати за логотип.',
            'en' => 'Levant Parfums — 22 compositions, twenty years of perfumery school, three countries in one signature. No premium for a logo.',
        ],
        'body' => [                              // longer paragraph
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
```

**Filament admin block.** New class `app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php`. Pattern mirrors `HeroBlock`:

- Toggle `is_visible` + `anchor` (`commonFields()`).
- `TranslatableTabs::make('eyebrow')`.
- `TranslatableTabs::make('title', required: true)`.
- `TranslatableTabs::make('lead', component: Textarea::class)`.
- `TranslatableTabs::make('body', component: Textarea::class)`.
- `FileUpload::make('image_path')` storing under `pages/blocks/`, like `HeroBlock`.
- `Repeater::make('stats')` — 0..4 items, with `TextInput::make('num')` and `TranslatableTabs::make('meta_label')`.

Register the new block in `PageForm.php` Builder.

Add translations to `lang/{uk,en}/content.php`:

```
blocks.about_hero.label    // "Шапка «Про дім»" / "About hero"
blocks.about_hero.add_stat // "Додати статистику" / "Add stat"
blocks.fields.lead         // "Підзаголовок" / "Lead"
blocks.fields.body         // already exists for other blocks — reuse
blocks.fields.stats        // "Статистика" / "Stats"
blocks.fields.stat_num     // "Число" / "Number"
blocks.fields.stat_label   // "Підпис" / "Label"
```

Reuse `blocks.fields.is_visible`, `blocks.fields.anchor`, `blocks.fields.eyebrow`, `blocks.fields.title`, `blocks.fields.image_path` from existing block translations.

**Blade view.** `resources/views/pages/blocks/about_hero.blade.php`. Structure (one-to-one with `pages-other.jsx:7–37`):

```blade
<section class="about-hero reveal" @if(!empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <x-site.breadcrumbs :items="[
            ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
            ['label' => $page->title],
        ]"/>
        <div class="grid">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                <h1>{{ $title }}</h1>
                @if($lead)<p class="lead">{{ $lead }}</p>@endif
                @if($body)<p class="body">{{ $body }}</p>@endif
            </div>
            <div class="img">
                @if($imageUrl)
                    <img src="{{ $imageUrl }}" alt="{{ $title }}"
                         width="800" height="800"
                         loading="eager" fetchpriority="high">
                @endif
            </div>
        </div>
        @if(!empty($stats))
            <div class="about-stats">
                @foreach($stats as $stat)
                    <div class="stat">
                        <div class="num">{{ $stat['num'] }}</div>
                        <div class="lbl">{{ $statLabel($stat) }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</section>
```

Locale resolution helpers (`$t`, `$statLabel`) follow the established `brand_story.blade.php` pattern.

**CSS.** New `resources/css/site/components/about-hero.css`. Copy `styles.css:819–830` adjusted to use the project's CSS variables (`--ink`, `--ink-soft`, `--ink-mute`, `--accent`, `--line-soft`, `--ease-out`, `--font-serif`). Add `@import './components/about-hero.css'` to `resources/css/site/index.css`.

Mobile breakpoints. The source CSS has no mobile rule for `.about-hero .grid`, so on narrow viewports the hero would stay at 2 columns with an 80px gap — cramped to broken. Match the project pattern used by `.manifesto .grid` and add:

```css
@media (max-width: 800px) {
  .about-hero .grid { grid-template-columns: 1fr; gap: 32px; align-items: start; }
}

@media (max-width: 900px) {
  .about-stats { grid-template-columns: 1fr 1fr; }
  .about-stats .stat:nth-child(2) { border-right: none; }
  .about-stats .stat:nth-child(-n+2) { border-bottom: 1px solid var(--line-soft); }
}
```

The 800/900px split mirrors the source — the stats grid drops to 2x2 first, the hero stacks slightly later.

### Block 2 — `text` (EXISTING)

Manifesto. Identical to the block already on the homepage (see `PageSeeder.php:365–381`).

```php
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
```

Note the **third paragraph** added vs. the homepage version (from `pages-other.jsx:53–57`) — Philosophy is the long form.

### Block 3 — `brand_story` (EXISTING)

Three points. Content identical to the homepage block (`PageSeeder.php:395–423`). Copy as-is. No code changes.

## Navigation

### Header (`resources/views/components/site/header.blade.php`)

Extend the `$nav` array (header.blade.php:4–8) with a `philosophy` entry, placed before `articles` to match the visual order from the design (`home / catalog / philosophy / articles`):

```php
$philosophySlug = config('content.philosophy_slug')[$locale] ?? config('content.philosophy_slug')['uk'];
$philosophyUrl  = route('page.show', ['slug' => $philosophySlug]);

$nav = [
    ['key' => 'home',       /* ... */],
    ['key' => 'catalog',    /* ... */],
    ['key' => 'philosophy', 'url' => $philosophyUrl, 'match' => fn ($r) => str_starts_with($r, '/' . $philosophySlug)],
    ['key' => 'articles',   /* ... */],
];
```

Add `nav.philosophy` to `lang/uk/site.php` and `lang/en/site.php`:

- uk: `'philosophy' => 'Філософія'`
- en: `'philosophy' => 'Philosophy'`

### Footer (`resources/views/components/site/footer.blade.php`)

In the existing nav column (lines 18–25), insert `<li>` for Philosophy, again before Articles, using the same `route('page.show', ...)` construct as the help links.

### Homepage Hero CTA

Leave `secondary_cta_url = '#manifesto'` on the homepage (current behaviour). Users discover the Philosophy page via the header nav. Rationale: avoids locale-dependent absolute URLs inside seeded block data, no rework on the hero block.

## Locale + slug uniqueness

`pages` already has functional unique indexes on `JSON_EXTRACT(slug, '$.uk')` and `'$.en'` (MySQL + SQLite branches in migration `2026_05_23_055707_create_pages_table.php`). New row obeys them — seeded slugs are unique by construction.

## Testing

New file `tests/Feature/Content/PhilosophyPageTest.php`:

1. **Renders at the uk slug.** Seed the page via factory or call `PageSeeder` partially; GET `/uk/filosofiia` → 200, response contains the about_hero title.
2. **Renders at the en slug.** Same for `/en/philosophy`.
3. **Hidden block stays hidden.** Save the page with `about_hero.is_visible = false`; GET returns 200 but does not include the hero title — relies on `Page::visibleBlocks()`.
4. **Stats render.** Response contains the four stat numbers (22, 2, 3, 20) and one of the localized labels.
5. **Header has a Philosophy link.** GET `/uk`. Existing footer pattern uses `route('page.show', [...])` which returns an **absolute** URL (e.g. `http://localhost/uk/filosofiia`), so assertions on a relative `href` will be flaky. Resolve the expected URL in the test via `route('page.show', ['slug' => config('content.philosophy_slug')['uk']])` and assert against that, or use two looser `assertSee()` calls — one for `filosofiia` (the slug appears in the href) and one for the localized link text `Філософія`.

Existing test patterns:
- `tests/Feature/Content/Filament/ArticleResourceTest.php` for Livewire-based admin assertions (only needed if we want to test the Filament block form — optional).
- `tests/Pest.php` auto-applies `RefreshDatabase` to `tests/Feature`.

All tests must pass on SQLite (`:memory:`) per `phpunit.xml`.

## Acceptance criteria

- `php artisan migrate:fresh --seed` creates a Philosophy page at `/uk/filosofiia` and `/en/philosophy`.
- The page renders three sections in order: about_hero (with breadcrumbs and 4 stats), manifesto, three-points.
- Header nav and footer nav include "Філософія" / "Philosophy".
- Existing homepage manifesto + brand_story sections remain unchanged.
- `composer test` is green; `./vendor/bin/pint` reports no issues.
- Admin can edit the `about_hero` block from Filament with translatable tabs for all text fields.

## Risks

- **Content drift between homepage and philosophy.** Manifesto text is duplicated between two seeded blocks. If marketing updates one, the other will lag. Accepted — same risk pattern as homepage vs. help pages.
- **Seeder overwrites admin edits.** `PageSeeder` is upsert-style. After admin edits the page through Filament, the next `migrate:fresh --seed` will reset content. Same behaviour as existing pages — treat seed data as initial state, not source of truth.
- **About-hero LCP.** Hero image is the largest element on the page. The view must set `loading="eager" fetchpriority="high"` and explicit `width`/`height` to avoid CLS. Mirrors the fix applied to article covers in commit `ff2c7e7`.

## File-level change list

New files:

- `app/Filament/Resources/Pages/Schemas/Blocks/AboutHeroBlock.php`
- `resources/views/pages/blocks/about_hero.blade.php`
- `resources/css/site/components/about-hero.css`
- `tests/Feature/Content/PhilosophyPageTest.php`

Modified files:

- `app/Enums/BlockType.php` — add `AboutHero` case.
- `app/Filament/Resources/Pages/Schemas/PageForm.php` — register the new block.
- `config/content.php` — add `philosophy_slug` map. `reserved_slugs` is **not** modified (would block the seeder).
- `database/seeders/Content/PageSeeder.php` — seed the Philosophy page.
- `lang/uk/content.php`, `lang/en/content.php` — labels for the new block (`blocks.about_hero.*`).
- `lang/uk/site.php`, `lang/en/site.php` — `nav.philosophy` string.
- `resources/css/site/index.css` — import `components/about-hero.css`.
- `resources/views/components/site/header.blade.php` — insert Philosophy nav entry.
- `resources/views/components/site/footer.blade.php` — insert Philosophy footer link.
