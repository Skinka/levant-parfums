# Contacts Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a CMS-managed Contacts page at `/uk/kontakty` and `/en/contacts`, backed by a new `contact` block and the existing `ContactForm` Livewire component, with a styled form, in-place success state, and nav links from the header and footer.

**Architecture:** A single `Page` row (`template = landing`, seeded idempotently in `PageSeeder`) renders one new `contact` block. The block contains translatable intro copy, optional address/phone/email/hours fields (empty fields hide), a repeater of social links, and embeds `<livewire:contact-form />`. The existing form's bare placeholder Blade view is rewritten to match the design; the underlying `ContactForm` Livewire component, `ContactFormType`, `ContactAdminMail`, and `FormSubmission` flow stay unchanged.

**Tech Stack:** Laravel 13, Filament 5, Livewire, Spatie\Translatable, Pest 4 (SQLite `:memory:`), Tailwind v4 + modular site CSS.

**Reference spec:** `docs/superpowers/specs/2026-05-25-contacts-page-design.md`

---

## File Structure

**New files (created in order):**

- `app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php` — Filament Builder block class for the admin form. Mirrors `BrandStoryBlock.php`.
- `resources/views/pages/blocks/contact.blade.php` — Public-site Blade view for the block. Renders breadcrumbs, intro, info column (with empty-field hiding), socials, and embeds the form.
- `resources/css/site/components/contacts.css` — Page CSS (grid, info rows, form card, fields, success state, mobile).
- `tests/Feature/Content/ContactsPageTest.php` — Pest feature tests covering public rendering, empty-row hiding, hidden-block behavior, form embedding, and header link.

**Modified files:**

- `app/Enums/BlockType.php` — Add `Contact = 'contact'` case.
- `config/content.php` — Add `contacts_slug` map.
- `app/Filament/Resources/Pages/Schemas/PageForm.php` — Register `ContactBlock::make()` in the Builder.
- `database/seeders/Content/PageSeeder.php` — Add `seedContactsPage()` and call it from `run()`.
- `lang/uk/content.php`, `lang/en/content.php` — Admin block labels (`blocks.contact.label`, `blocks.contact.add_social`), field labels (`blocks.fields.address`, `phone`, `phone_href`, `email`, `hours`, `socials`, `social_label`, `social_url`, `form_eyebrow`, `form_title`), and public-side info labels (`blocks.contact.label_address`, `label_phone`, `label_email`, `label_hours`, `label_social`).
- `lang/uk/site.php`, `lang/en/site.php` — `nav.contacts`, `footer.links.contacts_page`.
- `lang/uk/forms.php`, `lang/en/forms.php` — `contact.thanks`, `contact.submit`, `contact.agree`.
- `resources/css/site/index.css` — Add `@import './components/contacts.css'`.
- `resources/views/forms/contact.blade.php` — Rewrite placeholder with design markup + success state. Keep `wire:model="name|email|message|hp"` and `wire:submit="submit"` intact.
- `resources/views/components/site/header.blade.php` — Append `contacts` nav entry to the `$nav` array (exact-equality match).
- `resources/views/components/site/footer.blade.php` — Add Contacts link to the Nav column; prepend "Contacts page" link to the existing Contact column.

---

## Task 1: Add `Contact` to BlockType enum

**Files:**
- Modify: `app/Enums/BlockType.php`

- [ ] **Step 1: Add the enum case**

Open `app/Enums/BlockType.php`. The file currently looks like:

```php
<?php

namespace App\Enums;

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

    public function label(): string
    {
        return trans("content.blocks.{$this->value}.label");
    }
}
```

Add `Contact` after `Articles`:

```php
case Contact = 'contact';
```

Final case list:

```php
case Hero = 'hero';
case AboutHero = 'about_hero';
case Text = 'text';
case Products = 'products';
case BrandStory = 'brand_story';
case SeriesDuo = 'series_duo';
case Pillars = 'pillars';
case Testimonials = 'testimonials';
case Articles = 'articles';
case Contact = 'contact';
```

- [ ] **Step 2: Run a quick sanity test**

Run: `php artisan tinker --execute='echo \App\Enums\BlockType::Contact->value;'`
Expected: prints `contact`.

- [ ] **Step 3: Commit**

```bash
git add app/Enums/BlockType.php
git commit -m "feat(content): add Contact case to BlockType enum"
```

---

## Task 2: Add config + site nav/footer translations

**Files:**
- Modify: `config/content.php`
- Modify: `lang/uk/site.php`
- Modify: `lang/en/site.php`

- [ ] **Step 1: Add `contacts_slug` to content config**

Open `config/content.php`. Currently:

```php
return [
    'reserved_slugs' => [
        'admin', 'api', 'assets', 'storage', 'login', 'register', 'logout',
        'blog', 'articles', 'pages', 'sitemap', 'feed',
        'uk', 'en',
        'home',
    ],

    'help_pages' => [
        'delivery' => ['uk' => 'dostavka-ta-oplata', 'en' => 'delivery-and-payment'],
        'returns' => ['uk' => 'povernennya', 'en' => 'returns'],
        'terms' => ['uk' => 'umovy-vykorystannya', 'en' => 'terms'],
        'privacy' => ['uk' => 'konfidentsiynist', 'en' => 'privacy'],
    ],
];
```

The Philosophy implementation should have added a `philosophy_slug` key already. Add `contacts_slug` immediately after it (or after `help_pages` if `philosophy_slug` isn't there):

```php
    'philosophy_slug' => ['uk' => 'filosofiia', 'en' => 'philosophy'],
    'contacts_slug' => ['uk' => 'kontakty', 'en' => 'contacts'],
];
```

Do **not** add `'kontakty'` or `'contacts'` to `reserved_slugs` — the seeder must be able to create the page, and `Page::booted()` throws if a saved slug appears in that list.

- [ ] **Step 2: Add `nav.contacts` to uk site translations**

Open `lang/uk/site.php`. The `nav` block is around lines 11–17 and currently includes `home`, `catalog`, `philosophy`, `articles`. Append:

```php
    'nav' => [
        'aria' => 'Головне меню',
        'home' => 'Головна',
        'catalog' => 'Каталог',
        'philosophy' => 'Філософія',
        'articles' => 'Статті',
        'contacts' => 'Контакти',
    ],
```

Then locate the `footer.links` array (already contains `new`, `bestsellers`, `delivery`, `returns`, `terms`, `privacy`) and append `contacts_page`:

```php
        'links' => [
            'new' => 'Новинки',
            'bestsellers' => 'Бестселери',
            'delivery' => 'Доставка та оплата',
            'returns' => 'Повернення',
            'terms' => 'Угода користувача',
            'privacy' => 'Конфіденційність',
            'contacts_page' => 'Сторінка контактів',
        ],
```

- [ ] **Step 3: Add `nav.contacts` to en site translations**

Open `lang/en/site.php`. Append `contacts` to `nav`:

```php
    'nav' => [
        'aria' => 'Primary navigation',
        'home' => 'Home',
        'catalog' => 'Catalogue',
        'philosophy' => 'Philosophy',
        'articles' => 'Articles',
        'contacts' => 'Contacts',
    ],
```

Append `contacts_page` to `footer.links`:

```php
        'links' => [
            'new' => 'New',
            'bestsellers' => 'Bestsellers',
            'delivery' => 'Delivery & payment',
            'returns' => 'Returns',
            'terms' => 'Terms',
            'privacy' => 'Privacy',
            'contacts_page' => 'Contacts page',
        ],
```

- [ ] **Step 4: Verify config loads**

Run: `php artisan tinker --execute='echo config("content.contacts_slug.uk");'`
Expected: prints `kontakty`.

- [ ] **Step 5: Commit**

```bash
git add config/content.php lang/uk/site.php lang/en/site.php
git commit -m "feat(content): config + site translations for the Contacts page"
```

---

## Task 3: Add block translations (admin labels + public info labels)

**Files:**
- Modify: `lang/uk/content.php`
- Modify: `lang/en/content.php`

- [ ] **Step 1: Add `blocks.contact` and new fields to uk**

Open `lang/uk/content.php`. In the `blocks` array (after `articles`), add a `contact` sub-array. Currently the file ends with the `articles` entry then a `fields` sub-array. Insert before `fields`:

```php
        'contact' => [
            'label' => 'Контакти',
            'add_social' => 'Додати соцмережу',
            'label_address' => 'Адреса',
            'label_phone' => 'Телефон',
            'label_email' => 'Пошта',
            'label_hours' => 'Години',
            'label_social' => 'Соцмережі',
        ],
```

Then add new keys at the end of the existing `blocks.fields` array (just before its closing `],`):

```php
            // contact
            'address' => 'Адреса',
            'phone' => 'Телефон',
            'phone_href' => 'Телефон для дзвінка',
            'phone_href_hint' => 'Тільки цифри і +. Використовується у tel:-посиланні.',
            'email' => 'Пошта',
            'hours' => 'Години',
            'socials' => 'Соцмережі',
            'social_label' => 'Підпис',
            'social_url' => 'Посилання',
            'form_eyebrow' => 'Підпис форми',
            'form_title' => 'Заголовок форми',
```

- [ ] **Step 2: Mirror in en**

Open `lang/en/content.php`. Add the same structure with English copy:

```php
        'contact' => [
            'label' => 'Contacts',
            'add_social' => 'Add social',
            'label_address' => 'Address',
            'label_phone' => 'Phone',
            'label_email' => 'Mail',
            'label_hours' => 'Hours',
            'label_social' => 'Social',
        ],
```

And append to `blocks.fields`:

```php
            // contact
            'address' => 'Address',
            'phone' => 'Phone',
            'phone_href' => 'Phone (dial target)',
            'phone_href_hint' => 'Digits and + only. Used in the tel: link.',
            'email' => 'Email',
            'hours' => 'Hours',
            'socials' => 'Social links',
            'social_label' => 'Label',
            'social_url' => 'URL',
            'form_eyebrow' => 'Form eyebrow',
            'form_title' => 'Form title',
```

- [ ] **Step 3: Verify the new keys resolve**

Run: `php artisan tinker --execute='echo trans("content.blocks.contact.label");'`
Expected: prints `Контакти` (or `Contacts` depending on default locale).

- [ ] **Step 4: Commit**

```bash
git add lang/uk/content.php lang/en/content.php
git commit -m "feat(content): translations for the contact block"
```

---

## Task 4: Add form-side translations

**Files:**
- Modify: `lang/uk/forms.php`
- Modify: `lang/en/forms.php`

- [ ] **Step 1: Append `contact` block to uk forms**

Open `lang/uk/forms.php`. The file contains `types`, `statuses`, `actions`, `fields`, `errors`, `notifications`, `mail`, `order`. Add a new `contact` section before the closing `];` (after `order`):

```php
    'contact' => [
        'thanks' => 'Дякуємо! Ми отримали ваше повідомлення.',
        'submit' => 'Надіслати',
        'agree' => 'Натискаючи кнопку, я погоджуюсь з політикою конфіденційності',
    ],
```

- [ ] **Step 2: Append `contact` block to en forms**

Open `lang/en/forms.php`. Add the matching section:

```php
    'contact' => [
        'thanks' => 'Thank you. We have received your message.',
        'submit' => 'Send',
        'agree' => 'By clicking, I agree with the privacy policy',
    ],
```

- [ ] **Step 3: Verify**

Run: `php artisan tinker --execute='echo trans("forms.contact.submit");'`
Expected: prints `Надіслати` or `Send`.

- [ ] **Step 4: Commit**

```bash
git add lang/uk/forms.php lang/en/forms.php
git commit -m "feat(forms): translations for the contact form thank-you / submit / agree"
```

---

## Task 5: Create ContactBlock Filament class

**Files:**
- Create: `app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php`

- [ ] **Step 1: Write the class**

Create `app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php`:

```php
<?php

namespace App\Filament\Resources\Pages\Schemas\Blocks;

use App\Filament\Resources\Pages\Schemas\Blocks\Concerns\TranslatableTabs;
use Filament\Forms\Components\Builder\Block;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class ContactBlock
{
    public static function make(): Block
    {
        return Block::make('contact')
            ->label(trans('content.blocks.contact.label'))
            ->icon('heroicon-o-envelope')
            ->schema([
                ...self::commonFields(),

                TranslatableTabs::make('eyebrow'),
                TranslatableTabs::make('title', required: true),
                TranslatableTabs::make('lead', component: Textarea::class),

                TranslatableTabs::make('address', component: Textarea::class),
                TextInput::make('phone')
                    ->label(trans('content.blocks.fields.phone')),
                TextInput::make('phone_href')
                    ->label(trans('content.blocks.fields.phone_href'))
                    ->helperText(trans('content.blocks.fields.phone_href_hint'))
                    ->regex('/^\+?[0-9]+$/'),
                TextInput::make('email')
                    ->label(trans('content.blocks.fields.email'))
                    ->email(),
                TranslatableTabs::make('hours'),

                TranslatableTabs::make('form_eyebrow'),
                TranslatableTabs::make('form_title', required: true),

                Repeater::make('socials')
                    ->label(trans('content.blocks.fields.socials'))
                    ->schema([
                        TextInput::make('label')
                            ->label(trans('content.blocks.fields.social_label'))
                            ->required(),
                        TextInput::make('url')
                            ->label(trans('content.blocks.fields.social_url'))
                            ->url()
                            ->required(),
                    ])
                    ->maxItems(6)
                    ->reorderable()
                    ->addActionLabel(trans('content.blocks.contact.add_social')),
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

Notes:
- `address`, `phone`, `email`, `hours` are optional by design (no `required()`). This is what makes the empty-field-hiding rule on the public side actually reachable from the admin.
- `phone_href` is validated to digits + optional leading `+`. Empty is allowed; the Blade view falls back to `phone` when it's blank.
- The `Repeater::make('socials')` has no `minItems()` — empty is allowed (the public view hides the row).

- [ ] **Step 2: Sanity-check the class loads**

Run: `php artisan tinker --execute='echo get_class(\App\Filament\Resources\Pages\Schemas\Blocks\ContactBlock::make());'`
Expected: prints `Filament\Forms\Components\Builder\Block`.

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php
git commit -m "feat(filament): contact block schema"
```

---

## Task 6: Register ContactBlock in PageForm builder

**Files:**
- Modify: `app/Filament/Resources/Pages/Schemas/PageForm.php`

- [ ] **Step 1: Import + register**

Open `app/Filament/Resources/Pages/Schemas/PageForm.php`. At the top, add the import alongside the other block imports:

```php
use App\Filament\Resources\Pages\Schemas\Blocks\ContactBlock;
```

In the `Builder::make('blocks')->blocks([...])` array (currently lists `HeroBlock`, `AboutHeroBlock`, `TextBlock`, `ProductsBlock`, `BrandStoryBlock`, `SeriesDuoBlock`, `PillarsBlock`, `TestimonialsBlock`, `ArticlesBlock`), append:

```php
    ContactBlock::make(),
```

Resulting `blocks([...])`:

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
    ContactBlock::make(),
])
```

- [ ] **Step 2: Sanity-check the existing admin tests still pass**

Run: `php artisan test --filter=PageBuilderResourceTest`
Expected: PASS (no behavioural change; we only added a block to the list).

- [ ] **Step 3: Commit**

```bash
git add app/Filament/Resources/Pages/Schemas/PageForm.php
git commit -m "feat(filament): register contact block in PageForm builder"
```

---

## Task 7: Create the Contacts page Blade view

**Files:**
- Create: `resources/views/pages/blocks/contact.blade.php`

- [ ] **Step 1: Write the view**

Create `resources/views/pages/blocks/contact.blade.php`:

```blade
@php
    $locale = app()->getLocale();
    $t = function (string $key) use ($data, $locale) {
        $value = $data[$key][$locale] ?? null;
        return filled($value) ? $value : ($data[$key]['uk'] ?? '');
    };

    $eyebrow     = $t('eyebrow');
    $title       = $t('title');
    $lead        = $t('lead');
    $address     = $t('address');
    $hours       = $t('hours');
    $formEyebrow = $t('form_eyebrow');
    $formTitle   = $t('form_title');

    $phone     = $data['phone']      ?? '';
    $phoneHref = filled($data['phone_href'] ?? null) ? $data['phone_href'] : $phone;
    $email     = $data['email']      ?? '';
    $socials   = array_values(array_filter(
        $data['socials'] ?? [],
        fn ($s) => filled($s['label'] ?? null) && filled($s['url'] ?? null),
    ));

    $L = [
        'address' => __('content.blocks.contact.label_address'),
        'phone'   => __('content.blocks.contact.label_phone'),
        'email'   => __('content.blocks.contact.label_email'),
        'hours'   => __('content.blocks.contact.label_hours'),
        'social'  => __('content.blocks.contact.label_social'),
    ];
@endphp

<section class="contacts reveal" @if(!empty($data['anchor'])) id="{{ $data['anchor'] }}" @endif>
    <div class="container">
        <x-site.breadcrumbs :items="[
            ['href' => LaravelLocalization::localizeURL('/'), 'label' => __('site.nav.home')],
            ['label' => $page->title],
        ]"/>

        <div class="section-head">
            <div>
                @if($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
                @if($title)<h1>{{ $title }}</h1>@endif
                @if($lead)<p class="lead">{{ $lead }}</p>@endif
            </div>
        </div>

        <div class="grid">
            <div class="info">
                @if(filled($address))
                    <div class="item">
                        <div class="l">{{ $L['address'] }}</div>
                        <div class="v">{{ $address }}</div>
                    </div>
                @endif

                @if(filled($phone))
                    <div class="item">
                        <div class="l">{{ $L['phone'] }}</div>
                        <div class="v"><a href="tel:{{ $phoneHref }}">{{ $phone }}</a></div>
                    </div>
                @endif

                @if(filled($email))
                    <div class="item">
                        <div class="l">{{ $L['email'] }}</div>
                        <div class="v"><a href="mailto:{{ $email }}">{{ $email }}</a></div>
                    </div>
                @endif

                @if(filled($hours))
                    <div class="item">
                        <div class="l">{{ $L['hours'] }}</div>
                        <div class="v">{{ $hours }}</div>
                    </div>
                @endif

                @if(!empty($socials))
                    <div class="item socials">
                        <div class="l">{{ $L['social'] }}</div>
                        <div class="links">
                            @foreach($socials as $s)
                                <a href="{{ $s['url'] }}" class="lnk lnk-mute"
                                   target="_blank" rel="noopener">{{ $s['label'] }}</a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div class="form-card">
                @if($formEyebrow)<div class="eyebrow">{{ $formEyebrow }}</div>@endif
                @if($formTitle)<h2>{{ $formTitle }}</h2>@endif
                <livewire:contact-form />
            </div>
        </div>
    </div>
</section>
```

- [ ] **Step 2: Commit**

```bash
git add resources/views/pages/blocks/contact.blade.php
git commit -m "feat(content): blade view for the contact block"
```

---

## Task 8: Add Contacts CSS

**Files:**
- Create: `resources/css/site/components/contacts.css`
- Modify: `resources/css/site/index.css`

- [ ] **Step 1: Create the stylesheet**

Create `resources/css/site/components/contacts.css`:

```css
.contacts { padding: 32px 0 120px; }

.contacts .section-head { margin-bottom: 64px; }
.contacts .section-head h1 {
    margin-top: 18px;
    font-style: italic;
    font-family: var(--font-serif);
}
.contacts .section-head .lead {
    margin-top: 24px;
    max-width: 44ch;
    color: var(--ink-soft);
}

.contacts .grid {
    display: grid;
    grid-template-columns: 1fr 1.4fr;
    gap: 80px;
    align-items: start;
}

.contacts .info {
    display: flex;
    flex-direction: column;
    gap: 28px;
}
.contacts .info .item {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--line-soft);
}
.contacts .info .item:last-child { border-bottom: none; }
.contacts .info .l {
    font-size: 11px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--ink-mute);
}
.contacts .info .v {
    font-family: var(--font-serif);
    font-size: 22px;
    color: var(--ink);
}
.contacts .info .v a {
    color: inherit;
    text-decoration: none;
    border-bottom: 1px solid transparent;
    transition: border-color 200ms var(--ease-out);
}
.contacts .info .v a:hover { border-bottom-color: var(--ink); }

.contacts .info .socials .links {
    display: flex;
    gap: 18px;
    margin-top: 8px;
    flex-wrap: wrap;
}

.contacts .form-card {
    padding: 56px;
    background: var(--bg-2);
    border: 1px solid var(--line-soft);
}
.contacts .form-card h2 {
    margin-top: 12px;
    margin-bottom: 32px;
    font-style: italic;
    font-family: var(--font-serif);
}

.contacts .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.contacts .fields .field { display: flex; flex-direction: column; gap: 8px; }
.contacts .fields .field.full { grid-column: 1 / -1; }
.contacts .fields label {
    font-size: 11px;
    letter-spacing: 0.22em;
    text-transform: uppercase;
    color: var(--ink-mute);
}
.contacts .fields input,
.contacts .fields textarea {
    width: 100%;
    padding: 12px 0;
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--line-soft);
    font-family: inherit;
    font-size: 16px;
    color: var(--ink);
    transition: border-color 200ms var(--ease-out);
}
.contacts .fields input:focus,
.contacts .fields textarea:focus {
    outline: none;
    border-bottom-color: var(--ink);
}
.contacts .fields textarea { resize: vertical; min-height: 120px; }

.contacts .fields .actions {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    margin-top: 16px;
}
.contacts .fields .agree {
    font-size: 12px;
    color: var(--ink-mute);
    max-width: 36ch;
    line-height: 1.5;
}

.contacts .field-error {
    font-size: 12px;
    color: var(--danger, #b02a1c);
    margin-top: 4px;
}
.contacts .form-error {
    padding: 12px 16px;
    background: var(--bg);
    border: 1px solid var(--danger, #b02a1c);
    color: var(--danger, #b02a1c);
    font-size: 14px;
    margin-bottom: 8px;
    grid-column: 1 / -1;
}

.contacts .form-success { padding: 40px 0; }
.contacts .form-success .ok {
    width: 56px; height: 56px;
    border: 1px solid var(--accent);
    color: var(--accent);
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    font-size: 24px;
    line-height: 1;
}
.contacts .form-success h3 {
    font-style: italic;
    font-family: var(--font-serif);
}

@media (max-width: 900px) {
    .contacts .grid { grid-template-columns: 1fr; gap: 48px; }
    .contacts .form-card { padding: 32px 24px; }
    .contacts .fields { grid-template-columns: 1fr; gap: 20px; }
    .contacts .fields .actions { flex-direction: column; align-items: flex-start; }
}
```

- [ ] **Step 2: Add the import**

Open `resources/css/site/index.css`. The current file imports a list of component stylesheets in alphabetical-ish order. Add the line in the components block, sorted next to `collections.css` / `breadcrumbs.css`:

```css
@import './components/contacts.css';
```

(Placement: anywhere in the components block — pick a slot that keeps the imports roughly ordered. After `@import './components/collections.css';` is a fine choice.)

- [ ] **Step 3: Verify CSS builds**

Run: `npm run build`
Expected: completes without errors. Look for `contacts.css` content in the bundle output.

- [ ] **Step 4: Commit**

```bash
git add resources/css/site/components/contacts.css resources/css/site/index.css
git commit -m "feat(content): contacts page styles"
```

---

## Task 9: Rewrite the contact form Blade view

**Files:**
- Modify: `resources/views/forms/contact.blade.php`

- [ ] **Step 1: Confirm the existing tests pass before changes**

Run: `php artisan test --filter=ContactFormTest`
Expected: PASS (this is the regression baseline).

- [ ] **Step 2: Replace the placeholder**

Replace the entire content of `resources/views/forms/contact.blade.php` with:

```blade
@if (session('forms.success.contact'))
    <div class="form-success">
        <div class="ok" aria-hidden="true">✓</div>
        <h3>{{ __('forms.contact.thanks') }}</h3>
    </div>
@else
    <form wire:submit="submit" class="fields" novalidate>
        <x-forms.honeypot wire:model="hp" />

        @error('form')
            <div class="form-error" data-testid="form-error">{{ $message }}</div>
        @enderror

        <div class="field">
            <label for="contact-name">{{ __('forms.fields.name') }} *</label>
            <input id="contact-name" type="text" wire:model="name" autocomplete="name" required>
            @error('name') <span class="field-error" data-testid="name-error">{{ $message }}</span> @enderror
        </div>

        <div class="field">
            <label for="contact-email">{{ __('forms.fields.email') }} *</label>
            <input id="contact-email" type="email" wire:model="email" autocomplete="email" required>
            @error('email') <span class="field-error" data-testid="email-error">{{ $message }}</span> @enderror
        </div>

        <div class="field full">
            <label for="contact-message">{{ __('forms.fields.message') }} *</label>
            <textarea id="contact-message" wire:model="message" rows="6" required></textarea>
            @error('message') <span class="field-error" data-testid="message-error">{{ $message }}</span> @enderror
        </div>

        <div class="actions">
            <span class="agree">{{ __('forms.contact.agree') }}</span>
            <button type="submit" class="btn">
                <span>{{ __('forms.contact.submit') }}</span>
                <span class="btn-arrow" aria-hidden="true">→</span>
            </button>
        </div>
    </form>
@endif
```

The mandatory contract (preserved):
- `wire:submit="submit"`
- `wire:model="name"`, `wire:model="email"`, `wire:model="message"`, `wire:model="hp"`
- `<x-forms.honeypot wire:model="hp" />`

Everything else is presentation.

- [ ] **Step 3: Run the existing ContactForm tests — they must still pass**

Run: `php artisan test --filter=ContactFormTest`
Expected: PASS (5 tests). If anything fails, you've broken a `wire:model` or `wire:submit` binding.

- [ ] **Step 4: Commit**

```bash
git add resources/views/forms/contact.blade.php
git commit -m "feat(forms): styled contact form blade with in-place success state"
```

---

## Task 10: Seed the Contacts page in PageSeeder

**Files:**
- Modify: `database/seeders/Content/PageSeeder.php`

- [ ] **Step 1: Add `seedContactsPage()` and `buildContactsBlocks()`**

Open `database/seeders/Content/PageSeeder.php`. At the end of `run()` (after `$this->seedPhilosophyPage();`), append:

```php
        $this->seedContactsPage();
```

Then add the two private methods at the bottom of the class (after `buildPhilosophyBlocks()`):

```php
    private function seedContactsPage(): void
    {
        $slug = config('content.contacts_slug');

        $blocks = $this->buildContactsBlocks();

        $existing = Page::query()->whereJsonContains('slug->uk', $slug['uk'])->first();

        $data = [
            'slug' => $slug,
            'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
            'intro' => ['uk' => '', 'en' => ''],
            'content' => null,
            'seo_title' => [
                'uk' => 'Контакти · Levant Parfums',
                'en' => 'Contacts · Levant Parfums',
            ],
            'seo_description' => [
                'uk' => 'Бутік-студія Levant Parfums у центрі Києва. Адреса, телефон, пошта, години роботи, форма звʼязку.',
                'en' => 'Levant Parfums boutique studio in central Kyiv. Address, phone, mail, opening hours, contact form.',
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

    private function buildContactsBlocks(): array
    {
        return [
            [
                'type' => 'contact',
                'data' => [
                    'is_visible' => true,
                    'eyebrow' => ['uk' => "Зв'язок", 'en' => 'Contact'],
                    'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
                    'lead' => [
                        'uk' => 'Бутік-студія у центрі Києва. Запис на консультацію — за телефоном або через форму.',
                        'en' => 'A boutique studio in central Kyiv. Book a consultation by phone or via the form.',
                    ],
                    'address' => [
                        'uk' => 'Київ, вул. Рейтарська 19, друга арка',
                        'en' => 'Kyiv, Reitarska 19, second arch',
                    ],
                    'phone' => '+38 (097) 412 88 19',
                    'phone_href' => '+380974128819',
                    'email' => 'concierge@levant.parfum',
                    'hours' => [
                        'uk' => 'Пн–Сб · 11:00–20:00',
                        'en' => 'Mon–Sat · 11:00–20:00',
                    ],
                    'socials' => [
                        ['label' => 'Instagram', 'url' => 'https://instagram.com/levant.parfums'],
                        ['label' => 'Telegram', 'url' => 'https://t.me/levantparfums'],
                        ['label' => 'TikTok', 'url' => 'https://tiktok.com/@levant.parfums'],
                    ],
                    'form_eyebrow' => ['uk' => 'Форма', 'en' => 'Form'],
                    'form_title' => ['uk' => 'Напишіть нам', 'en' => 'Write to us'],
                ],
            ],
        ];
    }
```

- [ ] **Step 2: Run the seeder against a fresh DB**

Run: `php artisan migrate:fresh --seed`
Expected: completes successfully, including a `Page` row with `slug.uk = 'kontakty'`.

Verify:

```bash
php artisan tinker --execute='echo \App\Models\Content\Page::query()->whereJsonContains("slug->uk", "kontakty")->value("id");'
```

Expected: prints a non-empty integer.

- [ ] **Step 3: Commit**

```bash
git add database/seeders/Content/PageSeeder.php
git commit -m "feat(content): seed the Contacts page"
```

---

## Task 11: Write the public-page feature tests (TDD: red → green)

**Files:**
- Create: `tests/Feature/Content/ContactsPageTest.php`

- [ ] **Step 1: Write the test file**

Create `tests/Feature/Content/ContactsPageTest.php`. Mirror the helper pattern from `PhilosophyPageTest.php`:

```php
<?php

use App\Enums\PageTemplate;
use App\Forms\Livewire\ContactForm;
use App\Models\Content\Page;
use Mcamara\LaravelLocalization\LaravelLocalization;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

afterEach(function () {
    putenv(LaravelLocalization::ENV_ROUTE_KEY);
});

function makeContactsPage(array $overrides = []): Page
{
    $blocks = $overrides['blocks'] ?? [
        [
            'type' => 'contact',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => "Зв'язок", 'en' => 'Contact'],
                'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
                'lead' => [
                    'uk' => 'Бутік-студія у центрі Києва.',
                    'en' => 'A boutique studio in central Kyiv.',
                ],
                'address' => [
                    'uk' => 'Київ, вул. Рейтарська 19',
                    'en' => 'Kyiv, Reitarska 19, second arch',
                ],
                'phone' => '+38 (097) 412 88 19',
                'phone_href' => '+380974128819',
                'email' => 'concierge@levant.parfum',
                'hours' => [
                    'uk' => 'Пн–Сб · 11:00–20:00',
                    'en' => 'Mon–Sat · 11:00–20:00',
                ],
                'socials' => [
                    ['label' => 'Instagram', 'url' => 'https://instagram.com/x'],
                    ['label' => 'Telegram', 'url' => 'https://t.me/x'],
                ],
                'form_eyebrow' => ['uk' => 'Форма', 'en' => 'Form'],
                'form_title' => ['uk' => 'Напишіть нам', 'en' => 'Write to us'],
            ],
        ],
    ];

    return Page::factory()->create(array_merge([
        'template' => PageTemplate::Landing,
        'is_homepage' => false,
        'is_published' => true,
        'slug' => ['uk' => 'kontakty', 'en' => 'contacts'],
        'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
        'content' => null,
        'blocks' => $blocks,
    ], array_diff_key($overrides, ['blocks' => true])));
}

it('renders the Contacts page at the uk slug', function () {
    makeContactsPage();

    $this->get('/kontakty')
        ->assertOk()
        ->assertSee('Контакти')
        ->assertSee('+38 (097) 412 88 19')
        ->assertSee('concierge@levant.parfum')
        ->assertSee('tel:+380974128819', escape: false)
        ->assertSee('mailto:concierge@levant.parfum', escape: false);
});

it('renders the Contacts page at the en slug', function () {
    refreshApplicationWithLocale('en');
    makeContactsPage();

    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/en/contacts')
        ->assertOk()
        ->assertSee('Contacts')
        ->assertSee('Kyiv, Reitarska 19, second arch')
        ->assertSee('Mon–Sat · 11:00–20:00');
});

it('hides empty info rows', function () {
    makeContactsPage([
        'blocks' => [
            [
                'type' => 'contact',
                'data' => [
                    'is_visible' => true,
                    'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
                    'address' => ['uk' => 'Київ, вул. Рейтарська 19', 'en' => 'Kyiv'],
                    'phone' => '',
                    'phone_href' => '',
                    'email' => 'hello@x.test',
                    'hours' => ['uk' => '', 'en' => ''],
                    'socials' => [],
                    'form_title' => ['uk' => 'Напишіть', 'en' => 'Write'],
                ],
            ],
        ],
    ]);

    $response = $this->get('/kontakty');
    $response->assertOk()
        ->assertSee('Київ, вул. Рейтарська 19')
        ->assertSee('hello@x.test')
        ->assertDontSee('tel:', escape: false)
        ->assertDontSee('Телефон')
        ->assertDontSee('Години')
        ->assertDontSee('Соцмережі');
});

it('falls back to phone as the tel target when phone_href is empty', function () {
    makeContactsPage([
        'blocks' => [
            [
                'type' => 'contact',
                'data' => [
                    'is_visible' => true,
                    'title' => ['uk' => 'Контакти', 'en' => 'Contacts'],
                    'phone' => '+380000000000',
                    'phone_href' => '',
                    'form_title' => ['uk' => 'Напишіть', 'en' => 'Write'],
                ],
            ],
        ],
    ]);

    $this->get('/kontakty')
        ->assertOk()
        ->assertSee('tel:+380000000000', escape: false);
});

it('hides the contact block when is_visible is false', function () {
    makeContactsPage([
        'blocks' => [
            [
                'type' => 'contact',
                'data' => [
                    'is_visible' => false,
                    'title' => ['uk' => 'HIDDEN-CONTACTS-TITLE', 'en' => 'HIDDEN-CONTACTS-TITLE'],
                    'address' => ['uk' => 'HIDDEN-ADDRESS', 'en' => 'HIDDEN-ADDRESS'],
                    'phone' => '+999999999',
                ],
            ],
        ],
    ]);

    $this->get('/kontakty')
        ->assertOk()
        ->assertDontSee('HIDDEN-CONTACTS-TITLE')
        ->assertDontSee('HIDDEN-ADDRESS')
        ->assertDontSee('+999999999');
});

it('embeds the contact form livewire component', function () {
    makeContactsPage();

    $this->get('/kontakty')
        ->assertOk()
        ->assertSeeLivewire(ContactForm::class);
});

it('exposes a Contacts link in the header nav', function () {
    Page::query()->create([
        'is_homepage' => true,
        'is_published' => true,
        'template' => PageTemplate::Landing,
        'slug' => ['uk' => 'home-uk', 'en' => 'home-en'],
        'title' => ['uk' => 'Головна', 'en' => 'Home'],
        'intro' => ['uk' => '', 'en' => ''],
        'content' => null,
        'blocks' => [],
    ]);
    makeContactsPage();

    $expectedUrl = route('page.show', ['slug' => config('content.contacts_slug')['uk']]);

    $this->get('/')
        ->assertOk()
        ->assertSee($expectedUrl, escape: false)
        ->assertSee('Контакти');
});
```

- [ ] **Step 2: Run the new tests — most will fail**

Run: `php artisan test --filter=ContactsPageTest`
Expected:
- `renders the Contacts page at the uk slug` — **PASS** if Tasks 1, 3, 7 done; FAIL otherwise.
- `renders the Contacts page at the en slug` — **PASS** by the same conditions.
- `hides empty info rows` — **PASS** (the Blade guards are already in place from Task 7).
- `falls back to phone as the tel target when phone_href is empty` — **PASS**.
- `hides the contact block when is_visible is false` — **PASS** (relies on existing `Page::visibleBlocks()`).
- `embeds the contact form livewire component` — **PASS** (Livewire registration already in `AppServiceProvider`).
- `exposes a Contacts link in the header nav` — **FAIL** until Task 12 (header nav entry not added yet).

If all the first six pass and only the header-link test fails, you're on track. If others fail, debug the previous tasks before moving on.

- [ ] **Step 3: Commit the tests**

```bash
git add tests/Feature/Content/ContactsPageTest.php
git commit -m "test(content): feature tests for the Contacts page"
```

---

## Task 12: Add Contacts to header nav (green the header-link test)

**Files:**
- Modify: `resources/views/components/site/header.blade.php`

- [ ] **Step 1: Extend `$nav`**

Open `resources/views/components/site/header.blade.php`. The current `@php` block resolves `$philosophySlug` and `$philosophyUrl` and builds a `$nav` array with four entries (home, catalog, philosophy, articles).

Right below the philosophy lines, add:

```php
    $contactsSlug = config('content.contacts_slug')[$locale] ?? config('content.contacts_slug')['uk'];
    $contactsUrl = route('page.show', ['slug' => $contactsSlug]);
```

Then append a fifth `$nav` entry — exact-equality match, like Philosophy:

```php
        ['key' => 'contacts',   'url' => $contactsUrl,                                  'match' => fn ($r) => $r === '/' . $contactsSlug],
```

Final `$nav` array:

```php
    $nav = [
        ['key' => 'home',       'url' => LaravelLocalization::localizeURL('/'),         'match' => fn ($r) => $r === '/' || $r === ''],
        ['key' => 'catalog',    'url' => route('products.index'),                       'match' => fn ($r) => str_starts_with($r, '/products')],
        ['key' => 'philosophy', 'url' => $philosophyUrl,                                'match' => fn ($r) => $r === '/' . $philosophySlug],
        ['key' => 'articles',   'url' => route('articles.index', [], false),            'match' => fn ($r) => str_starts_with($r, '/articles')],
        ['key' => 'contacts',   'url' => $contactsUrl,                                  'match' => fn ($r) => $r === '/' . $contactsSlug],
    ];
```

The existing `@foreach($nav as $item)` block already pulls the label via `__("site.nav.{$item['key']}")`, which resolves to the `nav.contacts` key added in Task 2.

- [ ] **Step 2: Re-run the contacts tests**

Run: `php artisan test --filter=ContactsPageTest`
Expected: all 7 tests PASS.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/site/header.blade.php
git commit -m "feat(nav): header link to the Contacts page"
```

---

## Task 13: Add Contacts to footer

**Files:**
- Modify: `resources/views/components/site/footer.blade.php`

- [ ] **Step 1: Append to the Nav column**

Open `resources/views/components/site/footer.blade.php`. Find the Nav column (the `<div>` with `<h4>{{ __('site.footer.columns.nav') }}</h4>` — currently lists home / catalog / philosophy / articles). Append after the articles `<li>`:

```blade
                    <li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale] ?? 'kontakty']) }}">{{ __('site.nav.contacts') }}</a></li>
```

Final Nav column:

```blade
                <ul>
                    <li><a href="{{ LaravelLocalization::localizeURL('/') }}">{{ __('site.nav.home') }}</a></li>
                    <li><a href="{{ route('products.index') }}">{{ __('site.nav.catalog') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => config('content.philosophy_slug')[$locale] ?? 'filosofiia']) }}">{{ __('site.nav.philosophy') }}</a></li>
                    <li><a href="{{ route('articles.index', [], false) }}">{{ __('site.nav.articles') }}</a></li>
                    <li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale] ?? 'kontakty']) }}">{{ __('site.nav.contacts') }}</a></li>
                </ul>
```

- [ ] **Step 2: Prepend to the Contact column**

Find the Contact column (the `<div>` with `<h4>{{ __('site.footer.columns.contact') }}</h4>` — currently lists phone, email, Instagram, Telegram). Insert a new `<li>` as the **first** item:

```blade
                    <li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale] ?? 'kontakty']) }}">{{ __('site.footer.links.contacts_page') }}</a></li>
```

Final Contact column:

```blade
                <ul>
                    <li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale] ?? 'kontakty']) }}">{{ __('site.footer.links.contacts_page') }}</a></li>
                    <li><a href="tel:+380974128819">+38 (097) 412 88 19</a></li>
                    <li><a href="mailto:concierge@levant.parfum">concierge@levant.parfum</a></li>
                    <li><a href="#" rel="noopener">Instagram</a></li>
                    <li><a href="#" rel="noopener">Telegram</a></li>
                </ul>
```

The existing phone/email/Instagram/Telegram convenience links remain unchanged — duplication with the contact block is accepted per the spec's Risks section.

- [ ] **Step 3: Commit**

```bash
git add resources/views/components/site/footer.blade.php
git commit -m "feat(nav): footer links to the Contacts page"
```

---

## Task 14: Full verification

**Files:** none (verification only)

- [ ] **Step 1: Run the full test suite**

Run: `composer test`
Expected: ALL tests PASS, including:
- `ContactsPageTest` (7 cases)
- `ContactFormTest`, `FormTypeTest`, `FormRateLimiterTest` (no regressions)
- `PhilosophyPageTest` (no regressions)
- `PageBuilderResourceTest`, `PageResourceTest` (no regressions)

If anything fails, stop and debug before continuing.

- [ ] **Step 2: Run Pint**

Run: `./vendor/bin/pint`
Expected: "All files passed" or it auto-fixes the new files. If it changes files, commit the fixes separately.

- [ ] **Step 3: Manual smoke test against a running app**

Run (in two terminals or via `composer dev`):

```bash
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Open in browser:
- `http://localhost:8000/uk/kontakty` — should render the page with breadcrumbs, contact info, social links, and the styled form.
- `http://localhost:8000/en/contacts` — same in English.
- `http://localhost:8000/uk` — header should show "Контакти" link; footer Nav and Contact columns should both link to the page.

Submit the form on the page (any valid name/email/message). The form should swap in place to the thank-you card without a full reload.

Verify in the admin (`http://localhost:8000/admin`, log in as `admin@levantparfums.test` / `password`):
- The Contacts page is in the Pages list.
- Editing it shows the Builder with a `Contacts` block, and all fields are editable (translatable tabs on text fields, scalar inputs on phone/email, repeater for socials).

- [ ] **Step 4: Final commit (optional, only if Pint or anything else changed files)**

```bash
git status
# If Pint changed something:
git add -p
git commit -m "style: pint formatting"
```

---

## Acceptance checklist

When this plan is fully executed, all of the following must hold:

- [ ] `php artisan migrate:fresh --seed` creates a Page at `/uk/kontakty` and `/en/contacts`.
- [ ] The page renders breadcrumbs, eyebrow/title/lead, a 2-column grid (info + form), and the styled `<livewire:contact-form />`.
- [ ] Clearing any of `address`, `phone`, `email`, `hours` in admin causes that row to disappear on the public page (covered by `it('hides empty info rows')`).
- [ ] An empty `phone_href` falls back to the displayed `phone` for the `tel:` link.
- [ ] Hiding the block via `is_visible = false` keeps the page reachable but renders no contact content.
- [ ] Submitting the form creates a `FormSubmission` of type `contact`, queues `ContactAdminMail`, and swaps the form for the thank-you card without a full reload.
- [ ] Header has a Contacts link (last entry, exact-equality match). Footer Nav and Contact columns both link to the page.
- [ ] `composer test` is green. `./vendor/bin/pint` reports clean.
