# Public product page (`/products/{slug}`) — design

## Why

Це друга публічна сторінка storefront-а (перша — каталог, див. `2026-05-24-public-catalog-design.md`). Після каталогу користувач переходить на картку конкретного флакона. Сторінка має:

- візуально жити в одному з двох мудбордів — світла «Cream» для серії Luxury і темна «Onyx» для серії Onyx — і робити це **без хардкоду слагів у коді**;
- виставити повну продуктову інформацію (галерея, ноти, характер) і одразу дати замовити з тієї ж сторінки через існуючу forms-субсистему;
- розрізняти `in_stock` vs `out_of_stock` — у другому випадку CTA + лист стають передзамовленням, а адмін одразу бачить це у своєму інбоксі.

Дизайн-джерело: `docs/superpowers/design-sources/levant-parfums/project/pages-product.jsx` + `styles.css` + чати. Перенесення — як у `2026-05-24-public-catalog-design.md`: vanilla CSS з темами через CSS-змінні, мінімум vanilla-JS, ніякого Tailwind на storefront.

## Decisions

### 1. Тема серії — окреме поле, а не slug

Сьогодні `series.slug` — фактичний дискримінатор скрізь у коді (фільтр у `ProductCatalogController`, лук-апи в `ProductSeeder`, Filament-селекти). Це працює для URL, але **тема не повинна ламатися від ребрендингу серії**. Тому розводимо:

- Slug залишається URL-ідентифікатором (`?series=onyx`, breadcrumbs).
- Нова колонка `series.theme_class` (string NOT NULL, default `'theme-cream'`) — єдине джерело правди про тему.
- Допустимі значення — у новому `config/site.php`:
  ```php
  return [
      'themes' => [
          'theme-cream' => 'Cream (Luxury)',
          'theme-onyx' => 'Onyx (Dark)',
          'theme-editorial' => 'Editorial (Minimal)',
      ],
  ];
  ```
- У Filament `SeriesResource` додається `Select::make('theme_class')->options(config('site.themes'))->required()->default('theme-cream')`.
- `SeriesSeeder` оновлюється: `luxury → theme-cream`, `onyx → theme-onyx`. Існуючий метод `firstOrCreate` — додаємо ключі.
- `layouts/site.blade.php` приймає `$theme` із дефолтом `'theme-cream'`. **Конкретна правка:** рядок `<body class="theme-cream">` замінюється на `<body class="{{ $theme ?? 'theme-cream' }}">`. Решта існуючих сторінок (`/`, `/products`, CMS) працює без змін — підхоплює дефолт, бо `$theme` не передано.
- У `ProductCatalogController@show`: `$theme = $product->series?->theme_class ?? 'theme-cream'`.

**Жодного `match($slug)` у коді.** Нова серія «Editorial» — адмін у Filament підбирає тему, код не чіпаємо.

### 2. Чотири нові поля на `products`

Дизайн опирається на дані, яких на моделі нема. Додаємо їх однією міграцією `alter_products_add_character_block`:

| Колонка             | Тип                     | Translatable | UI                              |
|---|---|---|---|
| `character`         | `json` nullable         | так           | TextInput, max 160              |
| `why`               | `json` nullable         | так           | Textarea, rows 4                |
| `sillage_score`     | `unsignedTinyInteger` nullable | ні   | Select 1..5 (`1 = skin … 5 = heavy`) |
| `longevity_hours`   | `unsignedTinyInteger` nullable | ні   | Select 2/4/6/8/10/12             |

На `Product` моделі: розширити `$fillable`, додати `character` та `why` у масив `$translatable`, ніяких касткастів для `tinyInteger` (PHP сам читає як int).

У `ProductForm` (Filament Schema) — нова секція «Character & strength» з цими 4 полями. Лейбли + опції — через `__('catalogue.fields.*')`.

Для існуючих 32 сидованих товарів усі чотири поля лишаються NULL. Сидер ставить приклади тільки для перших 4 (2 Luxury + 2 Onyx), щоб одразу побачити сторінку «у повному вигляді» на демо-даних. Коли поле NULL — відповідний блок на сторінці просто не рендериться.

### 3. Order-form: розширюємо існуючу forms-субсистему

`OrderFormType` + `OrderForm` (Livewire) уже існують, але мають заглушковий набір полів (name/phone/email/qty/note) і ніде не змонтовані. Перебудовуємо схему під дизайн:

| Поле          | Правила                          | Джерело в дизайні             |
|---|---|---|
| `name`        | required, string, max 120        | full-row input                |
| `phone`       | required, string, max 40         | half-row, type=tel            |
| `email`       | required, email:rfc, max 255     | half-row, type=email          |
| `city`        | required, string, max 120        | half-row, **нове**            |
| `np_office`   | required, string, max 80         | half-row, **нове** (Нова Пошта) |
| `qty`         | required, integer, 1..5          | **переміщено в summary** як `−/+` степпер біля суми |
| `comment`     | nullable, string, max 1000       | full-row textarea (перейменування `note` → `comment`) |

`form_submissions.data` — JSON, тож рестриктивної міграції під ці поля нема: міняємо лише `OrderFormType::rules()`/`::attributes()`, публічні props у `OrderForm`, blade-в'юху, переклади у `forms.fields.*`. Існуючі тести (`tests/Feature/Forms/OrderFormTest.php`, якщо є) оновлюємо.

`OrderForm::increment()` / `::decrement()` — публічні Livewire-екшени з клампом 1..5. `subtotal` — computed-геттер `qty * $subject->displayPrice()['amount']`, відображається у summary.

### 4. Передзамовлення — за `subject->in_stock`

Семантика: якщо адмін зняв галочку `in_stock` у Filament — товар можна тільки передзамовити. Користувач все одно сабмітить ту саму форму, але і CTA, і листи, і Filament-інбокс розрізняють замовлення vs передзамовлення.

Реалізація — без подвоєння типів форм:

1. **Знімок прапорця при сабміті.** Розширюємо контракт `App\Forms\Types\FormType` мінімальним хуком:
   ```php
   public function metadata(?Model $subject): array { return []; }
   ```
   `FormComponent::submit()` мерджить результат у `$data` перед `FormSubmission::create()`:
   ```php
   $data = array_merge($data, $type->metadata($this->subject));
   ```
   `OrderFormType::metadata($subject)` повертає `['is_preorder' => $subject ? ! $subject->in_stock : false]`.

   Перевага знімка над читанням `subject->in_stock` пізніше: статус товару може змінитися після сабміту, але «це було передзамовлення на момент натискання» — назавжди істина.

2. **CTA на product page.**
   ```blade
   @if($product->in_stock)
       <a class="btn" href="#order">{{ __('catalogue.public.product.order_cta') }}</a>
   @else
       <a class="btn btn-secondary" href="#order">{{ __('catalogue.public.product.preorder_cta') }}</a>
   @endif
   ```

3. **Заголовок форми + thank-you state** читають `! $product->in_stock` (на момент рендеру) та переключають рядки: `forms.order.title.order` / `…title.preorder`, `forms.order.thanks.order` / `…thanks.preorder`. Номер однаковий — `LV-{str_pad($submission->id, 4, '0', STR_PAD_LEFT)}` (фіксований префікс, бо адресат у мейлі бачить тип словесно).

4. **Mailables.** Жодних нових класів. `OrderAdminMail::envelope()` і `OrderClientMail::envelope()` зчитують `$this->submission->data['is_preorder'] ?? false` і повертають різний subject. Markdown-шаблони (`resources/views/emails/forms/order-{admin,client}.blade.php`) переключають заголовок і додають окремий блок-попередження для передзамовлень: «Товар наразі недоступний; ми звʼяжемося з вами щодо термінів».

5. **Filament інбокс.** `FormSubmissionResource` Table — нова колонка «Type/Pre-order» через `TextColumn::make('data.is_preorder')->badge()->formatStateUsing(fn ($state) => $state ? 'PRE-ORDER' : null)`. Якщо не передзамовлення — клітинка порожня (без шуму).

### 5. Сторінка: контролер, в'юха, компоненти

**Маршрут.** Замінюємо stub у `routes/web.php` (усередині localized-group):
```php
Route::get('/products/{product:slug}', [ProductCatalogController::class, 'show'])
    ->where('product', '[A-Za-z0-9\-_]+')
    ->name('products.show');
```
Route-model binding по `slug`. 404 на `!$product->is_published` — у методі контролера, бо binding не знає про прапорець.

**`ProductCatalogController@show(Product $product)`** робить:
- `abort_unless($product->is_published, 404)`
- `$product->load(['series', 'perfumeFamily', 'concentration', 'notes', 'tags', 'occasions', 'media'])`
- `$related` — спочатку до 6 опублікованих з тієї ж серії (`series_id`, `id != $product->id`); якщо менше 4 — добиваємо з інших серій до 6
- `$theme = $product->series?->theme_class ?? 'theme-cream'`
- `return view('products.show', compact('product', 'related', 'theme'))`

**`resources/views/products/show.blade.php`** структура:
```
@extends('layouts.site', ['theme' => $theme])
@section('title', $product->name . ' · LEVANT Parfums')
@section('description', $product->tagline ?: Str::limit(strip_tags($product->description ?? ''), 160))

@section('content')
  <div class="product-page">
    <div class="container">
      <x-site.breadcrumbs :items="[
          ['href' => route('home'), 'label' => __('catalogue.public.crumb_home')],
          ['href' => route('products.index'), 'label' => __('catalogue.public.title')],
          ['href' => route('products.index', ['series' => $product->series->slug]),
           'label' => $product->series->name],
          ['href' => null, 'label' => $product->name],
      ]"/>
      <div class="top">
        <x-site.product-gallery :product="$product"/>
        <x-site.product-info :product="$product"/>
      </div>
      @if($product->notes->isNotEmpty())
        <x-site.product-pyramid :product="$product"/>
      @endif
      @if($product->sillage_score || $product->longevity_hours)
        <x-site.product-character :product="$product"/>
      @endif
      <section id="order">
        <livewire:order-form :subject="$product"/>
      </section>
    </div>
    <x-site.product-slider :products="$related"/>  {{-- full-bleed --}}
  </div>
@endsection
```

**Нові Blade-компоненти** під `resources/views/components/site/`:

- `breadcrumbs.blade.php` — універсальний, `:items` = масив `['href' => ?string, 'label' => string]`. Виносимо inline-крихту з каталога теж сюди.
- `product-gallery.blade.php` — thumbs стрічка + велике main img + zoom-hint. Джерело: `getMedia('gallery')` → fallback на `getFirstMedia('primary')`. **Main `<img>` обгорнутий у `<button data-lightbox-trigger data-lightbox-images='[...]' data-lightbox-index="0" type="button">`** — саме `data-lightbox-trigger` слухає JS-модуль (див. §6). Thumbs міняють activeindex без відкриття lightbox (просто свопають main image), щоб не плодити переходів.
- `product-info.blade.php` — series eyebrow («— Luxury Series», бере `$product->series->name` із translatable), `<h1 class="display-italic">` з `name`, `.subtitle` з `tagline`, `.character-line` (`character` + ` · ` + `optional($product->occasions->first())->name`), `.badges` (`new` / `bestseller` теги), `.price-row` (`displayPrice()` + `volume_ml ml · eau de parfum`), `<p class="desc">{!! $product->description !!}</p>` (description — translatable, plain text), why-block якщо `why`, `.specs` (SKU/Volume/Family/Concentration/Composed/Series; `composed` — статичний рядок з перекладів), CTA (з логікою з §4).
- `product-pyramid.blade.php` — три рівні: `$product->notesByLevel(NoteLevel::Top)`, `…::Heart`, `…::Base` (саме `Heart`, як у існуючому enum-і `App\Enums\NoteLevel`). Рівень з порожньою колекцією пропускаємо. Хедер блоку (eyebrow + h2 + опис) — статичний з перекладів.
- `product-character.blade.php` — два bar-row для `sillage_score` (1..5) і `longevity_hours` (1..12). Ширина філа = `value / max * 100%`, словесна оцінка через `__("catalogue.public.product.character.sillage_words.{$score}")` — **ключі словника 1..5 (без off-by-one), читаємо за `$score` напряму.** Якщо `sillage_score` NULL — ховаємо тільки рядок sillage, не весь блок (так само для longevity). Якщо обидва NULL — компонент рендерить порожньо; це покривається `@if` обгорткою в `show.blade.php`.
- `product-slider.blade.php` — горизонтальний CSS scroll-snap grid із `<x-site.product-card>`. Без JS-слайдера у цій ітерації. Заголовок-eyebrow + тайтл + посилання «Усі парфуми» вгорі.

### 6. CSS + JS

**CSS** (нові файли під `resources/css/site/`):

```
components/
  lightbox.css           ← fixed overlay, prev/next/close, counter
  pyramid.css            ← 2-col (text + levels), chip-notes
  character-bars.css     ← bar-row, fill, ticks
  order-form.css         ← 2-col layout, qty-stepper, thanks
  product-slider.css     ← scroll-snap-x
  qty-stepper.css        ← −/+ кнопки, value
pages/
  product.css            ← .product-page, .top grid, .gallery, .info, .specs, .subtitle, .character-line, .price-row, .display-italic, .why-block
```

Всі нові файли імпортуються в `resources/css/site/index.css` у порядку: спочатку компоненти, потім сторінка. Жодних правок до існуючих `base.css`/`typography.css`/`layout.css` не потрібно (теми вже є).

**JS** — один новий модуль `resources/js/site/lightbox.js`:
- Делегований клік на `[data-lightbox-trigger]`.
- Читає `data-lightbox-images` (JSON) і `data-lightbox-index`.
- Створює fixed-overlay, біндить `Esc` / `←` / `→` / click-outside, ремувається при close.
- Жодних залежностей, чистий vanilla.

Імпорт у `resources/js/app.js`: `import './site/lightbox.js';`.

### 7. Переклади

`lang/{uk,en}/catalogue.php` — додати гілку `public.product`:

- `crumb_catalog`
- `pyramid.{title, top, heart, base, subtitle}` (ключ `heart`, не `mid`, щоб збігтися з `NoteLevel::Heart->value` та існуючим `catalogue.product.fields.notes_heart`)
- `character.{sillage_label, longevity_label, sillage_words.1..5, longevity_word_h}` (ключі `sillage_words` — `1` до `5` включно, читаються в коді як `sillage_words.{$score}`)
- `specs.{sku, volume, family, concentration, composed, composed_value, series}` (`composed_value` = `«Іспанія / ES»` для uk, `«Spain / ES»` для en)
- `why_label`
- `order_cta`, `preorder_cta`
- `related.{title, subtitle, all_label}`
- `badges.{new, best}`
- `series_eyebrow.{luxury, onyx}` (формат «— Luxury Series»)

`lang/{uk,en}/forms.php` — оновити:
- `fields.city`, `fields.np_office`, `fields.comment` — додати
- `fields.qty_short` — `«шт»` / `«pcs»` для степпера
- `fields.note` — видалити
- Нова гілка `order.{title.order, title.preorder, thanks.order, thanks.preorder, subjects.order_admin, subjects.preorder_admin, subjects.order_client, subjects.preorder_client, preorder_notice}`

`lang/{uk,en}/catalogue.php` — нові ключі `fields.character`, `fields.why`, `fields.sillage_score`, `fields.longevity_hours`, `fields.theme_class` для Filament-форм.

## Verification

**Pest-тести.** Усі під `tests/Feature/Public/ProductShowTest.php` (новий файл):

- 200 для опублікованого Luxury-продукту; render містить `class="theme-cream"` на body
- 200 для опублікованого Onyx-продукту; render містить `class="theme-onyx"`
- 404 для `is_published = false`
- 404 для неіснуючого slug
- Опублікований продукт із серією без `theme_class` (захист на випадок дірки даних) → fallback `theme-cream`
- Render містить: name, tagline, description; до 6 related (за серією, fallback на крос-серію коли менше 4)
- Livewire-форма змонтована з правильним subject. Після Blade-рендеру буквальної директиви `<livewire:order-form` у HTML вже не буде — Livewire замінює її на `<div wire:id="…">`. Тест перевіряє через `$response->assertSeeLivewire(\App\Forms\Livewire\OrderForm::class)` (метод TestResponse-макрос від Livewire), або як страхувальник — `assertSee` на унікальний текст з форми (label «Місто», `name="city"`).
- `sillage_score = null && longevity_hours = null` → секція `.character` відсутня
- `notes` порожні → пірамідa відсутня
- `why` NULL → why-block відсутній
- `in_stock = true` → CTA з текстом order, `<a class="btn"`, без `btn-secondary`
- `in_stock = false` → CTA з текстом preorder, `.btn-secondary`

Оновлення `tests/Feature/Forms/OrderFormTest.php` (або новий, якщо нема):

- mount без subject (або з non-Product) → `FormSubjectException`
- submit з усіма обовʼязковими (`name, phone, email, city, np_office, qty=1, comment`) на in-stock товарі → `form_submissions` рядок з `subject_id = product.id`, `data` містить нові ключі та `data.is_preorder = false`
- submit на `in_stock = false` товарі → `data.is_preorder = true`
- `qty = 0` → validation error; `qty = 6` → validation error
- success-стан показує `LV-{padded id}`
- honeypot заповнений → submission не створюється (cover by existing pattern)
- `Mail::fake()`: admin-mail відправлений, subject містить word for «замовлення» при `is_preorder=false`; при `is_preorder=true` — для «передзамовлення»
- client-mail відправлений на введений `email`, з відповідним subject

Оновлення `SeriesSeederTest.php` (якщо є) або новий — `theme_class` виставлений для seed-серій.

**Ручна QA** після `composer dev`:

1. `/uk/products/luxury-1` → cream-тема, повна сторінка
2. `/uk/products/onyx-1` → onyx-тема, footer/header автоматично у темних кольорах
3. `/en/products/luxury-1` → ціна в EUR, текст англійською, тема та сама
4. Перемикач мови UA/EN зберігає slug + поточну тему
5. Click thumbnail → активний міняється; click main image → lightbox; `Esc`/стрілки/click-out працюють
6. На in-stock товарі CTA = «Замовити», submit → thank-you з `LV-0001`, admin-mail subject «Нове замовлення»
7. На out-of-stock товарі (зняти галочку в Filament на `luxury-2`) CTA = «Передзамовити», submit → thank-you (preorder-текст), admin-mail subject «Нове передзамовлення», у Filament інбоксі видно бейдж `PRE-ORDER`
8. CSS-ревʼю: `header.css`, `footer.css`, `announcement.css` не мають hardcoded кольорів повз CSS-змінні. Якщо знайду під час реалізації — патчу там же.

**Команди:**
```bash
php artisan migrate                                    # 2 нові міграції
php artisan db:seed --class=Database\\Seeders\\Catalogue\\SeriesSeeder
./vendor/bin/pint
php artisan test --filter='ProductShowTest|OrderFormTest'
composer dev                                           # ручна QA
```

## Що НЕ робимо

- Wishlist / cart / порівняння.
- Реальний JS-слайдер related-блока (поки CSS scroll-snap).
- SEO мікророзмітка JSON-LD (Product schema) — окрема ітерація.
- OG-картинки на льоту.
- Стрілки навігації у related-слайдері — у v2.
- Print-стилі (`Levant Parfums-print.html` з дизайну) — окрема ітерація.
- Перегляд адреси Нової Пошти через зовнішнє API — користувач вводить вручну рядком.
- Окремий FormType «preorder» — використовуємо той самий `order` з прапорцем `is_preorder` у `data`.
