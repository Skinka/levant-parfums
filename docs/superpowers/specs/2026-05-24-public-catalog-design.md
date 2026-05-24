# Public catalog page (`/products`) — design

## Why

Это первая публичная страница сайта. До сих пор фронт-часть LevantParfums состояла из админки + минимального CMS-каркаса (одна welcome-страница + динамические CMS-страницы). Перед запуском storefront-а нужно зафиксировать:

- как переносить визуальные артефакты из Claude Design в Laravel-проект (CSS, JS, Blade);
- какую базовую обёртку получает любая публичная страница;
- как маппить мокнутые данные дизайна на реальные модели каталога.

Каталог выбран первой публичной страницей потому, что это самостоятельный, замкнутый экран — header/footer и продуктовые карточки нужны на всех будущих страницах, поэтому шаблоны и компоненты, написанные здесь, будут переиспользованы и на главной, и на карточке товара, и на статьях.

## Решения

### 1. CSS — портируем как есть

Дизайн написан на vanilla CSS с CSS-переменными и семантическими классами (`.card`, `.catalog-head`, `.theme-cream`). У проекта уже подключён Tailwind v4 (CSS-first config), но **storefront им не пишем**. Tailwind остаётся доступен для админ-панели и одноразовых нужд.

Причины:
- дизайн опирается на CSS-переменные тем (`--accent`, `--ink`, `--bg`, тёмная/светлая темы переключаются классом на `<body>`) — это естественно ложится на vanilla CSS, в Tailwind пришлось бы держать data-варианты и кастомный плагин;
- классы дизайна семантичны и стабильны, в Blade-разметке короче и читабельнее, чем длинные строки утилит;
- сохраняем pixel-perfect соответствие исходным `styles.css` без слепых конверсий.

Структура:

```
resources/css/
├── app.css                ← @import tailwindcss + @import site/index.css + @theme (шрифты)
└── site/
    ├── index.css          ← @import всех ниже по порядку
    ├── base.css           ← reset, :root motion vars, темы cream/onyx/editorial
    ├── typography.css     ← h1-h4, .eyebrow, .lead, .serif/.sans/.mono
    ├── layout.css         ← .container, section, .diamond-band, .diamond-rule, utilities
    ├── animations.css     ← .page-fade, .intro-veil, .reveal*, reduced-motion
    ├── components/
    │   ├── button.css         ← .btn (+варианты), .lnk
    │   ├── announcement.css   ← marquee
    │   ├── header.css         ← .header, .brand, .nav, .head-right, .lang-*
    │   ├── footer.css
    │   ├── product-card.css   ← .card + .badge + .body
    │   ├── chip.css           ← .chip
    │   ├── pagination.css
    │   └── breadcrumbs.css    ← .crumbs
    └── pages/
        └── catalog.css        ← .catalog-head, .catalog-filters, .product-grid
```

Из 920 строк дизайнерского `styles.css` для этой итерации портируется ~370 (универсальные блоки + shell + каталог + анимации). Остальное (`.hero`, `.manifesto`, `.threepoints`, `.collections`, `.guide`, `.advantages`, `.reviews`, `.blog`, `.newsletter`, `.product-page`, `.pyramid`, `.character`, `.order-form`, `.lightbox`, `.about-*`, `.contacts`, `.articles-grid`) — заберётся на следующих итерациях.

### 2. JS — минимум vanilla + Alpine для интерактивности

Из дизайна берём только два поведения:
- **scroll-reveal** — добавление класса `.in` на элементы `.reveal`/`.reveal-stagger` когда они входят в viewport. Реализуем через `IntersectionObserver` (чище, чем design-овый rAF-цикл; то же поведение).
- **intro-veil** — однократная за сессию заставка-вуаль. `sessionStorage`-флаг + CSS-анимация.

Дизайн-роутер на hash (`#/catalog?...`) **не переносим** — у нас серверный роутинг с query string. Фильтры/сортировка/пагинация работают через классический form submit + ссылки.

Header lang-switcher — Alpine inline (`x-data="{ open: false }"`), без отдельного JS-файла.

```
resources/js/
├── app.js               ← + import './site/reveal.js'; + import './site/intro-veil.js';
└── site/
    ├── reveal.js
    └── intro-veil.js
```

### 3. Shell — `layouts/site.blade.php`

Полная обёртка дизайна, идентичная всем будущим страницам:
- announcement marquee (бесконечная прокрутка двух дублирующихся блоков, CSS keyframes);
- sticky header с brand-знаком, нав-меню и UA/EN dropdown;
- intro-veil (рендерится всегда, JS убирает после 1.5s, sessionStorage пропускает повтор);
- 5-колоночный footer с brand-копией, нав-ссылками, shop-ссылками, help, контактами;
- класс `theme-cream` на `<body>` (другие темы — на будущее).

Существующий CMS-каркас (`pages/layouts/base.blade.php` + 2 шаблона `landing`/`simple`) **переключается** на новый layout: оба шаблона начинают `@extends('layouts.site')`, старый `pages/layouts/base.blade.php` удаляется. CMS-страницы автоматически получают shell.

### 4. Site-компоненты как `<x-site.*>`

```
resources/views/components/site/
├── announcement.blade.php   ← marquee bar
├── header.blade.php         ← brand + nav + lang-switch
├── lang-switch.blade.php    ← Alpine dropdown UA/EN (использует LaravelLocalization::getLocalizedURL)
├── footer.blade.php
├── intro-veil.blade.php
├── diamond-band.blade.php   ← декоративный ромб-узор (используется в footer)
├── product-card.blade.php   ← :product=$p, :locale
└── badge.blade.php          ← :variant=gold|default, :text
```

### 5. Routing и controller

Маршрут добавляется внутрь существующей `LaravelLocalization::setLocale()` группы в `routes/web.php` **перед** catch-all `/{slug}` (иначе catch-all поглотит `/products`):

```php
Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', fn () => abort(404))->name('products.show'); // заглушка
```

Заглушка `products.show` нужна чтобы `route('products.show', $product->slug)` в карточке не падал. Реальная страница карточки — следующая итерация.

`ProductCatalogController@index` принимает query-параметры `series` (`onyx`|`luxury`|null), `sort` (`pop`|`new`|`priceA`|`priceB`, по умолчанию `pop`), `page`. Eager-load: `series`, `perfumeFamily`, `tags`, `media`. Пагинация 8 на страницу, `withQueryString()`.

### 6. Маппинг полей дизайна на `Product` модель

| Поле в дизайне  | Источник                                                      |
|----|----|
| `series` ("onyx"\|"luxury") | `Product->series->slug` (Series-сидер уже создаёт ровно эти два) |
| `name_uk` / `name_en`        | `$product->name` (translatable JSON) |
| `subtitle_uk` / `subtitle_en` | `$product->tagline` (translatable) |
| `family_uk` / `family_en`    | `$product->perfumeFamily->name` (translatable, может быть null) |
| `price`                       | `$product->displayPrice()['amount']` (UAH для uk, EUR для en) |
| `volume`                      | `$product->volume_ml` |
| `img`                         | `$product->getFirstMediaUrl('primary', 'card')` с fallback на CSS-плейсхолдер |
| `new` (bool)                  | `$product->tags->contains('slug', 'new')` (Tag-сидер уже сидит) |
| `best` (bool)                 | `$product->tags->contains('slug', 'bestseller')` |
| `slug`                        | `$product->slug` |

Сортировки:
- `pop` (default) — товары с тегом `bestseller` идут первыми (`EXISTS (…)` подзапрос), внутри по `published_at desc`.
- `new` — товары с тегом `new` первыми, внутри по `published_at desc`.
- `priceA` / `priceB` — `price_uah asc` / `desc`. **Важно**: цена для EN-локали отображается в EUR, но сортируется всегда по `price_uah` — порядок одинаковый, EUR-цены пропорциональны.

### 7. Шрифты

Vite-фонты переключаются с `Instrument Sans` на `Fraunces` (300/400) + `Inter` (400/500/600) через тот же `bunny()` плагин. CSS-переменные `--font-sans` и `--font-serif` добавляются в `@theme` блока `app.css`.

### 8. Переводы

- `lang/{uk,en}/catalogue.php` — расширяется секцией `public` (eyebrow, title, subtitle, total_label, filter_*, sort_*, prev/next, page_of, badge_*, crumb_home, empty).
- `lang/{uk,en}/site.php` — новый файл для shell-строк (announcement marquee, nav, footer columns, brand strapline).

## Что НЕ делаем

- Карточка товара `/products/{slug}` — заглушка-роут только чтобы `route()` не падал.
- Главная (`/`) и прочие маркетинговые страницы.
- Tweaks panel из дизайна — это инструмент Claude Design, не для прода.
- Корзина, wishlist, оформление заказа.
- Sitemap, OG-карточки, расширенное SEO — только `<title>` и `<meta description>`.
- Skill под импорт Claude Design — пользователь явно отложил.

## Verification

- Pest-тесты в `tests/Feature/Public/ProductCatalogTest.php`: 200, фильтр по серии, пагинация, сортировки, empty-state.
- Ручная проверка через `composer dev`: визуально прокликать чипы, сортировку, пагинацию, language switch, intro-veil поведение.
- `./vendor/bin/pint` — формат.
