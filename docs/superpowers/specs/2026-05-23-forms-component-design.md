# Forms: Universal Submissions Component

**Date:** 2026-05-23
**Status:** Approved
**Scope:** Backend engine for public forms (registration, persistence, email, anti-spam, admin inbox). The actual form markup (HTML/CSS) per form type is written by a developer using the engine — this spec does NOT design any specific form's view.

## Context

The site will need several public forms: contact / callback, order request, and likely more (price subscription, product question, etc.). The patterns repeat: validate input → persist → notify admin → optionally notify client. Building each form ad hoc would duplicate validation, persistence, email-dispatch and admin-view code, and leaves no single «inbox» for the operator.

This spec defines a reusable subsystem (new `App\Forms\*` namespace) that handles all of that, so adding a new form type is reduced to: a `FormType` class, one or two Mailables, a thin Livewire component extending the base, and a Blade view for the markup.

The storefront itself does not yet exist (the only public route is the localized `/` welcome) — this engine is built ahead of the first form usage, so the engine ships first and the forms are added as the storefront grows.

## Decisions (recorded from brainstorming)

| # | Decision | Rationale |
|---|---|---|
| 1 | PHP class per form type (not config-array, not DB-stored) | Typed, testable, statically analysable; matches «engine, not builder» framing |
| 2 | Single `form_submissions` table with JSON `data` payload | Maximally universal; YAGNI on Order — promotes to own entity later if needed |
| 3 | Per-type `Mailable` (not one generic Mailable) | Per-type subject, headers, attachments stay typed; per-type Blade template |
| 4 | Honeypot + Laravel RateLimiter for v1 | No external dependency, no UX hit; sufficient for launch |
| 5 | Single polymorphic `subject` (most often Product); extra context via meta JSON | Covers the «текущий товар на странице» case without inventing new joins |
| 6 | Read-only Filament resource — no Create/Edit pages | This is an inbox; admin acts via row-level status actions only |
| 7 | Bilingual (uk/en) via existing locale plumbing; visitor locale stored on row | Admin email in uk (fallback), client email in visitor's locale |
| 8 | No full Order domain in v1 — order stays «structured submission» | Real Order with statuses/payment/shipping is a separate, larger spec |

## Architecture

New top-level domain `App\Forms\*`, parallel to `App\Models\Catalogue` and `App\Models\Content` (and intentionally placed outside `App\Models` because it owns its own Livewire/Mail/Filament artefacts):

```
app/Forms/
  Types/
    FormType.php                # abstract base class
    ContactFormType.php         # v1
    OrderFormType.php           # v1
  Models/
    FormSubmission.php
  Livewire/
    FormComponent.php           # abstract Livewire base
    ContactForm.php
    OrderForm.php
  Mail/
    ContactAdminMail.php
    OrderAdminMail.php
    OrderClientMail.php
  Support/
    FormRateLimiter.php         # thin wrapper around Illuminate RateLimiter
app/Filament/Resources/FormSubmissions/
  FormSubmissionResource.php
  Schemas/FormSubmissionInfolist.php
  Tables/FormSubmissionsTable.php
  Pages/{ListFormSubmissions, ViewFormSubmission}.php
database/migrations/..._create_form_submissions_table.php
config/forms.php
lang/{uk,en}/forms.php
resources/views/components/forms/honeypot.blade.php
resources/views/emails/forms/{contact,order}-{admin,client}.blade.php
tests/Feature/Forms/...
```

Filament navigation: new group `forms` («Заявки» / «Submissions»), `navigationSort=3` (after `catalogue` and `content`).

## Data model

### `form_submissions`

```php
$table->id();
$table->string('type', 64)->index();                                  // FormType::key()
$table->string('status', 16)->default('new')->index();                // new | read | processed
$table->json('data');                                                  // validated payload
$table->nullableMorphs('subject');                                     // subject_type, subject_id
$table->json('meta')->nullable();                                      // url, ip, user_agent, referer
$table->string('locale', 5)->nullable();                               // LaravelLocalization::getCurrentLocale()
$table->timestamp('handled_at')->nullable();                           // set on transition to 'processed'
$table->timestamps();
$table->index(['type', 'status']);                                     // common admin filter combo
$table->index('created_at');                                           // default sort
```

`status` is stored as `string` (not enum) for SQLite test parity — the project pattern is to validate the enum value in PHP, not at the DB level. Allowed values live on the model as a const array.

The `subject` morph is nullable because most forms (e.g. generic contact) have no subject. For order-from-product, the developer passes `:subject="$product"` and the column is populated automatically by the base Livewire component.

`meta` is intentionally JSON, not separate columns — it is captured «for the record» (debugging spam, tracing referrers) and is not queried in the v1 admin UI. If we later want to filter by referer we add a column then; YAGNI now.

### Why single table (justification of decision #2)

Order and Contact look semantically very different — Order has line items, customer details, totals; Contact is name+email+message. The temptation is to model them separately. The argument for unifying them anyway:

- The site has **no checkout, no payment, no fulfilment** — an «order» today is functionally a structured request: «I want N of product X, here's my contact info, call me». That fits inside JSON just like Contact.
- The admin needs a **single inbox** to act on incoming requests — splitting now means two resources, two filters, two notification pipelines.
- When a real Order domain appears (statuses, payments, shipping, invoicing), it gets its own table and its own admin module. The migration is straightforward — the JSON payload is a historical record of how the request came in, and the new domain starts fresh.

## FormType abstract class

The single registration point for a form type. Adding a new form type to the system means: writing one of these.

```php
namespace App\Forms\Types;

abstract class FormType
{
    /** Stable machine key — goes into form_submissions.type, rate-limit keys, mail view names. */
    abstract public function key(): string;

    /** Translated, human-readable label for admin UI. */
    abstract public function label(): string;

    /** Laravel validation rules. $subject is the polymorphic Eloquent model (or null). */
    abstract public function rules(?\Illuminate\Database\Eloquent\Model $subject = null): array;

    /** Translated attribute names for validator messages (field => label). */
    public function attributes(): array { return []; }

    /** If the form requires a subject, return its class name (e.g. Product::class). */
    public function subjectClass(): ?string { return null; }
    public function subjectRequired(): bool { return false; }

    /** Recipients of the admin email. */
    public function adminRecipients(): array
    {
        return array_filter([config('forms.admin_email')]);
    }

    /** Mailable for the admin notification. Required. */
    abstract public function adminMailable(\App\Forms\Models\FormSubmission $submission): \Illuminate\Mail\Mailable;

    /** Mailable for the client confirmation. Return null to skip. */
    public function clientMailable(\App\Forms\Models\FormSubmission $submission): ?\Illuminate\Mail\Mailable
    {
        return null;
    }

    /** Field name in $submission->data that contains the client's email; null disables client mail. */
    public function clientEmailField(): ?string { return 'email'; }

    /** [attempts, decay_minutes] for RateLimiter, keyed by type + IP. */
    public function rateLimit(): array { return [5, 60]; }
}
```

`ContactFormType` returns rules like `['name' => ['required','string','max:120'], 'email' => ['required','email:rfc'], 'message' => ['required','string','max:2000']]` and an `adminMailable` of `ContactAdminMail`, no client mailable.

`OrderFormType` returns `subjectClass = Product::class`, `subjectRequired = true`, rules including `name`, `phone`, `email`, `qty`, optional `note`; admin + client mailable both present.

## Base Livewire component

```php
namespace App\Forms\Livewire;

abstract class FormComponent extends \Livewire\Component
{
    public ?\Illuminate\Database\Eloquent\Model $subject = null;
    public string $hp = '';                                             // honeypot

    abstract protected function formType(): \App\Forms\Types\FormType;
    abstract public function render(): \Illuminate\Contracts\View\View;

    public function mount(?\Illuminate\Database\Eloquent\Model $subject = null): void
    {
        $type = $this->formType();
        if ($type->subjectRequired()) {
            $expected = $type->subjectClass();
            if (! $subject instanceof $expected) {
                throw new \LogicException("Form {$type->key()} requires subject of type {$expected}");
            }
        }
        $this->subject = $subject;
    }

    public function submit(): void
    {
        $type = $this->formType();

        // Silent honeypot
        if ($this->hp !== '') {
            logger()->channel('stack')->info('forms.honeypot.tripped', [
                'type' => $type->key(), 'ip' => request()->ip(),
            ]);
            return;
        }

        // Rate limit
        \App\Forms\Support\FormRateLimiter::ensureAllowed($type, request());

        $data = $this->validate($type->rules($this->subject), [], $type->attributes());

        $submission = \App\Forms\Models\FormSubmission::create([
            'type' => $type->key(),
            'status' => 'new',
            'data' => $data,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'meta' => [
                'url' => url()->previous(),
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'referer' => request()->headers->get('referer'),
            ],
            'locale' => app(\Mcamara\LaravelLocalization\LaravelLocalization::class)->getCurrentLocale(),
        ]);

        $this->dispatchEmails($type, $submission);

        $this->reset($this->resetableFields());
        session()->flash("forms.success.{$type->key()}", true);
        $this->dispatch('form-submitted', type: $type->key());
    }

    protected function resetableFields(): array
    {
        // Override per-form when needed; default is "everything except subject and hp".
        return array_diff(
            array_keys(get_object_vars($this)),
            ['subject', 'hp']
        );
    }

    protected function dispatchEmails(\App\Forms\Types\FormType $type, \App\Forms\Models\FormSubmission $submission): void
    {
        \Illuminate\Support\Facades\Mail::to($type->adminRecipients())
            ->locale(config('app.fallback_locale'))
            ->queue($type->adminMailable($submission));

        if ($clientMail = $type->clientMailable($submission)) {
            $field = $type->clientEmailField();
            $address = $field ? data_get($submission->data, $field) : null;
            if ($address) {
                \Illuminate\Support\Facades\Mail::to($address)
                    ->locale($submission->locale ?: config('app.fallback_locale'))
                    ->queue($clientMail);
            }
        }
    }
}
```

A concrete component is small:

```php
class OrderForm extends FormComponent
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public string $note = '';
    public int    $qty = 1;

    protected function formType(): FormType { return app(OrderFormType::class); }
    public function render(): View { return view('forms.order'); }
}
```

The developer writes `resources/views/forms/order.blade.php` with full freedom over markup; the only required line in the view is the honeypot partial:

```blade
<x-forms.honeypot wire:model="hp" />
```

### Embedding

```blade
<livewire:contact-form />
<livewire:order-form :subject="$product" />
```

`subject` is the only «context» parameter the base supports. If a future form needs other context (campaign id, referrer code), the developer adds public properties to the concrete component and accepts them as Livewire attributes — the base does not need to know.

### Mailables

Per-type `Mailable` subclasses live in `App\Forms\Mail`, each backed by a Blade template in `resources/views/emails/forms/{type}-{admin|client}.blade.php`. They are all `ShouldQueue`. The Mailable's constructor accepts the `FormSubmission` and exposes it to the view; subject lines are generated via `trans('forms.mail.{type}.{admin|client}.subject', [...])` so admin-side mail respects the project's `fallback_locale` plumbing.

### Anti-spam: honeypot + rate limit

**Honeypot** — `<x-forms.honeypot>` partial renders:

```blade
<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
    <label for="hp_{{ $attributes->get('wire:model') }}">Leave this field empty</label>
    <input type="text" id="hp_..." {{ $attributes }} tabindex="-1" autocomplete="off">
</div>
```

If the bound property is non-empty, `submit()` returns silently without persisting or mailing — the bot sees a 200 OK and gives up.

**Rate limit** — `App\Forms\Support\FormRateLimiter`:

```php
public static function ensureAllowed(FormType $type, Request $request): void
{
    [$maxAttempts, $decayMinutes] = $type->rateLimit();
    $key = "forms:{$type->key()}:" . $request->ip();
    if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
        throw \Illuminate\Validation\ValidationException::withMessages([
            'form' => trans('forms.errors.rate_limited'),
        ]);
    }
    RateLimiter::hit($key, $decayMinutes * 60);
}
```

Throwing `ValidationException` (not `ThrottleRequestsException`) means the message surfaces inline in the form via Livewire's standard error bag, matching where users look for feedback. The error key `form` is a non-field key shown above the form.

## Config (`config/forms.php`)

```php
return [
    'admin_email' => env('FORMS_ADMIN_EMAIL'),

    'types' => [
        \App\Forms\Types\ContactFormType::class,
        \App\Forms\Types\OrderFormType::class,
    ],

    'queue' => env('FORMS_QUEUE', null),    // null = default queue

    'statuses' => ['new', 'read', 'processed'],
];
```

The `types` registry is used by the Filament resource (filter dropdown labels, registered type list for admin notifications). The base Livewire component does NOT consult this registry at runtime — each Livewire component instantiates its `FormType` directly.

`.env` additions:

```
FORMS_ADMIN_EMAIL=admin@levantparfums.test
FORMS_QUEUE=
```

## Filament admin

### Navigation

```php
\Filament\Navigation\NavigationGroup::make()
    ->label(fn () => trans('forms.navigation.group'))
    ->icon('heroicon-o-inbox-arrow-down'),
```

Added in `AdminPanelProvider::navigationGroups()` after the `content` group.

### `FormSubmissionResource`

- **No `CreateRecord` page, no `EditRecord` page.** Only `ListFormSubmissions` and `ViewFormSubmission`. Removed by overriding `getPages()` to return only those two and by not generating Create/Edit page classes.
- `getNavigationGroup()` returns `trans('forms.navigation.group')`; `navigationSort = 3`.
- `getNavigationBadge()` returns the count of `status = 'new'` rows so the operator sees how many unhandled submissions exist.
- Icon `heroicon-o-envelope`.

### `FormSubmissionsTable`

Columns:
- `TextColumn::make('type')` — badge, label from `trans("forms.types.{$state}")`, sortable, filterable.
- `TextColumn::make('status')` — badge with colors (new → warning, read → info, processed → success).
- `TextColumn::make('summary')` — virtual accessor on the model: builds a one-line preview like «Иван Иванов <ivan@example.com>» from `data` (name/email/phone, in that priority order, falling back to `Str::limit($message, 60)`).
- `TextColumn::make('subject')` — formatted state: if `subject` resolves to a Product, show its name with a link to `ProductResource::getUrl('edit', [$subject])`; else dash.
- `TextColumn::make('created_at')` — datetime, sortable, default desc.

Filters:
- `SelectFilter::make('type')` — options from `config('forms.types')` mapped to their `key()`/`label()`.
- `SelectFilter::make('status')` — `new` / `read` / `processed`.
- `Filter::make('created_at')` — between dates.

Row actions:
- `Action::make('mark_read')` — visible when `status = new`; sets status to `read`.
- `Action::make('mark_processed')` — visible when `status != processed`; sets status to `processed` and `handled_at = now()`.
- `Action::make('mark_new')` — visible when `status != new`; reverts status, clears `handled_at`. For mistakes.
- `ViewAction::make()` — opens `ViewFormSubmission`.

Bulk actions: `bulk_mark_read`, `bulk_mark_processed` — same pattern as catalogue resources.

### `FormSubmissionInfolist` (the ViewRecord page)

Sections:
- **Header:** `type` badge, `status` badge, `created_at`, `subject` link.
- **Данi форми / Form data:** `KeyValueEntry` rendering `data` as label/value pairs; labels come from the `FormType::attributes()` of the corresponding type when possible (resolved by the page using `config('forms.types')`).
- **Контекст / Context:** `meta` rendered as key/value (URL, IP, user agent, referer).
- **Технiчне / Technical:** `locale`, `handled_at`, model timestamps.

The page calls the same status-transition actions as the table.

### Admin notifications

On the `created` event of `FormSubmission`, a `Filament\Notifications\Notification` is sent (via `sendToDatabase`) to every `User` (admin panel access only — `User` model is the only authenticated entity now, this can be tightened later when roles are added):

```php
Notification::make()
    ->title(trans('forms.notifications.new', ['type' => trans("forms.types.{$submission->type}")]))
    ->icon('heroicon-o-inbox-arrow-down')
    ->actions([
        Action::make('view')->url(FormSubmissionResource::getUrl('view', [$submission])),
    ])
    ->sendToDatabase(User::all());
```

The fan-out is acceptable for the project's expected admin count (single-digit). When roles are introduced, this filters to users with the `view_form_submissions` permission.

The send is dispatched from a model observer (`FormSubmissionObserver`), not from the Livewire component, so any future source of submissions (Filament-side import, console command) emits notifications correctly.

## Translations (`lang/{uk,en}/forms.php`)

```php
return [
    'navigation' => ['group' => 'Заявки' /* en: 'Submissions' */],

    'resource' => [
        'label' => 'Заявка', 'plural' => 'Заявки',
    ],

    'types' => [
        'contact' => 'Зворотний звʼязок',
        'order'   => 'Замовлення',
    ],

    'statuses' => [
        'new' => 'Нова', 'read' => 'Переглянуто', 'processed' => 'Опрацьовано',
    ],

    'actions' => [
        'mark_read'      => 'Позначити переглянутою',
        'mark_processed' => 'Опрацьовано',
        'mark_new'       => 'Повернути в «Новi»',
    ],

    'fields' => [
        'type' => 'Тип', 'status' => 'Статус', 'subject' => 'Контекст',
        'summary' => 'Коротко', 'data' => 'Данi форми', 'meta' => 'Контекст',
        'locale' => 'Локаль', 'created_at' => 'Створено', 'handled_at' => 'Опрацьовано',
    ],

    'errors' => [
        'rate_limited' => 'Забагато спроб. Спробуйте пiзнiше.',
    ],

    'notifications' => [
        'new' => 'Нова заявка: :type',
    ],

    'mail' => [
        'contact' => [
            'admin' => ['subject' => 'Нова заявка зворотного звʼязку'],
        ],
        'order' => [
            'admin'  => ['subject' => 'Нове замовлення'],
            'client' => ['subject' => 'Ми отримали ваше замовлення'],
        ],
    ],
];
```

English mirror added in the same shape under `lang/en/forms.php`.

## Factories & seeders

- `database/factories/Forms/FormSubmissionFactory.php` — produces realistic rows for the two v1 types, varying `status` and `created_at`.
- `database/seeders/Forms/FormSubmissionSeeder.php` — inserts ~10 demo submissions (mix of statuses, mix of types, half attached to a random Product) so the admin shows something useful out of the box. Registered in `DatabaseSeeder` after the `Content` block.

## Tests (Pest)

All under `tests/Feature/Forms/`. `RefreshDatabase` is auto-applied.

### Engine + model invariants

```
tests/Feature/Forms/FormSubmissionTest.php
  - persists data, meta, locale, subject morph
  - status defaults to 'new'
  - handled_at is null until status transitions to 'processed'

tests/Feature/Forms/ContactFormTest.php
  - valid submit creates submission row with data and locale
  - valid submit queues ContactAdminMail to FORMS_ADMIN_EMAIL
  - no clientMailable → only one mail sent
  - honeypot tripped → no row, no mail, returns success
  - 6th submit from same IP within window throws ValidationException
  - invalid email surfaces inline validation error

tests/Feature/Forms/OrderFormTest.php
  - mount without subject throws LogicException
  - mount with non-Product subject throws LogicException
  - valid submit persists subject morph to Product
  - valid submit queues OrderAdminMail and OrderClientMail in submission locale
  - missing client email field → client mail skipped, admin mail still sent
```

`Mail::fake()` + `Queue::fake()` in each test. `request()->ip()` mocked via Livewire's request swap or by hitting via `$this->withServerVariables(['REMOTE_ADDR' => '...'])` when needed.

### Filament admin

```
tests/Feature/Forms/Filament/FormSubmissionsResourceTest.php
  - list page renders for admin user
  - create / edit pages return 404 (resource is read-only)
  - mark_read action transitions status new → read
  - mark_processed action sets status processed AND handled_at
  - mark_new action reverts status and clears handled_at
  - filter by type narrows the list
  - filter by status narrows the list
  - navigation badge equals count of status='new'
```

The notification fan-out is verified in a small unit-style test of `FormSubmissionObserver::created` (`Notification::fake()`); broader Filament render tests are intentionally skipped — same policy as Catalogue and Content modules.

## Implementation order

1. **Config** — `config/forms.php`, `.env.example` update.
2. **Migration** — `create_form_submissions_table` (single migration, both MySQL and SQLite happy — no functional indexes needed).
3. **Model** — `App\Forms\Models\FormSubmission` with casts (`data`, `meta` → array; `handled_at` → datetime), morph `subject()`, status constants, `setStatus()` helper.
4. **Translations** — `lang/{uk,en}/forms.php`.
5. **Mail templates + Mailables** — base view layout + `ContactAdminMail`, `OrderAdminMail`, `OrderClientMail`.
6. **FormType base + ContactFormType + OrderFormType.**
7. **Base FormComponent + ContactForm + OrderForm + honeypot partial.**
8. **`FormSubmissionObserver` + admin notification fan-out.**
9. **Filament `FormSubmissionResource` (list + view only) + table + infolist + actions.**
10. **Factories + seeder.**
11. **Tests** in the order above; smoke-render any Mailable via `php artisan tinker` before considering the step done.
12. **CLAUDE.md** — add a paragraph in «Architecture notes» describing the «add new form type» recipe (FormType + Mailable + Livewire + Blade + config registration).
13. **Verification.** `composer test`; manual: `php artisan serve` → admin → submit a Contact and Order via tinker / temporary route; confirm inbox + notification + queued mail (with `MAIL_MAILER=log` or Mailpit).

## Explicit non-goals (v1)

- No full Order domain (statuses payment/shipping, OrderItems, invoices) — Order remains a structured submission.
- No reply-to-customer from admin; no file attachments on forms; no multi-step or conditional forms.
- No CAPTCHA / Turnstile / reCAPTCHA — honeypot + rate-limit only.
- No webhooks to external systems (Telegram, Slack); no CSV export from the admin inbox.
- No frontend Blade views for the two v1 forms — only the engine and the Livewire classes ship. The first form view is built when the relevant page first needs it.
- No `Notifiable` per-user routing for admin email — single recipient from `.env`.

## Verification end-to-end

1. `composer test` — all green.
2. `php artisan migrate:fresh --seed` — DB recreated, admin user seeded, ~10 demo submissions visible at `/admin` under «Заявки».
3. From tinker: instantiate `ContactForm`, set fields, call `submit()` — row appears; with `MAIL_MAILER=log`, the admin mail body is rendered in `storage/logs/laravel.log`.
4. From tinker: instantiate `OrderForm`, pass a real `Product`, submit — row created with `subject` populated; two queued mails (admin + client) visible.
5. Hammer the same IP 6 times in tinker — 6th submit throws `ValidationException` with the translated rate-limit message.
6. In the admin: navigation badge reflects unhandled count, status actions move rows, notifications appear in the bell icon for the admin user.
