# Главная страница — натягиваем дизайн

**Date:** 2026-05-24
**Status:** Approved
**Scope:** Расширение существующего Page Builder (`docs/superpowers/specs/2026-05-23-page-builder-design.md`) — финальная вёрстка лендинга главной согласно `docs/superpowers/design-sources/levant-parfums/`. Никаких миграций БД, никаких новых таблиц.

## Контекст

`Page` со `is_homepage = true, template = landing` уже работает: контроллер `PageController@home` отдаёт `pages.templates.landing.blade.php`, та итерируется по `Page::visibleBlocks()` и инклюдит `pages.blocks.{type}.blade.php`. Сегодня все четыре партиала (`hero`, `products`, `text`, `articles`) — голые `<section>` со стубом.

В `docs/superpowers/design-sources/levant-parfums/project/pages-home.jsx` лежит эталонный React-прототип главной (HTML+CSS+копирайт `t.*` в `data.js`), который и натягиваем. Эталон содержит **10 секций** в порядке: `Hero · Manifesto · ProductSlider · ThreePoints · Collections · Guide · ProductSlider · Advantages · Reviews · BlogPreview`. `ProductSlider` встречается дважды (бестселлеры + новинки); все остальные секции уникальны.

Решения, принятые в brainstorming (2026-05-24):

1. **Подход:** 1:1 по эталону. 10 визуальных секций = 8 типов блоков (`products` и `pillars` повторяются). Ключ `text` сохраняем, но рендер и поля редактора переводим в стиль «manifesto» (2-col, italic), чтобы не ломать `PageSeeder` и JSON-данные.
2. **Новые типы блоков:** `brand_story` (ThreePoints), `series_duo` (Collections), `pillars` (Guide + Advantages), `testimonials` (Reviews → slider).
3. **Расширяются:** `hero` (editorial split + floating + 3-up meta + 2 CTAs), `products` (rich header), `articles` (rich header + жёсткая сетка 3).
4. **Тема:** вся главная остаётся в `theme-cream`. Визуальный ритм — фоновыми токенами (`bg-2` для `manifesto`, `bg-2` для `pillars.is-tinted`). Никаких `theme-onyx` врезок и никакого per-block surface select (кроме `pillars`).
5. **Икон-набор не вводим.** Advantages рендерится той же `pillars`-разметкой (numbered editorial), что и Guide. Поле `surface = default|tinted` отделяет визуально (Guide → tinted, Advantages → default).
6. **Reviews — slider**, не grid (как в эталоне). Карточка `.review` сохраняется, но обёртка — горизонтальный track в стиле `.product-slider .track`.
7. **Отзывы — repeater внутри блока.** Никакой отдельной сущности `Testimonial` в БД.
8. **Серии в `series_duo` — ручная пара** (фиксированный repeater на 2 слота, выбор `series_id` из существующих Series). Описание/картинки живут в JSON блока, не в Series.

## Архитектура (что появляется и что меняется)

```
app/
  Enums/
    BlockType.php                         # +4 case: BrandStory, SeriesDuo, Pillars, Testimonials
  Filament/Resources/Pages/Schemas/
    PageForm.php                          # +4 блока в Builder
    Blocks/
      HeroBlock.php                       # extend (см. ниже)
      TextBlock.php                       # repurpose → manifesto
      ProductsBlock.php                   # extend (eyebrow/title/cta_*)
      ArticlesBlock.php                   # extend + min=max=3
      BrandStoryBlock.php                 # NEW
      SeriesDuoBlock.php                  # NEW
      PillarsBlock.php                    # NEW
      TestimonialsBlock.php               # NEW

resources/views/
  pages/blocks/
    hero.blade.php                        # rewrite
    text.blade.php                        # rewrite (manifesto)
    products.blade.php                    # rewrite
    brand_story.blade.php                 # NEW
    series_duo.blade.php                  # NEW
    pillars.blade.php                     # NEW
    testimonials.blade.php                # NEW
    articles.blade.php                    # rewrite
  components/site/
    article-card.blade.php                # NEW
    review-card.blade.php                 # NEW
    pillar.blade.php                      # NEW (атом для brand_story и pillars)

resources/css/site/
  components/
    hero.css                              # NEW
    manifesto.css                         # NEW
    three-points.css                      # NEW
    collections.css                       # NEW
    pillars.css                           # NEW
    testimonials.css                      # NEW
    article-card.css                      # NEW
  index.css                               # +7 @import

database/seeders/
  Content/PageSeeder.php                  # переписать blocks (10 элементов)
  images/                                 # +3 jpg, копируем из design-sources

lang/{uk,en}/
  content.php                             # +4 block labels, ~20 fields, surface labels

tests/Feature/Content/
  HomePageRenderTest.php                  # NEW
  Filament/PageBuilderHomeTest.php        # NEW
```

Никаких изменений: модели `Page` (поля/касты не трогаем), миграции, контроллер, маршруты, `PageTemplate` enum, `LaravelLocalization` middleware, header/footer/layout, продуктовая страница.

## Контракты данных блоков

Все text-поля translatable, хранятся как `{uk, en}` напрямую в JSON `data`. `is_visible` и `anchor` наследуются из `Block::commonFields()` (как сейчас). Изображения — `FileUpload` на диске `public`, путь сохраняется в строку.

### `hero`

```yaml
eyebrow:             transl string                # "Колекція 2026 · Luxury × Onyx"
title_top:           transl string                # "Нішевий аромат." (верхняя строка h1, normal)
title_bottom:        transl string                # "Чесна ціна." (нижняя строка h1, italic + accent)
lead:                transl textarea              # lead-абзац
floating_label:      transl string                # "Іспанія → Туреччина → Україна" (полоска над сеткой)
primary_cta_label:   transl string
primary_cta_url:     string                       # допускает "/products" и "https://..."
secondary_cta_label: transl string (опц)
secondary_cta_url:   string (опц)
image_path:          string                       # вертикальная картинка справа (~4:5 / 3:4)
meta:                repeater, exactly 3:
   num:    string                                 # "22" / "2" / "3"
   label:  transl string                          # "Композиції" / "Series" / ...
```

### `text` (manifesto)

```yaml
eyebrow:    transl string                         # "Філософія"
title:      transl string                         # italic display, поддерживает декоративный glyph "
body:       transl textarea                       # multi-paragraph; рендер делает nl2br
signature:  transl string (опц)                   # "— Команда Levant", italic gold
```

Старые поля `title` / `body` совместимы по ключам.

### `products`

```yaml
eyebrow:    transl string                         # "Бестселери" / "Новинки"
title:      transl string                         # "Найулюбленіші у 2026"
cta_label:  transl string (опц)                   # "Усі бестселери"
cta_url:    string (опц)                          # "/products?sort=pop"
items:      repeater (как сейчас): { product_id: int }
```

### `brand_story` (ThreePoints)

```yaml
eyebrow:    transl string                         # "Три точки. Один путь."
title:      transl string                         # italic, "Levant — перетин трьох світів"
body:       transl textarea                       # lead под title (центр, 64ch)
pillars:    repeater, exactly 3:
   label:    transl string                        # "Іспанія" — italic serif clamp(28, 2.4vw, 36)
   caption:  transl string                        # "Народження ідеї" — eyebrow
```

Картинки нет. Между 3 точками рендерятся `.threepoints .conn` (горизонтальные золотые линии).

### `series_duo` (Collections)

```yaml
eyebrow:    transl string                         # "Колекції"
title:      transl string                         # "Дві серії, одна філософія"
items:      repeater, exactly 2 (left/right):
   series_id:    Select → existing Series (используется в cta_url через slug)
   image_path:   string                           # фоновая картинка карточки
   kicker:       transl string                    # "17 ароматів · жіноча та унісекс"
   title:        transl string                    # override названия (если пусто — Series->name)
   description:  transl textarea                  # короткое описание поверх overlay
   cta_label:    transl string                    # "Перейти до серії"
```

`cta_url` для каждой карточки вычисляется в Blade через безопасный lookup:

```blade
@php
    $series = ! empty($item['series_id']) ? \App\Models\Catalogue\Series::find($item['series_id']) : null;
    $ctaUrl = $series
        ? route('products.index', ['series' => $series->slug])
        : route('products.index');
@endphp
```

`Series::find()` возвращает `null` при отсутствии записи, поэтому `->slug` нельзя вызывать на нём напрямую — это вызовет fatal. Stale `series_id` (например, после удаления Series) даёт fallback на общий каталог.

### `pillars` (Guide + Advantages)

```yaml
eyebrow:    transl string                         # "Гід" / "Чому Levant"
title:      transl string                         # "Знайдіть аромат за три кроки"
body:       transl textarea (опц)                 # lead под title (используется в Guide)
surface:    select "default" | "tinted"           # tinted = bg-2 (Guide), default = без фона (Advantages)
items:      repeater, min=3, max=4:
   eyebrow:  transl string (опц)                  # "01 · Сімейство"; если пусто — auto-номер
   title:    transl string                        # italic serif h3
   body:     transl textarea
```

### `testimonials` (Reviews → slider)

```yaml
eyebrow:    transl string                         # "Відгуки"
title:      transl string                         # "Що пишуть про Levant"
cta_label:  transl string (опц)                   # "Усі відгуки"
cta_url:    string (опц)
items:      repeater, min=2:
   quote:    transl textarea
   author:   string                               # имя; не translatable
   city:     transl string (опц)                  # "Київ" / "Kyiv"
   rating:   int 1..5 (опц)
```

Карточка отзыва — компонент `<x-site.review-card>`, обёртка — горизонтальный track (scroll-snap, no-js), как в `.product-slider .track`.

### `articles`

```yaml
eyebrow:    transl string                         # "Журнал"
title:      transl string                         # "Свіже з нашого редакторського столу"
cta_label:  transl string (опц)                   # "Усі статті"
cta_url:    string (опц)
items:      repeater, min=3, max=3: { article_id: int }
```

`<x-site.article-card>` — новая компонента. Источники: `Article->title`, `Article->intro/excerpt`, `Article->published_at`, `Article->getFirstMediaUrl('primary', 'card')`. Если у статьи нет изображения — placeholder в стиле `product-card .placeholder`.

## Filament — Block-классы и PageForm

`PageForm::mainTab()` — единственное изменение в файле: список блоков Builder расширяется до 8:

```php
Builder::make('blocks')
    ->blocks([
        HeroBlock::make(),
        TextBlock::make(),         // manifesto
        ProductsBlock::make(),
        BrandStoryBlock::make(),
        SeriesDuoBlock::make(),
        PillarsBlock::make(),
        TestimonialsBlock::make(),
        ArticlesBlock::make(),
    ])
    ->collapsible()
    ->collapsed()
    ->blockNumbers(false)
    ->reorderableWithButtons()
    ->cloneable();
```

Иконки блоков (heroicons):

| Блок        | Icon                            |
|-------------|---------------------------------|
| hero        | `o-rectangle-stack` (как сейчас)|
| text        | `o-document-text` (как сейчас)  |
| products    | `o-shopping-bag` (как сейчас)   |
| brand_story | `o-map`                         |
| series_duo  | `o-squares-2x2`                 |
| pillars     | `o-list-bullet`                 |
| testimonials| `o-chat-bubble-left-right`      |
| articles    | `o-newspaper` (как сейчас)      |

Все Block-классы наследуют существующий паттерн — статический фабричный метод `make()`, `commonFields()` для `is_visible`/`anchor`, использование `TranslatableTabs::make('field', ...)` для translatable полей. См. `2026-05-23-page-builder-design.md` § «Блок (Filament Builder Block) — паттерн».

### Repeater min/max в Filament v5

`Repeater::make('pillars')->minItems(3)->maxItems(3)` (для `brand_story` и `series_duo` — exactly 3 и 2 соответственно). Это даёт UI без кнопок "Add" сверх лимита и валидацию при save. `defaultItems(3)`/`defaultItems(2)` стартует пустой блок уже с нужным числом пустых слотов.

Для `articles.items` — `minItems(3)->maxItems(3)`. Для `testimonials.items` — `minItems(2)` без верхнего лимита. Для `pillars.items` — `minItems(3)->maxItems(4)`. Для `hero.meta` — `minItems(3)->maxItems(3)`.

### `TranslatableTabs` — без изменений

Хелпер из `app/Filament/Resources/Pages/Schemas/Blocks/Concerns/TranslatableTabs.php` (см. предыдущую спеку) поддерживает `TextInput` (default), `Textarea`, `MarkdownEditor`. Все новые translatable поля используют его как есть. Для `body` в `pillars.items` и `testimonials.items.quote` — `component: Textarea::class`. Для `text.body` — `Textarea` (multi-paragraph; markdown не нужен — параграфы делим по \n\n + nl2br в Blade).

## Frontend — партиалы, компоненты, CSS

### Партиалы

Каждый `pages/blocks/*.blade.php` следует одному шаблону:

```blade
@php($locale = app()->getLocale())
@php($t = function (string $key) use ($data, $locale) {
    $value = $data[$key][$locale] ?? null;
    return filled($value) ? $value : ($data[$key]['uk'] ?? '');
})
<section class="{block-class}" @if(!empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        ...
    </div>
</section>
```

**Важно:** именно `filled()`, не `??`. Filament `TextInput` для пустого поля сохраняет в JSON строку `''`, а не `null`. Чистый `?? $data[$key]['uk']` пропускает пустую строку как валидное значение и рендерит пустоту на `/en`, когда редактор оставил поле незаполненным. `filled()` (= не null **и** не пустая строка) даёт ожидаемый fallback на uk.

### Компоненты

**`<x-site.article-card :article="$article">`** — карточка статьи для `articles`-блока:
```
.cover (aspect-ratio 4/3, img или placeholder)
.meta (.tag · .date · .read-time)
h3 (title, serif italic)
p (intro/excerpt, 3 строки clamp)
```
`read-time` пока вычисляем как `max(1, (int) ceil($words / 200))`, где `$words = preg_match_all('/[\\p{L}\\p{N}\']+/u', strip_tags($article->content))`. **Не** `str_word_count` — стандартная PHP-функция байт-based и матчит только `[A-Za-z\']`, поэтому для украинской кириллицы возвращает 0. Unicode-aware regex `\p{L}` (любая буква) + `\p{N}` (любая цифра) корректно считает uk/en. Если поле `read_time` появится в модели — переключимся на него.

**`<x-site.review-card :item="$item">`** — карточка отзыва для слайдера `testimonials`:
```
.quote-mark "“" (gold, 80px serif)
.text (serif italic, 22px)
.meta (.name "· city" — .stars)
```
`stars` рендерится 5 раз, `★` золотом если `i <= rating`, `☆` приглушённо иначе. Если `rating` пуст — секция `.stars` скрыта.

**`<x-site.pillar :label :caption :variant="brand_story|pillars">`** — атом для одной точки в `brand_story` (gem + label + caption) и одного шага в `pillars` (eyebrow + title + body). Через `:variant` управляется markup и классы.

Альтернатива: два отдельных компонента (`brand-pillar` и `usp-step`). Решено объединить в один с variant — markup на 90% общий (eyebrow/label + serif text + caption).

### CSS

7 новых файлов под `resources/css/site/components/`. Каждый адаптирует соответствующий блок из `design-sources/levant-parfums/project/styles.css`:

- **`hero.css`** ← `.hero`, `.hero .grid`, `.hero .image-wrap`, `.hero .copy`, `.hero .meta`, `.hero .floating` (строки 329-377 эталона).
- **`manifesto.css`** ← `.manifesto`, `.manifesto .grid`, `.manifesto h2`, `.manifesto .body`, `.manifesto .body .quote-open` (378-389). Включает `background: var(--bg-2)`.
- **`three-points.css`** ← `.threepoints`, `.threepoints .head`, `.threepoints .points`, `.threepoints .pt`, `.threepoints .pt .gem`, `.threepoints .conn` (390-430).
- **`collections.css`** ← `.collections`, `.collections .grid`, и весь набор `.collection-card` (491-517).
- **`pillars.css`** ← `.guide`, `.guide .grid`, `.guide .step`, `.guide .step .num`, `.guide .step .deco` (518-541) для `default` варианта. Модификатор `.pillars.is-tinted` добавляет `background: var(--bg-2)` (как `.guide`). Поведение `.advantages .grid` с дивайдерами между ячейками (542-555) делается через `.pillars[data-count="4"]` — другая grid-template и border-right у item'ов.
- **`testimonials.css`** — карточка `.review` (557-568) + обёртка `.testimonials .track` копирует поведение `.product-slider .track` (горизонтальный CSS scroll-snap, edge-bleed `margin-right: calc(50% - 50vw)`).
- **`article-card.css`** ← `.article-card` (поведение в эталоне в секции `.blog`).

`resources/css/site/index.css` — добавить 7 `@import`-строк под существующими `components/`. Порядок не критичен, но удобнее держать рядом с `product-card.css`.

Существующий `product-slider.css` — без изменений. Существующий `product-card.css` — без изменений (используется в `products`-блоке как есть).

### JS

Никаких новых JS-модулей. Слайдер — CSS scroll-snap. Reveal-анимации (`data-reveal` атрибуты) уже подключены через существующий `resources/js/site/reveal.js`. Подобавляем `data-reveal` атрибуты к корневым `<section>` и `data-reveal-child` к карточкам/items'ам там, где в эталоне `<Reveal stagger>`.

## Theming и фоновый ритм

Вся главная остаётся под `theme-cream` (дефолт layout'а). Ритм фона достигается только через локальные CSS-классы:

- `.hero` — `var(--bg)` (cream).
- `.manifesto` — `var(--bg-2)` (тёплый тёмный cream).
- `.product-slider` (бестселлеры) — `var(--bg)`.
- `.threepoints` — `var(--bg)`.
- `.collections` — `var(--bg)`. Карточки внутри имеют свой фон-картинку.
- `.pillars.is-tinted` (Guide) — `var(--bg-2)`.
- `.product-slider` (новинки) — `var(--bg)`.
- `.pillars` (Advantages, default) — `var(--bg)` + дивайдеры между ячейками.
- `.testimonials` — `var(--bg-2)` (по аналогии с `.manifesto` — даёт контраст слайдеру).
- `.blog` (articles) — `var(--bg)`.

Это даёт ритм cream → bg-2 → cream → cream → cream → bg-2 → cream → cream → bg-2 → cream. На бэке/в админке это не контролируется (хардкод в CSS-партиалах), кроме одного исключения — `pillars.surface` (см. контракт).

## PageSeeder — полная перезапись блоков главной

`database/seeders/Content/PageSeeder.php` переписывается так, чтобы:

1. **Скопировать 3 ассета** из `docs/superpowers/design-sources/levant-parfums/project/assets/` в `database/seeders/images/` (один раз, через git). Файлы: `levant-luxury-bottle.jpg`, `levant-flacon-3.jpg`, `levant-flacon-4.jpg`.
2. **При запуске** разложить их в `storage/app/public/pages/blocks/` через `Storage::disk('public')->put(...)`. Если файл уже есть — пропустить (`Storage::disk('public')->exists(...)`).
3. **Зафиксировать порядок сидеров в `DatabaseSeeder::run()`.** Сейчас `PageSeeder` стоит перед `ArticleSeeder` (строки 46-47), из-за чего на `migrate:fresh --seed` `Article::limit(3)->pluck('id')` возвращает пустой набор, и блок articles на главной запускается с `is_visible = false`. Меняем порядок так, чтобы `ArticleSeeder` шёл перед `PageSeeder`. `ArticleSeeder` уже зависит от `ProductSeeder` (использует `Product::query()->inRandomOrder()->limit(9)`) и идёт после него — в новом порядке цепочка: `ProductSeeder → ArticleSeeder → PageSeeder`.
4. **Найти связанные сущности:**
   - `$luxury = \App\Models\Catalogue\Series::where('slug', 'luxury')->first();`
   - `$onyx = \App\Models\Catalogue\Series::where('slug', 'onyx')->first();`
   - `$bestsellers = \App\Models\Catalogue\Product::whereHas('tags', fn($q) => $q->where('slug', 'bestseller'))->limit(6)->pluck('id')->all();` (или `Product::limit(6)->pluck('id')` если bestseller-тегов в сидерах ещё нет).
   - `$newItems = \App\Models\Catalogue\Product::whereHas('tags', fn($q) => $q->where('slug', 'new'))->limit(6)->pluck('id')->all();` (fallback аналогично).
   - `$articleIds = \App\Models\Content\Article::orderByDesc('published_at')->limit(3)->pluck('id')->all();` (после реордера сидеров — не пусто).
5. **Собрать массив `blocks`** из 10 элементов в эталонном порядке. Тексты — из `data.js` (`t.hero_*`, `t.manifesto_*`, etc.). Если какая-то связь всё-таки пуста (например, нет тегированных продуктов), соответствующий блок записывается с `is_visible = false` — редактор включит вручную.
6. **Сохранить через `Page::query()->updateOrCreate(['is_homepage' => true], [...])`** — как уже сейчас.

Полный массив `blocks` главной с дословным копирайтом из `data.js` и фактическими связями (Series/Product/Article ID) выносится в implementation plan (`docs/superpowers/plans/2026-05-24-homepage.md`, будет создан на следующем этапе после этого спека). В спеке его не дублируем для краткости и чтобы спека оставалась устойчивой к точечным изменениям копирайта.

## Переводы

`lang/uk/content.php` и `lang/en/content.php` расширяем существующую секцию:

```php
'blocks' => [
    // existing: hero, products, text, articles
    'brand_story'  => ['label' => '…'],
    'series_duo'   => ['label' => '…', 'add_item' => '…'],
    'pillars'      => ['label' => '…', 'add_item' => '…'],
    'testimonials' => ['label' => '…', 'add_item' => '…'],

    'fields' => [
        // existing keys remain

        // new common
        'eyebrow'              => '…',
        'lead'                 => '…',
        'signature'            => '…',
        'kicker'               => '…',
        'description'          => '…',
        'surface'              => '…',

        // hero
        'title_top'            => '…',
        'title_bottom'         => '…',
        'floating_label'       => '…',
        'meta'                 => '…',
        'meta_num'             => '…',
        'meta_label'           => '…',
        'secondary_cta_label'  => '…',
        'secondary_cta_url'    => '…',

        // brand_story
        'pillars'              => '…',
        'pillar_label'         => '…',
        'pillar_caption'       => '…',

        // series_duo
        'series_id'            => '…',

        // testimonials
        'quote'                => '…',
        'author'               => '…',
        'city'                 => '…',
        'rating'               => '…',
    ],

    'surface' => [
        'default' => '…',
        'tinted'  => '…',
    ],
],
```

`lang/{uk,en}/site.php` — без изменений (вся видимая копия на главной приходит из контента).

## Тесты (Pest, SQLite :memory:)

**`tests/Feature/Content/HomePageRenderTest.php` (новый):**

- `it renders all 8 block types on the homepage` — сидим главную с одним блоком каждого типа → GET `/uk` → 200; assertSee селекторов:
  - `.hero .copy h1`, `.manifesto`, `.product-slider .track`, `.threepoints .points`, `.collections .grid`, `.pillars`, `.testimonials .track`, `.blog .grid`.
- `it hides blocks with is_visible=false` — выставить `is_visible=false` на одном блоке → его селектор отсутствует.
- `it uses tinted surface on pillars when surface=tinted` — pillars-блок с `surface=tinted` → DOM содержит класс `is-tinted`.
- `it renders hero meta when 3 items are provided` — hero с 3 meta items → 3 `.hero .meta .item`.
- `it falls back to uk when active locale string is empty` — поле `title_top` для `en` пустое, для `uk` заполнено → en-страница использует uk (существующая конвенция `$data['x'][$locale] ?? $data['x']['uk']`).
- `it shows fallback link for series_duo when series_id is null` — без `series_id` карточка серии указывает на `/products`.
- `it limits articles block to 3 cards` — даже если в `items` оказалось 4 (через ручное редактирование JSON), Blade рендерит ровно 3.

**`tests/Feature/Content/Filament/PageBuilderHomeTest.php` (новый):**

- `it lists all 8 block types in the builder` — открыть `EditPage` главной → schema содержит все 8 ключей.
- `it saves a brand_story block with 3 pillars` — Livewire submit → JSON содержит 3 элемента в `pillars`.
- `it enforces pillars items min and max` — попытка сохранить pillars с 2 items или 5 items → ошибка валидации Filament.
- `it enforces hero meta exactly 3` — попытка сохранить hero с 2 meta items → ошибка валидации.
- `it enforces series_duo exactly 2 items` — попытка сохранить series_duo с 1 или 3 items → ошибка валидации.

Существующие `PageBuilderTest`/`PageBuilderResourceTest` (см. предыдущую спеку) — не трогаем; их инварианты (template enum, visibleBlocks, is_homepage unique) остаются.

## Порядок реализации

1. **Enums + переводы.** Расширить `BlockType`. Добавить ключи в `lang/{uk,en}/content.php`.
2. **Filament Block-классы.** 4 новых + правки 4 существующих. Регистрация в `PageForm`.
3. **Blade-компоненты.** `article-card`, `review-card`, `pillar`.
4. **Партиалы.** 4 новых (`brand_story`, `series_duo`, `pillars`, `testimonials`) + переписанные 4 (`hero`, `text`, `products`, `articles`). Перенести fallback-хелпер `$t` на `filled()` (см. § Партиалы) во все, включая существующие.
5. **CSS.** 7 новых файлов + 7 `@import` в `index.css`. Адаптация цвета через токены.
6. **Реордер `DatabaseSeeder`.** Сдвинуть `ArticleSeeder::class` перед `PageSeeder::class` (между `ProductSeeder` и `PageSeeder`).
7. **PageSeeder.** Скопировать 3 ассета в `database/seeders/images/`. Переписать массив `blocks` главной с реальным копирайтом.
8. **Тесты.** 2 новых файла.
9. **Верификация.** `composer test` зелёный. `php artisan migrate:fresh --seed`. `npm run dev` → ручной обзор `/uk` и `/en`. Админка: `/admin/pages` → редактирование главной (8 блоков, LocaleSwitcher, repeater min/max).

## Верификация

- `composer test` — все тесты зелёные.
- `php artisan migrate:fresh --seed` — БД поднимается; главная заполнена 10 секциями (включая блок articles с 3 реальными статьями — после реордера сидеров `ArticleSeeder` выполняется до `PageSeeder`); storage содержит 3 jpg в `pages/blocks/`.
- `/uk` визуально соответствует `pages-home.jsx` (с поправкой на testimonials = slider).
- `/en` — все строки переведены; пустые en-поля fallback'ятся на uk.
- `/admin/pages` → EditPage главной → переключатель template `landing` → Builder показывает 8 типов; LocaleSwitcher переключает uk/en; в каждом repeater'е min/max работает.
- Переключаю `pillars.surface` (Guide) с tinted → default и обратно — фон секции переключается.
- Удаляю первый hero-блок, ставлю другой блок на его место (manifesto) — главная не падает, манифест рендерится первым.

## Что НЕ входит

- Икон-набор (используем нумерацию + diamond/gem декорации). Иконки появятся, когда будет бренд-набор SVG.
- Reference picker для CTA URL (свободная строка, как в существующем `HeroBlock`).
- HTTP/view cache главной — добавим под нагрузкой.
- Авто-режим выборки products/articles по тегам в админке (ручной список через repeater, как в `ProductsBlock`).
- Удаление осиротевших файлов из `storage/app/public/pages/blocks/` при удалении блока (открытый вопрос из предыдущей спеки).
- JSON-LD / OG-разметка для главной — отдельный SEO-таск.
- Миграция блочных картинок на Spatie MediaLibrary с responsive conversions (открытый вопрос предыдущей спеки).
- Дополнительные блоки: `cta_banner`, `image_text`, `video`, `faq`, `quote` — добавим, когда появится дизайн.
- Подключение реального icon-набора — Advantages, как и Guide, рендерится только типографикой + gem decorations.

## Открытые вопросы

- **Series-зависимость PageSeeder.** Если порядок сидеров изменится и `SeriesSeeder` упадёт после `PageSeeder`, `series_duo.items[].series_id` окажется null. Сейчас `PageSeeder` вызывается после `SeriesSeeder` в `DatabaseSeeder` — нужно явно зафиксировать порядок и оставить комментарий в обоих сидерах.
- **Соседние `pillars` блоки с одинаковым `surface`.** Если редактор поставит два `pillars.is-tinted` подряд, они склеются в один длинный tinted-участок. Решений два: (a) добавить `data-prev-surface` heuristic в Blade landing-шаблона, (b) положиться на редактора. Выбираем (b), оставляем (a) на случай реальной жалобы.
- **Testimonials на мобильных.** Slider в эталоне — desktop-only паттерн; на мобильных design показывает 2-col grid. Решим эмпирически после прогона в браузере: либо оставить slider (full-width карточки), либо переключаться на column-stack ниже 720px через CSS.
- **`articles.items` < 3.** Если в БД < 3 статей, сидер положит `is_visible = false`. UX редактора при редактировании этого блока — стандартный (минимум 3 элемента). На рендере (если редактор обойдёт лимит и подсунет 1 статью JSON-ом) — Blade рендерит то, что есть, без падения.

## Revision log

**2026-05-24 — initial.** Спека согласована в brainstorming-сессии 2026-05-24. Решения по подходу (1:1 по эталону), составу блоков (8 типов), полям hero/text/pillars (с дополнительным surface), способу хранения отзывов (repeater без отдельной модели) и порядку фонов задокументированы выше.

**2026-05-24 — post-review revision (5 comments addressed):**

- **P1 — `??` fallback пропускает пустую строку.** Filament `TextInput` сохраняет пустое поле как `''`, не `null`, поэтому существующий шаблон `$data[$key][$locale] ?? $data[$key]['uk']` рендерит пустоту на `/en`, когда редактор оставил поле незаполненным. Шаблон `$t` хелпера в § «Партиалы» переписан через `filled()` (= не `null` и не пустая строка) с явным fallback на uk. Тест `it falls back to uk when active locale string is empty` теперь зелёный по дизайну.
- **P1 — `PageSeeder` идёт перед `ArticleSeeder`.** В текущем `DatabaseSeeder::run()` порядок такой, что `Article::limit(3)->pluck('id')` в `PageSeeder` возвращает пустой набор, блок articles пропадает с главной. Добавлен явный шаг в порядок реализации — реордер `ArticleSeeder` перед `PageSeeder` в `DatabaseSeeder`. Цепочка зависимостей: `ProductSeeder → ArticleSeeder → PageSeeder`.
- **P1 — Stale `series_id` валит fatal.** `Series::find($id)->slug` падает с `Attempt to read property "slug" on null`, если редактор удалил Series или указал несуществующий id. Заменили на двухшаговый lookup с проверкой `null` и fallback на `route('products.index')` без параметра `series`.
- **P2 — Битая ссылка на план.** Изначально спека ссылалась на `docs/superpowers/plans/` как на источник полного `blocks` payload, но плана ещё нет. Сформулировано явно: плана пока нет, он будет создан на следующем этапе как `docs/superpowers/plans/2026-05-24-homepage.md`, и именно он будет содержать дословный JSON `blocks` главной.
- **P2 — `str_word_count` не Unicode-safe.** Стандартная PHP-функция матчит только `[A-Za-z\']`, поэтому для украинского контента возвращает 0 → read-time = 0 мин. Заменили на `preg_match_all('/[\\p{L}\\p{N}\']+/u', ...)` с `max(1, ceil(words / 200))` — корректно считает кириллицу и латиницу, минимум 1 мин.
