# Contacts page — design spec

**Date:** 2026-05-25
**Status:** draft
**Source:** `docs/superpowers/design-sources/levant-parfums/project/pages-other.jsx` (ContactsPage, lines 124–192), `data.js` (`contacts_*` keys), `styles.css` (`.contacts`, lines 833–840).

## Why

The handed-off design ships a dedicated Contacts page that the live site lacks. Today the only way to reach the brand is the footer (hardcoded phone, email, Instagram, Telegram). The page needs to:

1. Give the brand a canonical contact surface — address, phone, email, opening hours, social links, contact form, all in one place.
2. Replace the bare placeholder at `resources/views/forms/contact.blade.php` with a styled form that matches the rest of the site.
3. Stay CMS-editable so the admin can change copy and contact info from Filament without code changes.

## Out of scope

- **Map embed.** Source has no map; deferring until the boutique location is finalized. Avoids 3rd-party iframe / GDPR concerns.
- **Per-locale phone or email values.** Phone & email are scalars, identical across locales. Only address and hours are translatable.
- **Refactoring the footer Contact column to read from block data.** The footer keeps its hardcoded phone/email/Instagram/Telegram links for now — duplication is accepted and noted under Risks.
- **Markdown content.** Page uses typed blocks (`landing` template), not free-form markdown.
- **New ContactForm Livewire component.** The existing component & tests stand; only its blade view is rewritten.

## Architecture

### Page record

A single `Page` row, created/updated idempotently in `database/seeders/Content/PageSeeder.php`:

```
template       = PageTemplate::Landing
is_homepage    = false
is_published   = true
slug           = { uk: 'kontakty', en: 'contacts' }
title          = { uk: 'Контакти', en: 'Contacts' }
intro          = { uk: '', en: '' }
content        = null
seo_title      = { uk: 'Контакти · Levant Parfums',
                   en: 'Contacts · Levant Parfums' }
seo_description = { uk: 'Бутік-студія Levant Parfums у центрі Києва. Адреса, телефон, пошта, години роботи, форма звʼязку.',
                    en: 'Levant Parfums boutique studio in central Kyiv. Address, phone, mail, opening hours, contact form.' }
blocks         = [contact]   // single block, defined below
```

The page is resolved by the existing catch-all `/{slug}` → `PageController@show` (`whereJsonContains("slug->{$locale}", $slug)`). No new route.

### Slug uniqueness — leave `reserved_slugs` alone

`Page::booted()` throws `DomainException` on save if a translated slug appears in `config('content.reserved_slugs')`. Adding `kontakty` / `contacts` to that list would block `PageSeeder` from creating its own row — the same trap the Philosophy and help pages avoid.

Uniqueness is already enforced by the **functional unique JSON indexes** on `pages.slug->uk` and `slug->en` from migration `2026_05_23_055707_create_pages_table.php` (separate MySQL `ALTER TABLE ... ADD UNIQUE` and SQLite `CREATE UNIQUE INDEX` branches). An admin trying to create another page with `kontakty` or `contacts` hits the DB constraint.

### Reusable URL config

Add a slug map next to the existing `help_pages`:

```php
// config/content.php
'contacts_slug' => ['uk' => 'kontakty', 'en' => 'contacts'],
```

Header and footer resolve their URLs via `route('page.show', ['slug' => config('content.contacts_slug')[$locale]])` — same pattern as `help_pages` in `footer.blade.php` and the planned `philosophy_slug`.

## The `contact` block

### BlockType enum

Add case `Contact = 'contact'` to `App\Enums\BlockType`. The enum file already has the philosophy-driven `AboutHero` addition; `Contact` slots in alongside the rest.

### Block data shape

One row in `Page.blocks` JSON:

```php
[
    'type' => 'contact',
    'data' => [
        'is_visible' => true,
        'anchor'     => null,

        'eyebrow'    => ['uk' => "Зв'язок",  'en' => 'Contact'],
        'title'      => ['uk' => 'Контакти', 'en' => 'Contacts'],
        'lead'       => [
            'uk' => 'Бутік-студія у центрі Києва. Запис на консультацію — за телефоном або через форму.',
            'en' => 'A boutique studio in central Kyiv. Book a consultation by phone or via the form.',
        ],

        // Info column — scalars are global; address/hours are translatable
        'address'    => ['uk' => 'Київ, вул. Рейтарська 19, друга арка',
                         'en' => 'Kyiv, Reitarska 19, second arch'],
        'phone'      => '+38 (097) 412 88 19',
        'phone_href' => '+380974128819',                    // tel: target, digits/+ only
        'email'      => 'concierge@levant.parfum',
        'hours'      => ['uk' => 'Пн–Сб · 11:00–20:00',
                         'en' => 'Mon–Sat · 11:00–20:00'],

        // Optional social rows — admin-editable
        'socials' => [
            ['label' => 'Instagram', 'url' => 'https://instagram.com/...'],
            ['label' => 'Telegram',  'url' => 'https://t.me/...'],
            ['label' => 'TikTok',    'url' => 'https://tiktok.com/@...'],
        ],

        // Form column eyebrow + headline
        'form_eyebrow'   => ['uk' => 'Форма',          'en' => 'Form'],
        'form_title'     => ['uk' => 'Напишіть нам',   'en' => 'Write to us'],
    ],
],
```

**Why `phone_href` is separate.** The displayed phone has spaces and brackets for readability; `tel:` URIs need digits-only with an optional leading `+`. Seeder fills both; admin form has both fields side-by-side with a hint.

### Empty-field rule

The Blade view (below) guards every info row with `filled()` / `!empty()`. Rows whose data is empty are **not rendered at all** — no empty `<div class="v"></div>` placeholders. If the admin clears `phone`, the entire phone row, label included, disappears. If `socials` is empty, the "Соцмережі" row is hidden. This is the canonical behaviour for the block.

### Filament admin block

New class `app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php`, mirroring `BrandStoryBlock.php`:

- `commonFields()` — `is_visible` toggle + `anchor`.
- `TranslatableTabs::make('eyebrow')`.
- `TranslatableTabs::make('title', required: true)`.
- `TranslatableTabs::make('lead', component: Textarea::class)`.
- `TranslatableTabs::make('address', component: Textarea::class)`.
- `TextInput::make('phone')`.
- `TextInput::make('phone_href')->regex('/^\+?[0-9]+$/')` with a helper hint that explains the `tel:`-target format.
- `TextInput::make('email')->email()`.
- `TranslatableTabs::make('hours')`.
- `TranslatableTabs::make('form_eyebrow')`.
- `TranslatableTabs::make('form_title', required: true)`.
- `Repeater::make('socials')` — 0..6 items, schema `TextInput::make('label')->required()` + `TextInput::make('url')->url()->required()`, reorderable, with `addActionLabel(trans('content.blocks.contact.add_social'))`.

Only `title` and `form_title` are required at the admin level. Every info field (`address`, `phone`, `email`, `hours`) is optional so the admin can clear any of them and have the corresponding row disappear on the public page — that is the contract promised by the empty-field rule below and the matching tests. The seeder still ships with all four populated; nullability is for the edit path, not the initial state.

Register the new block in `PageForm` so it shows up in the page Builder.

### Translations for the admin block

`lang/{uk,en}/content.php`:

```
blocks.contact.label        // uk: "Контакти"           en: "Contacts"
blocks.contact.add_social   // uk: "Додати соцмережу"   en: "Add social"

blocks.fields.address       // uk: "Адреса"             en: "Address"
blocks.fields.phone         // uk: "Телефон"            en: "Phone"
blocks.fields.phone_href    // uk: "Телефон для дзвінка" en: "Phone (dial target)"
blocks.fields.email         // uk: "Пошта"              en: "Email"
blocks.fields.hours         // uk: "Години"             en: "Hours"
blocks.fields.socials       // uk: "Соцмережі"          en: "Social links"
blocks.fields.social_label  // uk: "Підпис"             en: "Label"
blocks.fields.social_url    // uk: "Посилання"          en: "URL"
blocks.fields.form_eyebrow  // uk: "Підпис форми"       en: "Form eyebrow"
blocks.fields.form_title    // uk: "Заголовок форми"    en: "Form title"
```

Reuse the existing `blocks.fields.is_visible`, `blocks.fields.anchor`, `blocks.fields.eyebrow`, `blocks.fields.title`, `blocks.fields.lead` translations (also used by the Philosophy `about_hero` block).

### Public-facing translations for info labels

The labels above the values (`Address` / `Адреса`, `Phone` / `Телефон`, …) are public-site copy — also stored under `content.blocks.contact.label_*` so the Blade view can read them via `__()`:

```
blocks.contact.label_address  // uk: "Адреса"     en: "Address"
blocks.contact.label_phone    // uk: "Телефон"    en: "Phone"
blocks.contact.label_email    // uk: "Пошта"      en: "Email"
blocks.contact.label_hours    // uk: "Години"     en: "Hours"
blocks.contact.label_social   // uk: "Соцмережі"  en: "Social"
```

These are intentionally separate keys from the admin `blocks.fields.*` so the two surfaces can diverge without breaking each other (e.g. admin can show "Phone (dial target)" while the public label stays "Phone").

## Blade view

New file `resources/views/pages/blocks/contact.blade.php`. Follows the locale-resolution pattern from `brand_story.blade.php`; structure maps one-to-one to `pages-other.jsx:124–192`, minus the per-row icons (excluded by design decision — the design source had `Icon name="pin|phone|mail|clock"`; we render text labels only).

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

**Notes**

- `<livewire:contact-form />` is plain Blade — works because `ContactForm` is already registered as `contact-form` in `AppServiceProvider::boot()` (`AppServiceProvider.php:23`).
- `socials` is filtered defensively in PHP (drop rows missing label or URL) before render, so a half-edited admin row never produces a bare empty link.
- `target="_blank" rel="noopener"` on social links prevents tab-nabbing.

## Contact form blade rewrite

Replace `resources/views/forms/contact.blade.php`. Constraint: keep `wire:model="name|email|message|hp"` and `wire:submit="submit"` exactly — the existing `tests/Feature/Forms/ContactFormTest.php` drives the component via Livewire's API and does not inspect DOM, so markup is free to change.

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

**Success state mechanics.** `FormComponent::submit()` already does `session()->flash("forms.success.contact", true)` and resets the public fields. Livewire re-renders without a full page reload, so the `@if (session('forms.success.contact'))` branch swaps the form for the thank-you card in place. No new public property on `ContactForm` is required — the existing infra already wires this UX.

**New form translations** in `lang/{uk,en}/forms.php`:

```
contact.thanks  // uk: "Дякуємо! Ми отримали ваше повідомлення."
                // en: "Thank you. We've received your message."
contact.submit  // uk: "Надіслати"   en: "Send"
contact.agree   // uk: "Натискаючи кнопку, я погоджуюсь з політикою конфіденційності"
                // en: "By clicking, I agree with the privacy policy"
```

Existing `forms.fields.name|email|message` keys stay as-is.

**Icons.** The success card uses the plain `✓` glyph inside `<div class="ok">` — matches the pattern in `resources/views/forms/order.blade.php:10`. The submit button uses `<span class="btn-arrow" aria-hidden="true">→</span>` — matches `resources/views/components/site/product-info.blade.php:80`. The project has no shared icon component (verified — `app/View/` does not exist), so following the established inline-glyph convention.

## CSS

New `resources/css/site/components/contacts.css`. Adapts `styles.css:833–840` to the project's CSS variables and adds the missing styles for the success card, form fields, and social-links row.

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

Then add one line to `resources/css/site/index.css`:

```css
@import './components/contacts.css';
```

`.btn` is already global (used across catalogue/homepage), so the submit-button styling doesn't need redefinition.

## Navigation

### Header (`resources/views/components/site/header.blade.php`)

Append `contacts` to `$nav` as the last entry, after `articles`. The Philosophy nav entry is already in place (`header.blade.php:10`), so the resulting order is `home / catalogue / philosophy / articles / contacts`. Match uses exact-equality (`$r === '/' . $contactsSlug`) — same shape as the existing Philosophy match — so the link is highlighted only on the contacts page itself, not on any incidental path that happens to start with `/contacts`.

```php
$contactsSlug = config('content.contacts_slug')[$locale] ?? config('content.contacts_slug')['uk'];
$contactsUrl  = route('page.show', ['slug' => $contactsSlug]);

$nav = [
    ['key' => 'home',       /* ... */],
    ['key' => 'catalog',    /* ... */],
    ['key' => 'philosophy', /* ... already present */],
    ['key' => 'articles',   /* ... */],
    ['key' => 'contacts',   'url' => $contactsUrl,
                            'match' => fn ($r) => $r === '/' . $contactsSlug],
];
```

### Footer (`resources/views/components/site/footer.blade.php`)

Two changes:

1. The **Nav column** (footer.blade.php:18–25) gets a `<li>` for Contacts, after Articles.
2. The **Contact column** (footer.blade.php:54–62) gets a `<li>` linking to the contacts page itself, inserted as the first item; the existing phone/email/Instagram/Telegram convenience links stay.

```blade
{{-- Nav column --}}
<li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale]]) }}">{{ __('site.nav.contacts') }}</a></li>

{{-- Contact column, first <li> --}}
<li><a href="{{ route('page.show', ['slug' => config('content.contacts_slug')[$locale]]) }}">{{ __('site.footer.links.contacts_page') }}</a></li>
```

### Site translations

`lang/{uk,en}/site.php`:

```
nav.contacts                  // uk: "Контакти"           en: "Contacts"
footer.links.contacts_page    // uk: "Сторінка контактів" en: "Contacts page"
```

The footer-link key is distinct from `nav.contacts` so the footer can say "Сторінка контактів" / "Contacts page" — clearer under the "Зв'язок" / "Contact" column heading than the bare "Контакти" / "Contacts".

## Testing

New file `tests/Feature/Content/ContactsPageTest.php`. Pest auto-applies `RefreshDatabase`. Tests run on SQLite `:memory:` — no MySQL-only syntax.

1. **Renders at the uk slug.** Seed the page (call `PageSeeder` partially or build a minimal `Page` inline); GET `/uk/kontakty` → 200, response contains the title `Контакти`, the seeded phone, the seeded email.
2. **Renders at the en slug.** Same for `/en/contacts`; response contains `Contacts` and the English address.
3. **Hides empty info rows.** Save the page with `phone = ''` and `hours = null`. Response must not contain the localized phone or hours labels nor a `tel:` href. Address & email rows still render.
4. **Hides the socials row when no social links are configured.** Save the contact block with `socials = []`. Response must not contain the localized social label or any of `Instagram`, `Telegram`, `TikTok`.
5. **Hidden block keeps the page reachable but omits its content.** Save with `blocks[0].data.is_visible = false`. GET returns 200, response does not contain the contact title or any info row (relies on `Page::visibleBlocks()`).
6. **Embeds the contact form Livewire component.** `assertSeeLivewire(ContactForm::class)` on the GET response.
7. **Header link present.** GET `/uk`. Resolve the expected URL via `route('page.show', ['slug' => config('content.contacts_slug')['uk']])` and `assertSee($expected, escape: false)` — same flakiness trap noted in the Philosophy spec (`route()` returns absolute URLs).

The existing `ContactFormTest`, `FormTypeTest`, `FormRateLimiterTest` cover the form submission flow itself; not duplicated here.

## Acceptance criteria

- `php artisan migrate:fresh --seed` creates a Contacts page at `/uk/kontakty` and `/en/contacts`.
- The page renders breadcrumbs, the eyebrow/title/lead, then a 2-column grid: info column (address, phone, email, hours, socials — each row hidden if its data is empty) and a form card with the styled `<livewire:contact-form />`.
- Form submit creates a `FormSubmission` row of type `contact`, queues `ContactAdminMail`, and swaps the form for the thank-you card in place.
- Header nav has a "Contacts" / "Контакти" link after "Articles". Footer Nav column includes Contacts; the existing footer Contact column gets a "Contacts page" link at the top.
- `composer test` is green; `./vendor/bin/pint` reports no issues.
- Admin can edit the `contact` block from Filament with translatable tabs for all text fields and a reorderable socials repeater.

## Risks

- **Footer Contact column duplication.** The footer's hardcoded phone/email/Instagram/Telegram (`footer.blade.php:54–62`) will drift from the admin-edited block data. Accepted in v1 — the new page is the canonical surface; the footer is a convenience. Follow-up: read footer contact links from the contact block.
- **Seeder overwrites admin edits.** `PageSeeder` is upsert; re-running it after admin edits resets the block content. Same behaviour as help and philosophy pages — treat seed data as initial state, not source of truth.
- **No phone-format validation on the display field.** `phone_href` is regex-validated to `^\+?[0-9]+$` in the admin form, but `phone` (the displayed string) is free text. Risk is low (admin-only field) and any error is locally visible on the page.

## File-level change list

**New files**

- `app/Filament/Resources/Pages/Schemas/Blocks/ContactBlock.php`
- `resources/views/pages/blocks/contact.blade.php`
- `resources/css/site/components/contacts.css`
- `tests/Feature/Content/ContactsPageTest.php`

**Modified files**

- `app/Enums/BlockType.php` — add `Contact` case.
- `app/Filament/Resources/Pages/Schemas/PageForm.php` — register `ContactBlock` in the Builder.
- `config/content.php` — add `contacts_slug` map. `reserved_slugs` is **not** modified (would block the seeder).
- `database/seeders/Content/PageSeeder.php` — seed the Contacts page (idempotent upsert).
- `lang/uk/content.php`, `lang/en/content.php` — admin labels (`blocks.contact.*`, `blocks.fields.*`) and public labels (`blocks.contact.label_*`).
- `lang/uk/site.php`, `lang/en/site.php` — `nav.contacts`, `footer.links.contacts_page`.
- `lang/uk/forms.php`, `lang/en/forms.php` — `contact.thanks`, `contact.submit`, `contact.agree`.
- `resources/css/site/index.css` — `@import './components/contacts.css'`.
- `resources/views/components/site/header.blade.php` — append Contacts nav entry.
- `resources/views/components/site/footer.blade.php` — add Contacts links to Nav and Contact columns.
- `resources/views/forms/contact.blade.php` — replace the placeholder with the design-styled form + success state.
