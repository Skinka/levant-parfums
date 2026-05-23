# Page Builder для главной (и будущих лендингов)

**Date:** 2026-05-23
**Status:** Approved (revised 2026-05-23 after code review — see revision log)
**Scope:** Расширение модели `Page` + Filament Builder + Blade-шаблоны + роутинг главной и страниц. Финальная вёрстка блоков — out of scope.

## Контекст

Главная страница сайта будет лендингом со структурой из секций: hero, товары, описание, "о нас" и т.д. Сейчас `/` отдаёт заглушку `welcome.blade.php`. Нужен механизм в админке, который позволит:

- редактировать тексты и изображения каждой секции;
- включать/выключать блоки;
- менять порядок блоков перетаскиванием;
- работать в двух языках (uk/en — как остальной проект);
- добавлять новые поля в блоки **позже**, когда появится дизайн, без переписывания всей архитектуры.

Решения, принятые в brainstorming:

1. **Page Builder** через нативный Filament `Builder` field — идиоматичный инструмент для этой задачи.
2. **Расширить существующую `Page`** моделью (а не заводить отдельную сущность) — любая страница может быть лендингом через поле `template`.
3. **Translatable стратегия:** колонка `blocks` **не** translatable, а текстовые поля **внутри** блоков — translatable. Структура блоков и привязки к товарам/медиа единые для всех локалей; переводится только текст. Защита от рассинхрона между uk/en.
4. **Набор блоков сейчас:** `hero`, `products`, `text`, `articles`. Расширим по дизайну.
5. **Template → Blade view:** каждому `template` соответствует свой view (`pages.templates.{template}`), каждому типу блока — свой партиал (`pages.blocks.{type}`). Простое и масштабируемое сопоставление.

## Архитектура

```
app/
  Enums/
    PageTemplate.php                # enum: Simple, Landing
    BlockType.php                   # enum: Hero, Products, Text, Articles
  Models/Content/
    Page.php                        # +template, +blocks, +is_homepage
  Http/Controllers/
    PageController.php              # / → home, /{slug} → page
  Filament/Resources/Pages/
    Schemas/PageForm.php            # + Builder для landing-режима
    Schemas/Blocks/                 # фабрики Block для Builder
      HeroBlock.php
      ProductsBlock.php
      TextBlock.php
      ArticlesBlock.php
      Concerns/TranslatableTabs.php # хелпер: табы uk/en внутри блока

database/migrations/
  ..._add_template_blocks_to_pages_table.php

resources/views/pages/
  templates/
    simple.blade.php                # рендерит markdown content
    landing.blade.php               # итерирует $page->visible_blocks → @include
  blocks/
    hero.blade.php                  # placeholder-разметка
    products.blade.php
    text.blade.php
    articles.blade.php
  layouts/
    base.blade.php                  # минимальная обёртка <html>

config/content.php
  # +reserved homepage slugs, +block defaults

lang/{uk,en}/content.php
  # + переводы template-меток, названий и полей блоков

routes/web.php
  # /          → PageController@home
  # /{slug}    → PageController@show

tests/Feature/Content/
  PageBuilderTest.php               # модель + scope
  Filament/PageBuilderResourceTest.php  # форма с Builder
  PageRoutingTest.php               # / и /{slug}
```

## Модель данных

Миграция `add_template_blocks_to_pages_table`:

```php
Schema::table('pages', function (Blueprint $table) {
    $table->string('template', 32)->default('simple')->after('content')->index();
    $table->json('blocks')->nullable()->after('template');     // НЕ translatable
    $table->boolean('is_homepage')->default(false)->after('is_published');

    // content больше не обязателен — для template=landing он не используется.
    // Изначально создан как json('content') (NOT NULL) в 2026_05_23_055707_create_pages_table.
    $table->json('content')->nullable()->change();
});

// Гарантия: ровно одна страница может быть главной. Реализуем функциональным/частичным
// уникальным индексом — синтаксис расходится между MySQL и SQLite, как уже сделано
// для slug-уникальности в create_pages_table.
if (DB::getDriverName() === 'mysql') {
    // MySQL 8.0.13+: partial индексов нет, используем CASE-выражение —
    // is_homepage=1 индексируется как 1, is_homepage=0 как NULL (NULL'ы не нарушают уникальность).
    DB::statement("CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages ((CASE WHEN is_homepage = 1 THEN 1 ELSE NULL END))");
} elseif (DB::getDriverName() === 'sqlite') {
    // SQLite поддерживает partial unique index с WHERE — чище, чем CASE.
    DB::statement("CREATE UNIQUE INDEX pages_is_homepage_uniq ON pages (is_homepage) WHERE is_homepage = 1");
}
```

> **Замечание для `down()`:** нужно сбросить `pages_is_homepage_uniq` (через `DB::statement('DROP INDEX ...')`) и вернуть `content` в not-null до того, как `Schema::table` уберёт колонки — иначе откат миграции упадёт.

В `Page::class`:

- Добавить в `$fillable`: `'template', 'blocks', 'is_homepage'`.
- Добавить в `casts()`: `'blocks' => 'array'`, `'is_homepage' => 'boolean'`, `'template' => PageTemplate::class`.
- `$translatable` — **не** включает `blocks` (это сознательное решение, см. brainstorming).
- Аксессор `visibleBlocks()`: фильтрует массив `blocks` по `is_visible !== false` с сохранением порядка.
- Скоуп `scopeHomepage(Builder $q)` → `where('is_homepage', true)`.

Enum `PageTemplate`:

```php
enum PageTemplate: string {
    case Simple = 'simple';
    case Landing = 'landing';

    public function label(): string { return trans("content.template.{$this->value}"); }
    public static function options(): array { /* как Gender */ }
}
```

Enum `BlockType` — аналогично, кейсы `Hero`, `Products`, `Text`, `Articles`. Используется как единый источник правды для имён блоков и поиска view (`pages.blocks.{$type->value}`).

## Структура данных блока

Каждый элемент массива `blocks`:

```json
{
  "type": "hero",
  "data": {
    "is_visible": true,
    "anchor": "intro",
    "title": {"uk": "...", "en": "..."},
    "subtitle": {"uk": "...", "en": "..."},
    "image_path": "pages/blocks/abc123.webp",
    "cta_label": {"uk": "Купити", "en": "Buy"},
    "cta_url": "/products"
  }
}
```

- `is_visible` и `anchor` — общие для всех блоков (через хелпер `commonFields()`).
- Текстовые поля хранятся как `{uk, en}` напрямую в `data`. **Не** через Spatie HasTranslations (т.к. `blocks` не translatable) — UI-таб локали внутри блока пишет в эти ключи руками, рендер на фронте делает `$data['title'][$locale] ?? $data['title']['uk']`.
- **Медиа в блоках:** на этом этапе используем обычный Filament `FileUpload` с диском `public`, путь к файлу сохраняется как строка в `data.image_path` (или `data.images[]` для массива). **Не** Spatie `SpatieMediaLibraryFileUpload` — он спроектирован как relationship-поле (dehydrated, UUID, привязка к media collection модели) и внутри JSON-state Builder работает непредсказуемо: state не попадает в `blocks.data` ожидаемым образом, а несколько upload-полей в одной коллекции на Page чистят чужие attachments как abandoned. Когда появится дизайн и реальная потребность в конверсиях/responsive images — перейдём на Spatie с `customProperties(['block_uid' => ...])` и `filterMediaUsing()` для связи media с конкретным блоком (см. «Открытые вопросы»).
- Товары/статьи: массивы ID + `sort_order`, реализованные через Repeater внутри блока.

## Filament — форма Page

Логика `PageForm::mainTab()` меняется так:

1. Существующие поля `title`/`slug`/`intro`/`is_published` остаются.
2. Добавляется `Select::make('template')` с опциями `PageTemplate::options()`, `->live()`, `->required()`, default `simple`.
3. Существующий `MarkdownEditor::make('content')` оборачивается в `->visible(fn (callable $get) => $get('template') === 'simple')` И `->required(fn (callable $get) => $get('template') === 'simple')` (раньше было безусловно required). В landing-режиме поле скрыто и сохраняется как `null` — миграция выше делает колонку nullable.
4. Новый `Builder::make('blocks')` — `->visible(fn (callable $get) => $get('template') === 'landing')`:

```php
Builder::make('blocks')
    ->label(trans('content.fields.blocks'))
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
    ->cloneable();
```

5. Добавляется `Toggle::make('is_homepage')` (опционально — в форме видно, что страница — главная; снять флаг можно).

В `seoTab()` и `imagesTab()` ничего не меняется.

### Блок (Filament Builder Block) — паттерн

Каждый Block — отдельный класс-фабрика в `Schemas/Blocks/`:

```php
class HeroBlock {
    public static function make(): Block {
        return Block::make('hero')
            ->label(trans('content.blocks.hero.label'))
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                ...self::commonFields(),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('subtitle', component: Textarea::class),
                TranslatableTabs::make('cta_label'),
                TextInput::make('cta_url')
                    ->maxLength(2048)
                    ->helperText(trans('content.blocks.fields.cta_url.helper')),
                    // НЕ ->url() — Laravel `url` validator требует абсолютный URL со схемой
                    // и отбрасывает внутренние ссылки вроде "/products". Финальная валидация
                    // (internal-or-absolute через regex или page/product reference picker) —
                    // когда будет дизайн и список сценариев.
                FileUpload::make('image_path')
                    ->disk('public')
                    ->directory('pages/blocks')
                    ->image()
                    ->imageEditor()
                    ->maxSize(4096),
                    // Обычный FileUpload, путь сохраняется в blocks.data.image_path как string.
                    // См. секцию «Структура данных блока» — почему не Spatie.
            ]);
    }

    protected static function commonFields(): array {
        return [
            Toggle::make('is_visible')->default(true),
            TextInput::make('anchor')->prefix('#')->alphaDash(),
        ];
    }
}
```

### `TranslatableTabs` — хелпер для перевода поля внутри блока

```php
// Schemas/Blocks/Concerns/TranslatableTabs.php
class TranslatableTabs {
    public static function make(string $field, bool $required = false, string $component = TextInput::class): Tabs {
        return Tabs::make($field)
            ->label(trans("content.blocks.fields.{$field}"))
            ->tabs(collect(config('app.supported_locales', ['uk', 'en']))
                ->map(fn ($locale) => Tab::make(strtoupper($locale))
                    ->schema([
                        $component::make("{$field}.{$locale}")
                            ->label(false)
                            ->required($required && $locale === 'uk'),
                    ]))->all());
    }
}
```

Это даёт визуально аккуратные мини-табы локалей внутри каждого блока без подключения LaraZeus-плагина (он работает на уровне колонки модели, а `blocks` у нас не translatable).

### Блок `products` — Repeater для ручной выборки

Внутри `ProductsBlock::make()` повторяем паттерн `notesRepeater()` из `ProductForm.php:136-150`:

```php
Repeater::make('items')
    ->schema([
        Select::make('product_id')
            ->options(fn () => Product::query()->orderBy('slug')->get()
                ->mapWithKeys(fn (Product $p) => [$p->id => $p->name])->all())
            ->searchable()
            ->required(),
    ])
    ->reorderable()
    ->defaultItems(0)
    ->addActionLabel(trans('content.blocks.products.add_item'));
```

Внутри `blocks.data.items` это сохранится как `[{product_id: 1}, {product_id: 2}, ...]` — порядок массива задаёт порядок вывода. Авто-режим (по тегу/бренду/featured) — out of scope, добавим когда понадобится.

Блок `articles` — то же самое с `Article::query()`.

## Frontend — роутинг + рендер

`routes/web.php`:

```php
Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', [PageController::class, 'home'])->name('home');
    Route::get('/{slug}', [PageController::class, 'show'])->name('page.show');
});
```

`PageController`:

```php
public function home() {
    $page = Page::query()->homepage()->published()->firstOrFail();
    return view("pages.templates.{$page->template->value}", ['page' => $page]);
}

public function show(string $slug) {
    $locale = app()->getLocale();
    $page = Page::query()
        ->whereJsonContains("slug->{$locale}", $slug)
        ->published()
        ->firstOrFail();
    return view("pages.templates.{$page->template->value}", ['page' => $page]);
}
```

`resources/views/pages/templates/simple.blade.php`:

```blade
@extends('pages.layouts.base')
@section('content')
    <article>
        <h1>{{ $page->title }}</h1>
        {!! Str::markdown($page->content ?? '') !!}
    </article>
@endsection
```

`resources/views/pages/templates/landing.blade.php`:

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

Каждый партиал в `pages/blocks/` — placeholder-разметка (просто текст + изображение в самой простой обвязке), задача которой сейчас — доказать что pipeline работает. Финальная разметка появится с дизайном.

Пример `pages/blocks/hero.blade.php`:

```blade
@php($locale = app()->getLocale())
<section @if($data['anchor'] ?? false) id="{{ $data['anchor'] }}" @endif>
    <h1>{{ $data['title'][$locale] ?? $data['title']['uk'] ?? '' }}</h1>
    @if($data['subtitle'] ?? null)
        <p>{{ $data['subtitle'][$locale] ?? $data['subtitle']['uk'] ?? '' }}</p>
    @endif
    @if($path = ($data['image_path'] ?? null))
        <img src="{{ Storage::disk('public')->url($path) }}" alt="">
    @endif
    @if(($data['cta_url'] ?? null) && ($data['cta_label'] ?? null))
        <a href="{{ $data['cta_url'] }}">{{ $data['cta_label'][$locale] ?? $data['cta_label']['uk'] }}</a>
    @endif
</section>
```

## Резервирование slug `home`

Главная не имеет slug-роута (она — `/`), но чтобы редактор не создал страницу со slug `home`, который перетянул бы на себя смысл, добавляем `home` в `config/content.php → reserved_slugs`.

Главная находится строго через `is_homepage`. Один уникальный индекс гарантирует, что главная одна; если флаг снят со всех — `home()` отдаёт 404 (явный сигнал что сайт не настроен).

## Сидинг

`PageSeeder` сейчас пустой — заменим:

- Создать одну `Page` с `is_homepage = true`, `template = landing`, `slug = ['uk' => 'home-uk', 'en' => 'home-en']` (slug нужен только из-за NOT NULL — фактически не используется), пустым `blocks = []`. Редактор зайдёт в админку и наполнит.
- Альтернатива (рекомендую): засеять `blocks` с одним placeholder-блоком каждого типа (всего 4), чтобы при первом запуске сразу было видно, как работает страница.

## Тесты (Pest)

```
tests/Feature/Content/PageBuilderTest.php
  - template enum cast работает
  - blocks cast → array
  - visibleBlocks() фильтрует is_visible=false и сохраняет порядок
  - DB запрещает второй is_homepage=true (QueryException)
  - homepage scope возвращает только страницу с is_homepage=true

tests/Feature/Content/PageRoutingTest.php
  - GET /uk → 200, рендерит landing-шаблон главной
  - GET /uk → 404 если is_homepage не выставлен
  - GET /uk/{slug} → 200 для опублікованої simple-страницы
  - GET /uk/{slug} → 200 для landing-страницы (не главной)
  - блок с is_visible=false НЕ рендерится в HTML

tests/Feature/Content/Filament/PageBuilderResourceTest.php
  - переключение template=landing скрывает content и показывает Builder
  - сохранение страницы с 2 блоками сохраняет порядок в blocks JSON
  - можно отметить is_homepage; повторная попытка на другой странице падает с QueryException
```

Бродовые render-тесты Filament-форм по-прежнему не пишем — следуем паттерну `ProductResourceTest`.

## Порядок реализации

1. **Enums + конфиг.** `PageTemplate`, `BlockType`. Добавить `home` в `reserved_slugs`.
2. **Миграция** + правки `Page` (fillable, casts, scope, visibleBlocks, media collection `blocks`).
3. **Переводы** `lang/{uk,en}/content.php` — template labels, block labels, block field labels.
4. **Filament: Blocks** — `HeroBlock`, `ProductsBlock`, `TextBlock`, `ArticlesBlock` + `TranslatableTabs` хелпер.
5. **Filament: PageForm** — `Select template`, `Builder blocks` под visibility-флагом, `Toggle is_homepage`.
6. **Контроллер + роуты** — `PageController::home` / `show`, обновить `routes/web.php`.
7. **Blade-шаблоны** — `layouts/base`, `templates/{simple, landing}`, `blocks/{hero, products, text, articles}` — placeholder-разметка.
8. **Сидер** — обновить `PageSeeder` (главная + 4 placeholder-блока).
9. **Тесты** — три файла выше.
10. **Верификация** — composer test + ручной прогон.

## Верификация

- `composer test` — все тесты зелёные, включая новые в `tests/Feature/Content`.
- `php artisan migrate:fresh --seed` — БД поднимается без ошибок, сидер создаёт главную.
- `php artisan serve` → `/admin/pages` — в форме виден переключатель template; в режиме `landing` появляется Builder; можно добавить блок, переключить локаль, ввести title `uk`/`en`, сохранить.
- `/uk` отдаёт landing с placeholder-блоками; `/en` — то же с английским текстом.
- Создаём вторую landing-страницу со slug `about-us`, ставим `is_homepage` — ожидаем ошибку валидации/QueryException (главная только одна).
- В блоке выключаем `is_visible` → блок исчезает с фронта; контент сохранён в БД.

## Что НЕ входит в этот этап

- Финальная вёрстка блоков и стили — Blade-партиалы намеренно содержат placeholder-разметку.
- Авто-режим выборки товаров/статей в блоках (по тегу/бренду/featured) — сейчас только ручной список через Repeater.
- Кэширование рендера (`page.cache` теги, full-page cache) — добавим при появлении нагрузки.
- JSON-LD, hreflang, sitemap для лендингов — соответствует решению из spec пред. этапа.
- Дополнительные блоки (image_text, cta_banner, faq, testimonials) — добавим по мере появления дизайна, паттерн готов.
- История ревизий контента, превью неопубликованных версий, A/B блоков.

## Открытые вопросы (требуют решения позже)

- **Осиротевшие файлы блоков:** при удалении блока через Builder его `image_path` исчезает из JSON, но файл на диске `public/pages/blocks/` остаётся. То же самое при замене картинки. Решение: в `Page::saved` хук собирает все `image_path` из текущих блоков, сравнивает с тем, что физически лежит в директории Page (или с предыдущим snapshot из `original` атрибутов) и удаляет лишнее через `Storage::disk('public')->delete(...)`. **Не делаем сейчас** — заготовка, объёмы небольшие; вернёмся, когда станет проблемой.
- **Миграция на Spatie MediaLibrary для блоков:** когда появится финальный дизайн с responsive images / WebP-конверсиями / OG-картинками — заменим простой `FileUpload` на `SpatieMediaLibraryFileUpload` с `customProperties(['block_uid' => $uuid])` (где `block_uid` — UUID блока в Builder) и `filterMediaUsing(fn ($query) => $query->where('custom_properties->block_uid', $uuid))`. Это правильный путь привязать конкретное медиа к конкретному блоку внутри JSON без конфликтов между upload-полями одной коллекции. Потребует data-миграции существующих `image_path` → media records.
- **Internal-or-absolute URL валидация:** сейчас `cta_url` — свободная строка с длиной до 2048. Когда у редактора появится потребность в reference picker (выбрать страницу / товар / артикул из списка) — заменим строку на структуру `{type: 'page'|'product'|'external', id?: int, url?: string}` и Filament-композитный input.
- **Translatable slug `home`:** редактор может в UI попробовать поменять slug главной. Спорный кейс. Пока полагаемся на reserved_slugs и тот факт, что главная находится по `is_homepage`, а не по slug.

## Revision log

**2026-05-23 — post-review revision (4 comments addressed):**

- **P1 — `SpatieMediaLibraryFileUpload` в Builder ломает контракт** → заменили на обычный `FileUpload` с диском `public`, путь сохраняется в `blocks.data.image_path` как строка. Рендер в Blade обновлён под `Storage::disk('public')->url()`. Миграция на Spatie с `customProperties(['block_uid' => ...])` вынесена в открытые вопросы.
- **P1 — landing-страница падает из-за NOT NULL `content`** → миграция `add_template_blocks_to_pages_table` дополнительно делает `content` nullable. `MarkdownEditor::make('content')` теперь `required()` только при `template === 'simple'`. Сидер главной может оставить `content = null`.
- **P2 — `cta_url` с `->url()` отбрасывает относительные ссылки** → убрали `->url()`, используем `TextInput` с `maxLength(2048)`. Reference picker — будущая итерация.
- **P2 — SQLite-ветка для `is_homepage` индекса** → добавили `DB::getDriverName()` развилку: MySQL — `CASE`-выражение, SQLite — partial index с `WHERE is_homepage = 1`. Соответствует паттерну существующих slug-индексов.
