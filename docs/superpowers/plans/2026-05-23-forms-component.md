# Forms: Universal Submissions Component — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Ship a reusable backend engine (`App\Forms\*`) that turns any public Livewire form into: validated input → row in `form_submissions` (with optional polymorphic `subject`) → queued admin email → optional queued client email, plus a read-only Filament inbox with status workflow and bell-icon notifications. Two concrete form types ship: `Contact` and `Order`.

**Architecture:** Single `form_submissions` table with JSON `data` payload, polymorphic `subject` (most commonly a Product), and status workflow (`new` / `read` / `processed`). Each form type is a PHP class (`FormType` subclass) declaring rules, attributes, mailables. Each public form is a thin Livewire component extending an abstract `FormComponent` base that owns the submit pipeline (honeypot → rate limit → validate → persist → dispatch mails). The admin is a Filament resource with only List + View pages, status row-actions, and a navigation badge for new submissions; new rows fan out a Filament database-notification to every admin user via a model observer.

**Tech Stack:** Laravel 13, PHP 8.3, MySQL 8.0 (prod) / SQLite `:memory:` (tests), Livewire 3 (transitive via Filament), Filament 5, Pest 4 + `pestphp/pest-plugin-laravel`, Spatie Translatable 6 (for type labels in `lang/`), `mcamara/laravel-localization` 2.

**Spec:** `docs/superpowers/specs/2026-05-23-forms-component-design.md`

**Conventions used throughout:**
- All Bash commands assume `cwd = /Users/romanroman/Projects/LevantParfums`.
- Migration timestamp in this plan is a placeholder (`2026_05_23_HHMMSS_*`); use `php artisan make:migration` to get the actual timestamp.
- Commits use the prefix `forms:` to match the project's recent prefix style (`content: ...`, `catalogue: ...`).
- Pest is the test runner. `RefreshDatabase` is auto-applied to everything under `tests/Feature` via `tests/Pest.php`; do not re-add it.
- Mail templates use Laravel Markdown mailables (`Content::markdown(...)`); no plain HTML views.
- New Livewire components live outside Livewire's default `App\Livewire` namespace, so each one must be registered explicitly in `AppServiceProvider::boot()` for the `<livewire:contact-form />` Blade syntax to resolve. Tests reach the class directly and do not depend on registration.

---

## File Structure

```
config/forms.php                                                         [Task 1, create]
.env.example                                                             [Task 1, modify]

database/migrations/2026_05_23_HHMMSS_create_form_submissions_table.php  [Task 2, create]

app/Forms/Models/FormSubmission.php                                      [Task 3, create]
database/factories/Forms/FormSubmissionFactory.php                       [Task 3, create]
tests/Feature/Forms/FormSubmissionTest.php                               [Task 3, create]

lang/uk/forms.php                                                        [Task 4, create]
lang/en/forms.php                                                        [Task 4, create]

app/Forms/Mail/ContactAdminMail.php                                      [Task 5, create]
app/Forms/Mail/OrderAdminMail.php                                        [Task 5, create]
app/Forms/Mail/OrderClientMail.php                                       [Task 5, create]
resources/views/emails/forms/contact-admin.blade.php                     [Task 5, create]
resources/views/emails/forms/order-admin.blade.php                       [Task 5, create]
resources/views/emails/forms/order-client.blade.php                      [Task 5, create]

app/Forms/Types/FormType.php                                             [Task 6, create]
app/Forms/Types/ContactFormType.php                                      [Task 6, create]
app/Forms/Types/OrderFormType.php                                        [Task 6, create]
tests/Feature/Forms/FormTypeTest.php                                     [Task 6, create]

app/Forms/Support/FormRateLimiter.php                                    [Task 7, create]
tests/Feature/Forms/FormRateLimiterTest.php                              [Task 7, create]

app/Forms/Livewire/FormComponent.php                                     [Task 8, create]
resources/views/components/forms/honeypot.blade.php                      [Task 8, create]

app/Forms/Livewire/ContactForm.php                                       [Task 9, create]
resources/views/forms/contact.blade.php                                  [Task 9, create]
tests/Feature/Forms/ContactFormTest.php                                  [Task 9, create]

app/Forms/Livewire/OrderForm.php                                         [Task 10, create]
resources/views/forms/order.blade.php                                    [Task 10, create]
tests/Feature/Forms/OrderFormTest.php                                    [Task 10, create]

app/Forms/Observers/FormSubmissionObserver.php                           [Task 11, create]
app/Providers/AppServiceProvider.php                                     [Task 11, modify]
tests/Feature/Forms/FormSubmissionObserverTest.php                       [Task 11, create]

app/Filament/Resources/FormSubmissions/FormSubmissionResource.php        [Task 12, create]
app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php   [Task 12, create]
app/Filament/Resources/FormSubmissions/Schemas/FormSubmissionInfolist.php[Task 12, create]
app/Filament/Resources/FormSubmissions/Pages/ListFormSubmissions.php     [Task 12, create]
app/Filament/Resources/FormSubmissions/Pages/ViewFormSubmission.php      [Task 12, create]
app/Providers/Filament/AdminPanelProvider.php                            [Task 12, modify]
tests/Feature/Forms/Filament/FormSubmissionsResourceTest.php             [Task 12, create]

database/seeders/Forms/FormSubmissionSeeder.php                          [Task 13, create]
database/seeders/DatabaseSeeder.php                                      [Task 13, modify]

CLAUDE.md                                                                [Task 14, modify]
```

---

## Task 1: Config + .env + publish Livewire config

**Files:**
- Create: `config/forms.php`
- Create: `config/livewire.php` (published from vendor)
- Modify: `.env.example`

- [ ] **Step 1: Publish the Livewire config**

Run: `php artisan livewire:publish --config`
Expected stdout: `INFO  Config file successfully published.`

This creates `config/livewire.php` from the vendor's published copy. The new Livewire components in this plan live outside the default `App\Livewire` namespace and are registered explicitly in Task 11, so we keep the default `'class_namespace' => 'App\\Livewire'` value untouched — but we want the file in version control so the project's Livewire baseline is explicit and discoverable.

Verify the file exists:

Run: `test -f config/livewire.php && echo ok || echo MISSING`
Expected stdout: `ok`

- [ ] **Step 2: Create `config/forms.php`**

```php
<?php

return [
    'admin_email' => env('FORMS_ADMIN_EMAIL'),

    'types' => [
        \App\Forms\Types\ContactFormType::class,
        \App\Forms\Types\OrderFormType::class,
    ],

    'queue' => env('FORMS_QUEUE', null),

    'statuses' => ['new', 'read', 'processed'],
];
```

- [ ] **Step 3: Append the two new keys to `.env.example`**

Open `.env.example` and append (keep one blank line above):

```
FORMS_ADMIN_EMAIL=admin@levantparfums.test
FORMS_QUEUE=
```

- [ ] **Step 4: Sanity check**

Run: `php artisan tinker --execute='echo count(config("forms.statuses")), "|", config("livewire.class_namespace");'`
Expected stdout: `3|App\Livewire`

- [ ] **Step 5: Commit**

```bash
git add config/forms.php config/livewire.php .env.example
git commit -m "forms: config + Livewire config publish + .env scaffolding"
```

---

## Task 2: Migration — `form_submissions`

**Files:**
- Create: `database/migrations/2026_05_23_HHMMSS_create_form_submissions_table.php`

- [ ] **Step 1: Generate migration file**

Run: `php artisan make:migration create_form_submissions_table`

This produces a file like `database/migrations/2026_05_23_HHMMSS_create_form_submissions_table.php`. Note the exact path — every step below refers to it as «the migration file».

- [ ] **Step 2: Replace the migration body**

Replace the entire file contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('status', 16)->default('new')->index();
            $table->json('data');
            $table->nullableMorphs('subject');
            $table->json('meta')->nullable();
            $table->string('locale', 5)->nullable();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();

            $table->index(['type', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};
```

- [ ] **Step 3: Run the migration against the dev DB**

Run: `php artisan migrate`
Expected: a single `INFO Migration ... DONE` line for `create_form_submissions_table`.

- [ ] **Step 4: Verify the columns exist (SQLite-compatible since `composer test` uses it)**

Run: `php artisan db:show --table=form_submissions`
Expected: 10 columns listed: `id`, `type`, `status`, `data`, `subject_type`, `subject_id`, `meta`, `locale`, `handled_at`, `created_at`, `updated_at`.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/2026_05_23_HHMMSS_create_form_submissions_table.php
git commit -m "forms: create form_submissions table"
```

---

## Task 3: Model `FormSubmission` + factory + invariants tests

**Files:**
- Create: `app/Forms/Models/FormSubmission.php`
- Create: `database/factories/Forms/FormSubmissionFactory.php`
- Create: `tests/Feature/Forms/FormSubmissionTest.php`

- [ ] **Step 1: Create the model file**

`app/Forms/Models/FormSubmission.php`:

```php
<?php

namespace App\Forms\Models;

use Database\Factories\Forms\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    public const STATUS_NEW = 'new';
    public const STATUS_READ = 'read';
    public const STATUS_PROCESSED = 'processed';

    public const STATUSES = [self::STATUS_NEW, self::STATUS_READ, self::STATUS_PROCESSED];

    protected $fillable = [
        'type', 'status', 'data', 'subject_type', 'subject_id', 'meta', 'locale', 'handled_at',
    ];

    protected $attributes = [
        'status' => self::STATUS_NEW,
    ];

    protected function casts(): array
    {
        return [
            'data' => 'array',
            'meta' => 'array',
            'handled_at' => 'datetime',
        ];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function markRead(): void
    {
        if ($this->status === self::STATUS_NEW) {
            $this->update(['status' => self::STATUS_READ]);
        }
    }

    public function markProcessed(): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSED,
            'handled_at' => now(),
        ]);
    }

    public function markNew(): void
    {
        $this->update([
            'status' => self::STATUS_NEW,
            'handled_at' => null,
        ]);
    }
}
```

- [ ] **Step 2: Create the factory**

`database/factories/Forms/FormSubmissionFactory.php`:

```php
<?php

namespace Database\Factories\Forms;

use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<FormSubmission> */
class FormSubmissionFactory extends Factory
{
    protected $model = FormSubmission::class;

    public function definition(): array
    {
        return [
            'type' => 'contact',
            'status' => FormSubmission::STATUS_NEW,
            'data' => [
                'name' => $this->faker->name(),
                'email' => $this->faker->safeEmail(),
                'message' => $this->faker->paragraph(),
            ],
            'subject_type' => null,
            'subject_id' => null,
            'meta' => [
                'url' => $this->faker->url(),
                'ip' => $this->faker->ipv4(),
                'user_agent' => 'PestTest/1.0',
                'referer' => null,
            ],
            'locale' => 'uk',
            'handled_at' => null,
        ];
    }

    public function order(): static
    {
        return $this->state(fn () => [
            'type' => 'order',
            'data' => [
                'name' => $this->faker->name(),
                'phone' => $this->faker->phoneNumber(),
                'email' => $this->faker->safeEmail(),
                'qty' => $this->faker->numberBetween(1, 5),
                'note' => $this->faker->optional()->sentence(),
            ],
        ]);
    }

    public function status(string $status): static
    {
        return $this->state(fn () => [
            'status' => $status,
            'handled_at' => $status === FormSubmission::STATUS_PROCESSED ? now() : null,
        ]);
    }
}
```

- [ ] **Step 3: Write the failing invariants test**

`tests/Feature/Forms/FormSubmissionTest.php`:

```php
<?php

use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;

it('persists data, meta, locale and timestamps', function () {
    $submission = FormSubmission::factory()->create([
        'data' => ['name' => 'Іван', 'email' => 'ivan@example.test'],
        'meta' => ['ip' => '203.0.113.5'],
        'locale' => 'uk',
    ]);

    $fresh = $submission->fresh();
    expect($fresh->data)->toBe(['name' => 'Іван', 'email' => 'ivan@example.test']);
    expect($fresh->meta)->toBe(['ip' => '203.0.113.5']);
    expect($fresh->locale)->toBe('uk');
    expect($fresh->created_at)->not->toBeNull();
});

it('defaults status to new', function () {
    $submission = FormSubmission::create([
        'type' => 'contact',
        'data' => ['name' => 'X'],
    ]);

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_NEW);
});

it('resolves polymorphic subject to Product', function () {
    $product = Product::factory()->create();

    $submission = FormSubmission::factory()->order()->create([
        'subject_type' => $product->getMorphClass(),
        'subject_id' => $product->getKey(),
    ]);

    expect($submission->subject)->toBeInstanceOf(Product::class);
    expect($submission->subject->is($product))->toBeTrue();
});

it('markProcessed sets status and handled_at', function () {
    $submission = FormSubmission::factory()->create();

    $submission->markProcessed();

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_PROCESSED);
    expect($submission->fresh()->handled_at)->not->toBeNull();
});

it('markNew clears handled_at and resets status', function () {
    $submission = FormSubmission::factory()->status(FormSubmission::STATUS_PROCESSED)->create();
    expect($submission->handled_at)->not->toBeNull();

    $submission->markNew();

    expect($submission->fresh()->status)->toBe(FormSubmission::STATUS_NEW);
    expect($submission->fresh()->handled_at)->toBeNull();
});
```

- [ ] **Step 4: Run the test — it should pass already (model + factory exist)**

Run: `php artisan test --filter=FormSubmissionTest`
Expected: 5 passing tests.

If anything fails: re-read steps 1-3 — most likely a typo in the cast names or missing `$fillable` entry.

- [ ] **Step 5: Commit**

```bash
git add app/Forms/Models/FormSubmission.php database/factories/Forms/FormSubmissionFactory.php tests/Feature/Forms/FormSubmissionTest.php
git commit -m "forms: FormSubmission model + factory + invariants tests"
```

---

## Task 4: Translations

**Files:**
- Create: `lang/uk/forms.php`
- Create: `lang/en/forms.php`

- [ ] **Step 1: Create `lang/uk/forms.php`**

```php
<?php

return [
    'navigation' => [
        'group' => 'Заявки',
    ],

    'resource' => [
        'label' => 'Заявка',
        'plural' => 'Заявки',
    ],

    'types' => [
        'contact' => 'Зворотний звʼязок',
        'order' => 'Замовлення',
    ],

    'statuses' => [
        'new' => 'Нова',
        'read' => 'Переглянуто',
        'processed' => 'Опрацьовано',
    ],

    'actions' => [
        'mark_read' => 'Позначити переглянутою',
        'mark_processed' => 'Опрацьовано',
        'mark_new' => 'Повернути в «Новi»',
    ],

    'fields' => [
        'type' => 'Тип',
        'status' => 'Статус',
        'subject' => 'Контекст',
        'summary' => 'Коротко',
        'data' => 'Данi форми',
        'meta' => 'Технiчнi данi',
        'locale' => 'Локаль',
        'created_at' => 'Створено',
        'handled_at' => 'Опрацьовано',
        'name' => 'Iмʼя',
        'email' => 'Email',
        'phone' => 'Телефон',
        'message' => 'Повiдомлення',
        'qty' => 'Кiлькiсть',
        'note' => 'Коментар',
    ],

    'errors' => [
        'rate_limited' => 'Забагато спроб. Спробуйте пiзнiше.',
    ],

    'notifications' => [
        'new' => 'Нова заявка: :type',
    ],

    'mail' => [
        'contact' => [
            'admin' => [
                'subject' => 'Нова заявка зворотного звʼязку',
                'intro' => 'Отримано нову заявку зворотного звʼязку.',
            ],
        ],
        'order' => [
            'admin' => [
                'subject' => 'Нове замовлення',
                'intro' => 'Отримано нове замовлення.',
            ],
            'client' => [
                'subject' => 'Ми отримали ваше замовлення',
                'intro' => 'Дякуємо! Ми отримали вашу заявку i звʼяжемось найближчим часом.',
            ],
        ],
    ],
];
```

- [ ] **Step 2: Create `lang/en/forms.php` — mirror, English copy**

```php
<?php

return [
    'navigation' => [
        'group' => 'Submissions',
    ],

    'resource' => [
        'label' => 'Submission',
        'plural' => 'Submissions',
    ],

    'types' => [
        'contact' => 'Contact request',
        'order' => 'Order request',
    ],

    'statuses' => [
        'new' => 'New',
        'read' => 'Read',
        'processed' => 'Processed',
    ],

    'actions' => [
        'mark_read' => 'Mark as read',
        'mark_processed' => 'Mark as processed',
        'mark_new' => 'Move back to «New»',
    ],

    'fields' => [
        'type' => 'Type',
        'status' => 'Status',
        'subject' => 'Subject',
        'summary' => 'Summary',
        'data' => 'Form data',
        'meta' => 'Technical metadata',
        'locale' => 'Locale',
        'created_at' => 'Created at',
        'handled_at' => 'Processed at',
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'message' => 'Message',
        'qty' => 'Quantity',
        'note' => 'Note',
    ],

    'errors' => [
        'rate_limited' => 'Too many attempts. Please try again later.',
    ],

    'notifications' => [
        'new' => 'New submission: :type',
    ],

    'mail' => [
        'contact' => [
            'admin' => [
                'subject' => 'New contact request',
                'intro' => 'A new contact request has arrived.',
            ],
        ],
        'order' => [
            'admin' => [
                'subject' => 'New order request',
                'intro' => 'A new order request has arrived.',
            ],
            'client' => [
                'subject' => 'We received your order request',
                'intro' => 'Thank you — we received your request and will get back to you shortly.',
            ],
        ],
    ],
];
```

- [ ] **Step 3: Sanity check both files load**

Run: `php artisan tinker --execute='echo trans("forms.statuses.new", [], "uk"), "|", trans("forms.statuses.new", [], "en");'`
Expected stdout: `Нова|New`

- [ ] **Step 4: Commit**

```bash
git add lang/uk/forms.php lang/en/forms.php
git commit -m "forms: uk + en translations"
```

---

## Task 5: Mailables + email templates

**Files:**
- Create: `app/Forms/Mail/ContactAdminMail.php`
- Create: `app/Forms/Mail/OrderAdminMail.php`
- Create: `app/Forms/Mail/OrderClientMail.php`
- Create: `resources/views/emails/forms/contact-admin.blade.php`
- Create: `resources/views/emails/forms/order-admin.blade.php`
- Create: `resources/views/emails/forms/order-client.blade.php`

- [ ] **Step 1: Publish Laravel Markdown mail components (one-off, idempotent)**

Run: `php artisan vendor:publish --tag=laravel-mail`
Expected: writes `resources/views/vendor/mail/...` if not already there. If output says «No publishable resources» — the files already exist. Either way, continue.

- [ ] **Step 2: Create `app/Forms/Mail/ContactAdminMail.php`**

```php
<?php

namespace App\Forms\Mail;

use App\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactAdminMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans('forms.mail.contact.admin.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forms.contact-admin',
            with: ['s' => $this->submission],
        );
    }
}
```

- [ ] **Step 3: Create `app/Forms/Mail/OrderAdminMail.php`**

```php
<?php

namespace App\Forms\Mail;

use App\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderAdminMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans('forms.mail.order.admin.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forms.order-admin',
            with: ['s' => $this->submission],
        );
    }
}
```

- [ ] **Step 4: Create `app/Forms/Mail/OrderClientMail.php`**

```php
<?php

namespace App\Forms\Mail;

use App\Forms\Models\FormSubmission;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderClientMail extends Mailable implements ShouldQueue
{
    use Queueable;
    use SerializesModels;

    public function __construct(public FormSubmission $submission) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans('forms.mail.order.client.subject'));
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.forms.order-client',
            with: ['s' => $this->submission],
        );
    }
}
```

- [ ] **Step 5: Create `resources/views/emails/forms/contact-admin.blade.php`**

```blade
<x-mail::message>
# {{ trans('forms.mail.contact.admin.subject') }}

{{ trans('forms.mail.contact.admin.intro') }}

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '—' }}

**{{ trans('forms.fields.message') }}:**

{{ $s->data['message'] ?? '' }}

<x-mail::subcopy>
{{ trans('forms.fields.locale') }}: {{ $s->locale }}
{{ trans('forms.fields.created_at') }}: {{ $s->created_at?->toDateTimeString() }}
</x-mail::subcopy>
</x-mail::message>
```

- [ ] **Step 6: Create `resources/views/emails/forms/order-admin.blade.php`**

```blade
<x-mail::message>
# {{ trans('forms.mail.order.admin.subject') }}

{{ trans('forms.mail.order.admin.intro') }}

@if ($s->subject)
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? $s->subject->getKey() }}
@endif

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
**{{ trans('forms.fields.phone') }}:** {{ $s->data['phone'] ?? '—' }}
**{{ trans('forms.fields.email') }}:** {{ $s->data['email'] ?? '—' }}
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '—' }}

@if (!empty($s->data['note']))
**{{ trans('forms.fields.note') }}:** {{ $s->data['note'] }}
@endif

<x-mail::subcopy>
{{ trans('forms.fields.locale') }}: {{ $s->locale }}
{{ trans('forms.fields.created_at') }}: {{ $s->created_at?->toDateTimeString() }}
</x-mail::subcopy>
</x-mail::message>
```

- [ ] **Step 7: Create `resources/views/emails/forms/order-client.blade.php`**

```blade
<x-mail::message>
# {{ trans('forms.mail.order.client.subject') }}

{{ trans('forms.mail.order.client.intro') }}

**{{ trans('forms.fields.name') }}:** {{ $s->data['name'] ?? '—' }}
@if ($s->subject)
**{{ trans('forms.fields.subject') }}:** {{ $s->subject->name ?? $s->subject->getKey() }}
@endif
**{{ trans('forms.fields.qty') }}:** {{ $s->data['qty'] ?? '—' }}

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
```

- [ ] **Step 8: Smoke-render each mailable via tinker (no need to write a test yet — sender-side coverage comes in Tasks 9-10)**

Run: `php artisan tinker --execute='echo (new App\Forms\Mail\ContactAdminMail(App\Forms\Models\FormSubmission::factory()->make()))->render() !== "" ? "ok" : "EMPTY";'`
Expected stdout: `ok`

Repeat for `OrderAdminMail` and `OrderClientMail` (substitute factory state `->order()` so the order template has fields):

Run: `php artisan tinker --execute='echo (new App\Forms\Mail\OrderAdminMail(App\Forms\Models\FormSubmission::factory()->order()->make()))->render() !== "" ? "ok" : "EMPTY";'`
Run: `php artisan tinker --execute='echo (new App\Forms\Mail\OrderClientMail(App\Forms\Models\FormSubmission::factory()->order()->make()))->render() !== "" ? "ok" : "EMPTY";'`

Both expected: `ok`.

- [ ] **Step 9: Commit**

```bash
git add app/Forms/Mail resources/views/emails/forms
git commit -m "forms: Mailables + Markdown email templates"
```

---

## Task 6: `FormType` base + ContactFormType + OrderFormType + tests

**Files:**
- Create: `app/Forms/Types/FormType.php`
- Create: `app/Forms/Types/ContactFormType.php`
- Create: `app/Forms/Types/OrderFormType.php`
- Create: `tests/Feature/Forms/FormTypeTest.php`

- [ ] **Step 1: Create abstract `app/Forms/Types/FormType.php`**

```php
<?php

namespace App\Forms\Types;

use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

abstract class FormType
{
    /** Stable machine key — goes into form_submissions.type, rate-limit keys, mail view names. */
    abstract public function key(): string;

    /** Translated, human-readable label for admin UI. */
    abstract public function label(): string;

    /** Laravel validation rules. $subject is the polymorphic Eloquent model (or null). */
    abstract public function rules(?Model $subject = null): array;

    /** Translated attribute names for validator messages (field => label). */
    public function attributes(): array
    {
        return [];
    }

    /** If the form requires a subject, return its class name (e.g. Product::class). */
    public function subjectClass(): ?string
    {
        return null;
    }

    public function subjectRequired(): bool
    {
        return false;
    }

    /** Recipients of the admin email. */
    public function adminRecipients(): array
    {
        return array_filter([config('forms.admin_email')]);
    }

    /** Mailable for the admin notification. Required. */
    abstract public function adminMailable(FormSubmission $submission): Mailable;

    /** Mailable for the client confirmation. Return null to skip. */
    public function clientMailable(FormSubmission $submission): ?Mailable
    {
        return null;
    }

    /** Field name in $submission->data that contains the client's email; null disables client mail. */
    public function clientEmailField(): ?string
    {
        return 'email';
    }

    /** [attempts, decay_minutes] for RateLimiter, keyed by type + IP. */
    public function rateLimit(): array
    {
        return [5, 60];
    }
}
```

- [ ] **Step 2: Create `app/Forms/Types/ContactFormType.php`**

```php
<?php

namespace App\Forms\Types;

use App\Forms\Mail\ContactAdminMail;
use App\Forms\Models\FormSubmission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

class ContactFormType extends FormType
{
    public function key(): string
    {
        return 'contact';
    }

    public function label(): string
    {
        return trans('forms.types.contact');
    }

    public function rules(?Model $subject = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('forms.fields.name'),
            'email' => trans('forms.fields.email'),
            'message' => trans('forms.fields.message'),
        ];
    }

    public function adminMailable(FormSubmission $submission): Mailable
    {
        return new ContactAdminMail($submission);
    }
}
```

- [ ] **Step 3: Create `app/Forms/Types/OrderFormType.php`**

```php
<?php

namespace App\Forms\Types;

use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Mail\Mailable;

class OrderFormType extends FormType
{
    public function key(): string
    {
        return 'order';
    }

    public function label(): string
    {
        return trans('forms.types.order');
    }

    public function subjectClass(): ?string
    {
        return Product::class;
    }

    public function subjectRequired(): bool
    {
        return true;
    }

    public function rules(?Model $subject = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'phone' => ['required', 'string', 'max:40'],
            'email' => ['required', 'string', 'email:rfc', 'max:255'],
            'qty' => ['required', 'integer', 'min:1', 'max:100'],
            'note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => trans('forms.fields.name'),
            'phone' => trans('forms.fields.phone'),
            'email' => trans('forms.fields.email'),
            'qty' => trans('forms.fields.qty'),
            'note' => trans('forms.fields.note'),
        ];
    }

    public function adminMailable(FormSubmission $submission): Mailable
    {
        return new OrderAdminMail($submission);
    }

    public function clientMailable(FormSubmission $submission): ?Mailable
    {
        return new OrderClientMail($submission);
    }
}
```

- [ ] **Step 4: Write the type tests**

`tests/Feature/Forms/FormTypeTest.php`:

```php
<?php

use App\Forms\Mail\ContactAdminMail;
use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Forms\Types\ContactFormType;
use App\Forms\Types\OrderFormType;
use App\Models\Catalogue\Product;

it('ContactFormType: key + label + rules + admin mailable', function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    $type = new ContactFormType;

    expect($type->key())->toBe('contact');
    expect($type->label())->not->toBeEmpty();
    expect($type->subjectRequired())->toBeFalse();
    expect($type->subjectClass())->toBeNull();
    expect($type->rules())->toHaveKeys(['name', 'email', 'message']);
    expect($type->adminRecipients())->toBe(['admin@levantparfums.test']);

    $submission = FormSubmission::factory()->make();
    expect($type->adminMailable($submission))->toBeInstanceOf(ContactAdminMail::class);
    expect($type->clientMailable($submission))->toBeNull();
});

it('OrderFormType: requires Product subject and emits admin + client mailables', function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    $type = new OrderFormType;

    expect($type->key())->toBe('order');
    expect($type->subjectRequired())->toBeTrue();
    expect($type->subjectClass())->toBe(Product::class);
    expect($type->rules())->toHaveKeys(['name', 'phone', 'email', 'qty']);

    $submission = FormSubmission::factory()->order()->make();
    expect($type->adminMailable($submission))->toBeInstanceOf(OrderAdminMail::class);
    expect($type->clientMailable($submission))->toBeInstanceOf(OrderClientMail::class);
});

it('adminRecipients filters out null FORMS_ADMIN_EMAIL', function () {
    config()->set('forms.admin_email', null);
    expect((new ContactFormType)->adminRecipients())->toBe([]);
});

it('rateLimit default is 5 attempts per 60 minutes', function () {
    expect((new ContactFormType)->rateLimit())->toBe([5, 60]);
});
```

- [ ] **Step 5: Run the tests**

Run: `php artisan test --filter=FormTypeTest`
Expected: 4 passing tests.

- [ ] **Step 6: Commit**

```bash
git add app/Forms/Types tests/Feature/Forms/FormTypeTest.php
git commit -m "forms: FormType abstract + Contact/Order types + tests"
```

---

## Task 7: `FormRateLimiter` support class + test

**Files:**
- Create: `app/Forms/Support/FormRateLimiter.php`
- Create: `tests/Feature/Forms/FormRateLimiterTest.php`

- [ ] **Step 1: Create `app/Forms/Support/FormRateLimiter.php`**

```php
<?php

namespace App\Forms\Support;

use App\Forms\Types\FormType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class FormRateLimiter
{
    public static function ensureAllowed(FormType $type, Request $request): void
    {
        [$maxAttempts, $decayMinutes] = $type->rateLimit();
        $key = self::keyFor($type, $request);

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            throw ValidationException::withMessages([
                'form' => trans('forms.errors.rate_limited'),
            ]);
        }

        RateLimiter::hit($key, $decayMinutes * 60);
    }

    public static function clear(FormType $type, Request $request): void
    {
        RateLimiter::clear(self::keyFor($type, $request));
    }

    private static function keyFor(FormType $type, Request $request): string
    {
        return 'forms:'.$type->key().':'.$request->ip();
    }
}
```

- [ ] **Step 2: Write the test**

`tests/Feature/Forms/FormRateLimiterTest.php`:

```php
<?php

use App\Forms\Support\FormRateLimiter;
use App\Forms\Types\ContactFormType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

beforeEach(function () {
    RateLimiter::clear('forms:contact:127.0.0.1');
});

it('allows up to N attempts and throws on N+1', function () {
    $type = new ContactFormType;
    $request = Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $request);
    }

    expect(fn () => FormRateLimiter::ensureAllowed($type, $request))
        ->toThrow(ValidationException::class);
});

it('uses translated message key forms.errors.rate_limited', function () {
    $type = new ContactFormType;
    $request = Request::create('/', server: ['REMOTE_ADDR' => '127.0.0.1']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $request);
    }

    try {
        FormRateLimiter::ensureAllowed($type, $request);
        expect(false)->toBeTrue('should have thrown');
    } catch (ValidationException $e) {
        expect($e->errors())->toHaveKey('form');
        expect($e->errors()['form'][0])->toBe(trans('forms.errors.rate_limited'));
    }
});

it('keys are isolated per IP', function () {
    $type = new ContactFormType;
    $ip1 = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.1']);
    $ip2 = Request::create('/', server: ['REMOTE_ADDR' => '10.0.0.2']);

    for ($i = 0; $i < 5; $i++) {
        FormRateLimiter::ensureAllowed($type, $ip1);
    }

    // Second IP still allowed.
    FormRateLimiter::ensureAllowed($type, $ip2);
    expect(true)->toBeTrue();

    RateLimiter::clear('forms:contact:10.0.0.1');
    RateLimiter::clear('forms:contact:10.0.0.2');
});
```

- [ ] **Step 3: Run the test**

Run: `php artisan test --filter=FormRateLimiterTest`
Expected: 3 passing tests.

- [ ] **Step 4: Commit**

```bash
git add app/Forms/Support tests/Feature/Forms/FormRateLimiterTest.php
git commit -m "forms: FormRateLimiter + tests"
```

---

## Task 8: Abstract `FormComponent` + honeypot partial

**Files:**
- Create: `app/Forms/Livewire/FormComponent.php`
- Create: `resources/views/components/forms/honeypot.blade.php`

No test in this task — the base class is exercised via the Contact and Order Livewire tests in Tasks 9-10.

- [ ] **Step 1: Create the honeypot Blade component**

`resources/views/components/forms/honeypot.blade.php`:

```blade
@props([])
<div aria-hidden="true" style="position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden">
    <label>
        Leave this field empty
        <input type="text" {{ $attributes }} tabindex="-1" autocomplete="off">
    </label>
</div>
```

- [ ] **Step 2: Create `app/Forms/Livewire/FormComponent.php`**

```php
<?php

namespace App\Forms\Livewire;

use App\Forms\Models\FormSubmission;
use App\Forms\Support\FormRateLimiter;
use App\Forms\Types\FormType;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;
use Livewire\Component;

abstract class FormComponent extends Component
{
    public ?Model $subject = null;
    public string $hp = '';

    abstract protected function formType(): FormType;
    abstract public function render(): View;

    public function mount(?Model $subject = null): void
    {
        $type = $this->formType();

        if ($type->subjectRequired()) {
            $expected = $type->subjectClass();
            if (! $subject instanceof $expected) {
                throw new LogicException("Form {$type->key()} requires subject of type {$expected}");
            }
        }

        $this->subject = $subject;
    }

    public function submit(): void
    {
        $type = $this->formType();

        if ($this->hp !== '') {
            logger()->info('forms.honeypot.tripped', [
                'type' => $type->key(),
                'ip' => request()->ip(),
            ]);
            return;
        }

        FormRateLimiter::ensureAllowed($type, request());

        $data = $this->validate($type->rules($this->subject), [], $type->attributes());

        $submission = FormSubmission::create([
            'type' => $type->key(),
            'status' => FormSubmission::STATUS_NEW,
            'data' => $data,
            'subject_type' => $this->subject?->getMorphClass(),
            'subject_id' => $this->subject?->getKey(),
            'meta' => [
                'url' => url()->previous(),
                'ip' => request()->ip(),
                'user_agent' => substr((string) request()->userAgent(), 0, 1000),
                'referer' => request()->headers->get('referer'),
            ],
            'locale' => LaravelLocalization::getCurrentLocale() ?: config('app.fallback_locale'),
        ]);

        $this->dispatchEmails($type, $submission);

        $this->reset($this->resetableFields());
        session()->flash("forms.success.{$type->key()}", true);
        $this->dispatch('form-submitted', type: $type->key());
    }

    protected function dispatchEmails(FormType $type, FormSubmission $submission): void
    {
        $adminRecipients = $type->adminRecipients();
        if ($adminRecipients !== []) {
            Mail::to($adminRecipients)
                ->locale(config('app.fallback_locale'))
                ->queue($type->adminMailable($submission));
        }

        if ($clientMail = $type->clientMailable($submission)) {
            $field = $type->clientEmailField();
            $address = $field ? data_get($submission->data, $field) : null;
            if ($address) {
                Mail::to($address)
                    ->locale($submission->locale ?: config('app.fallback_locale'))
                    ->queue($clientMail);
            }
        }
    }

    protected function resetableFields(): array
    {
        return array_diff(
            array_keys(get_object_vars($this)),
            ['subject', 'hp'],
        );
    }
}
```

- [ ] **Step 3: Sanity-check the file parses (no test yet — covered by Tasks 9-10)**

Run: `php -l app/Forms/Livewire/FormComponent.php`
Expected: `No syntax errors detected in app/Forms/Livewire/FormComponent.php`

- [ ] **Step 4: Commit**

```bash
git add app/Forms/Livewire/FormComponent.php resources/views/components/forms/honeypot.blade.php
git commit -m "forms: abstract FormComponent + honeypot partial"
```

---

## Task 9: `ContactForm` Livewire component + feature tests

**Files:**
- Create: `app/Forms/Livewire/ContactForm.php`
- Create: `resources/views/forms/contact.blade.php`
- Create: `tests/Feature/Forms/ContactFormTest.php`

- [ ] **Step 1: Create `app/Forms/Livewire/ContactForm.php`**

```php
<?php

namespace App\Forms\Livewire;

use App\Forms\Types\ContactFormType;
use App\Forms\Types\FormType;
use Illuminate\Contracts\View\View;

class ContactForm extends FormComponent
{
    public string $name = '';
    public string $email = '';
    public string $message = '';

    protected function formType(): FormType
    {
        return app(ContactFormType::class);
    }

    public function render(): View
    {
        return view('forms.contact');
    }
}
```

- [ ] **Step 2: Create a minimal placeholder view (developer fleshes this out later — must be sufficient for Livewire tests)**

`resources/views/forms/contact.blade.php`:

```blade
<form wire:submit="submit">
    <x-forms.honeypot wire:model="hp" />

    @error('form') <div data-testid="form-error">{{ $message }}</div> @enderror

    <label>
        <span>{{ trans('forms.fields.name') }}</span>
        <input type="text" wire:model="name">
        @error('name') <span data-testid="name-error">{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.email') }}</span>
        <input type="email" wire:model="email">
        @error('email') <span data-testid="email-error">{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.message') }}</span>
        <textarea wire:model="message"></textarea>
        @error('message') <span data-testid="message-error">{{ $message }}</span> @enderror
    </label>

    <button type="submit">Submit</button>

    @if (session('forms.success.contact'))
        <div data-testid="form-success">Thanks</div>
    @endif
</form>
```

- [ ] **Step 3: Write the feature tests**

`tests/Feature/Forms/ContactFormTest.php`:

```php
<?php

use App\Forms\Livewire\ContactForm;
use App\Forms\Mail\ContactAdminMail;
use App\Forms\Models\FormSubmission;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    Mail::fake();
    RateLimiter::clear('forms:contact:127.0.0.1');
});

it('valid submit creates row, queues admin mail, flashes success', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'ivan@example.test')
        ->set('message', 'Привiт')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    expect(FormSubmission::count())->toBe(1);
    $row = FormSubmission::first();
    expect($row->type)->toBe('contact');
    expect($row->data)->toMatchArray([
        'name' => 'Iван',
        'email' => 'ivan@example.test',
        'message' => 'Привiт',
    ]);
    expect($row->subject_type)->toBeNull();
    expect($row->subject_id)->toBeNull();

    Mail::assertQueued(ContactAdminMail::class, fn ($m) => $m->hasTo('admin@levantparfums.test')
        && $m->submission->is($row));
});

it('invalid email surfaces inline validation error and does not persist', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'not-an-email')
        ->set('message', 'Привiт')
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('honeypot tripped: no row, no mail, no errors', function () {
    Livewire::test(ContactForm::class)
        ->set('name', 'Iван')
        ->set('email', 'ivan@example.test')
        ->set('message', 'Привiт')
        ->set('hp', 'bot-filled-this')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('6th submit within window throws rate-limit error', function () {
    for ($i = 0; $i < 5; $i++) {
        Livewire::test(ContactForm::class)
            ->set('name', "User {$i}")
            ->set('email', "u{$i}@example.test")
            ->set('message', 'Hi')
            ->call('submit')
            ->assertHasNoErrors();
    }

    Livewire::test(ContactForm::class)
        ->set('name', 'Sixth')
        ->set('email', 'sixth@example.test')
        ->set('message', 'Hi')
        ->call('submit')
        ->assertHasErrors(['form']);

    expect(FormSubmission::count())->toBe(5);
});

it('locale is captured from current LaravelLocalization locale', function () {
    app(\Mcamara\LaravelLocalization\LaravelLocalization::class)->setLocale('en');

    Livewire::test(ContactForm::class)
        ->set('name', 'John')
        ->set('email', 'john@example.test')
        ->set('message', 'Hi')
        ->call('submit')
        ->assertHasNoErrors();

    expect(FormSubmission::first()->locale)->toBe('en');
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --filter=ContactFormTest`
Expected: 5 passing tests.

If `Livewire::test` cannot find the class: it is referenced by FQCN — registration is not required for tests, only for `<livewire:contact-form />` Blade syntax (handled in Task 11).

- [ ] **Step 5: Commit**

```bash
git add app/Forms/Livewire/ContactForm.php resources/views/forms/contact.blade.php tests/Feature/Forms/ContactFormTest.php
git commit -m "forms: ContactForm Livewire + feature tests"
```

---

## Task 10: `OrderForm` Livewire component + feature tests

**Files:**
- Create: `app/Forms/Livewire/OrderForm.php`
- Create: `resources/views/forms/order.blade.php`
- Create: `tests/Feature/Forms/OrderFormTest.php`

- [ ] **Step 1: Create `app/Forms/Livewire/OrderForm.php`**

```php
<?php

namespace App\Forms\Livewire;

use App\Forms\Types\FormType;
use App\Forms\Types\OrderFormType;
use Illuminate\Contracts\View\View;

class OrderForm extends FormComponent
{
    public string $name = '';
    public string $phone = '';
    public string $email = '';
    public int $qty = 1;
    public string $note = '';

    protected function formType(): FormType
    {
        return app(OrderFormType::class);
    }

    public function render(): View
    {
        return view('forms.order');
    }
}
```

- [ ] **Step 2: Create the placeholder view**

`resources/views/forms/order.blade.php`:

```blade
<form wire:submit="submit">
    <x-forms.honeypot wire:model="hp" />

    @error('form') <div data-testid="form-error">{{ $message }}</div> @enderror

    @if ($subject)
        <p data-testid="subject-name">{{ $subject->name ?? $subject->getKey() }}</p>
    @endif

    <label>
        <span>{{ trans('forms.fields.name') }}</span>
        <input type="text" wire:model="name">
        @error('name') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.phone') }}</span>
        <input type="text" wire:model="phone">
        @error('phone') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.email') }}</span>
        <input type="email" wire:model="email">
        @error('email') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.qty') }}</span>
        <input type="number" min="1" wire:model="qty">
        @error('qty') <span>{{ $message }}</span> @enderror
    </label>

    <label>
        <span>{{ trans('forms.fields.note') }}</span>
        <textarea wire:model="note"></textarea>
    </label>

    <button type="submit">Submit</button>

    @if (session('forms.success.order'))
        <div data-testid="form-success">Thanks</div>
    @endif
</form>
```

- [ ] **Step 3: Write the feature tests**

`tests/Feature/Forms/OrderFormTest.php`:

```php
<?php

use App\Forms\Livewire\OrderForm;
use App\Forms\Mail\OrderAdminMail;
use App\Forms\Mail\OrderClientMail;
use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('forms.admin_email', 'admin@levantparfums.test');
    Mail::fake();
    RateLimiter::clear('forms:order:127.0.0.1');
});

it('mount without subject throws LogicException', function () {
    expect(fn () => Livewire::test(OrderForm::class))
        ->toThrow(LogicException::class);
});

it('mount with non-Product subject throws LogicException', function () {
    $article = Article::factory()->create();

    expect(fn () => Livewire::test(OrderForm::class, ['subject' => $article]))
        ->toThrow(LogicException::class);
});

it('valid submit persists subject morph and queues admin + client mails', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', 'ivan@example.test')
        ->set('qty', 2)
        ->set('note', 'Подзвонiть пiсля 18:00')
        ->call('submit')
        ->assertHasNoErrors()
        ->assertDispatched('form-submitted');

    $row = FormSubmission::first();
    expect($row->type)->toBe('order');
    expect($row->subject_type)->toBe($product->getMorphClass());
    expect((int) $row->subject_id)->toBe($product->getKey());
    expect($row->data)->toMatchArray([
        'name' => 'Iван',
        'phone' => '+380501234567',
        'email' => 'ivan@example.test',
        'qty' => 2,
    ]);

    Mail::assertQueued(OrderAdminMail::class, fn ($m) => $m->hasTo('admin@levantparfums.test'));
    Mail::assertQueued(OrderClientMail::class, fn ($m) => $m->hasTo('ivan@example.test'));
});

it('blank email blocks submission and queues no mail', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', '')
        ->set('qty', 2)
        ->call('submit')
        ->assertHasErrors(['email']);

    expect(FormSubmission::count())->toBe(0);
    Mail::assertNothingQueued();
});

it('ContactForm (clientMailable=null) emits exactly one mail — covers the null-clientMail branch', function () {
    // Cross-check that the base dispatch logic differentiates client-mail null vs. set.
    \Illuminate\Support\Facades\RateLimiter::clear('forms:contact:127.0.0.1');

    Livewire::test(\App\Forms\Livewire\ContactForm::class)
        ->set('name', 'X')
        ->set('email', 'x@example.test')
        ->set('message', 'hi')
        ->call('submit')
        ->assertHasNoErrors();

    Mail::assertQueuedCount(1);
});

it('qty must be a positive integer', function () {
    $product = Product::factory()->create();

    Livewire::test(OrderForm::class, ['subject' => $product])
        ->set('name', 'Iван')
        ->set('phone', '+380501234567')
        ->set('email', 'ivan@example.test')
        ->set('qty', 0)
        ->call('submit')
        ->assertHasErrors(['qty']);
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --filter=OrderFormTest`
Expected: 6 passing tests.

- [ ] **Step 5: Commit**

```bash
git add app/Forms/Livewire/OrderForm.php resources/views/forms/order.blade.php tests/Feature/Forms/OrderFormTest.php
git commit -m "forms: OrderForm Livewire + feature tests"
```

---

## Task 11: `FormSubmissionObserver` + Livewire registration + observer test

**Files:**
- Create: `app/Forms/Observers/FormSubmissionObserver.php`
- Modify: `app/Providers/AppServiceProvider.php`
- Create: `tests/Feature/Forms/FormSubmissionObserverTest.php`

- [ ] **Step 1: Create `app/Forms/Observers/FormSubmissionObserver.php`**

```php
<?php

namespace App\Forms\Observers;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Forms\Models\FormSubmission;
use App\Models\User;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;

class FormSubmissionObserver
{
    public function created(FormSubmission $submission): void
    {
        $title = trans('forms.notifications.new', [
            'type' => trans("forms.types.{$submission->type}"),
        ]);

        $url = class_exists(FormSubmissionResource::class)
            ? FormSubmissionResource::getUrl('view', ['record' => $submission])
            : null;

        $notification = Notification::make()
            ->title($title)
            ->icon('heroicon-o-inbox-arrow-down');

        if ($url !== null) {
            $notification->actions([
                Action::make('view')->url($url),
            ]);
        }

        $notification->sendToDatabase(User::all());
    }
}
```

Note: the `class_exists` guard keeps the observer functional during Task 11 even before the Filament resource class is created in Task 12 — the test below would otherwise need to be deferred.

- [ ] **Step 2: Read the current `app/Providers/AppServiceProvider.php`**

Run: `cat app/Providers/AppServiceProvider.php`

You should see the default scaffold. Replace the file with the version below (preserving any project-specific edits — if the file diverges from the Laravel default, merge the additions into the existing `boot()`).

`app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Forms\Livewire\ContactForm;
use App\Forms\Livewire\OrderForm;
use App\Forms\Models\FormSubmission;
use App\Forms\Observers\FormSubmissionObserver;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        FormSubmission::observe(FormSubmissionObserver::class);

        Livewire::component('contact-form', ContactForm::class);
        Livewire::component('order-form', OrderForm::class);
    }
}
```

- [ ] **Step 3: Write the observer test**

`tests/Feature/Forms/FormSubmissionObserverTest.php`:

```php
<?php

use App\Forms\Models\FormSubmission;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Notifications\DatabaseNotification;

beforeEach(function () {
    User::factory()->create(['email' => 'admin1@levantparfums.test']);
    User::factory()->create(['email' => 'admin2@levantparfums.test']);
});

it('creating a submission sends a database notification to every user', function () {
    expect(DatabaseNotification::count())->toBe(0);

    FormSubmission::factory()->create(['type' => 'contact']);

    expect(DatabaseNotification::count())->toBe(2);
    expect(DatabaseNotification::query()->pluck('notifiable_id')->sort()->values()->toArray())
        ->toBe(User::query()->orderBy('id')->pluck('id')->toArray());
});

it('notification title includes the translated type label', function () {
    FormSubmission::factory()->create(['type' => 'order']);

    $payload = DatabaseNotification::first()->data;
    expect($payload['title'])->toContain(trans('forms.types.order'));
});
```

- [ ] **Step 4: Run the tests**

Run: `php artisan test --filter=FormSubmissionObserverTest`
Expected: 2 passing tests.

If the test fails with «table notifications doesn't exist»: Filament 5 expects the `notifications` table to be migrated. Check `database/migrations` for the standard Laravel `create_notifications_table` migration — if absent, run `php artisan notifications:table` and `php artisan migrate`, then retry.

- [ ] **Step 5: Commit**

```bash
git add app/Forms/Observers/FormSubmissionObserver.php app/Providers/AppServiceProvider.php tests/Feature/Forms/FormSubmissionObserverTest.php
git commit -m "forms: observer fans out admin notifications + register Livewire components"
```

---

## Task 12: Filament `FormSubmissionResource` (List + View + Table + Infolist) + nav registration

**Files:**
- Create: `app/Filament/Resources/FormSubmissions/FormSubmissionResource.php`
- Create: `app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php`
- Create: `app/Filament/Resources/FormSubmissions/Schemas/FormSubmissionInfolist.php`
- Create: `app/Filament/Resources/FormSubmissions/Pages/ListFormSubmissions.php`
- Create: `app/Filament/Resources/FormSubmissions/Pages/ViewFormSubmission.php`
- Modify: `app/Providers/Filament/AdminPanelProvider.php`
- Create: `tests/Feature/Forms/Filament/FormSubmissionsResourceTest.php`

- [ ] **Step 1: Create `app/Filament/Resources/FormSubmissions/FormSubmissionResource.php`**

```php
<?php

namespace App\Filament\Resources\FormSubmissions;

use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Filament\Resources\FormSubmissions\Schemas\FormSubmissionInfolist;
use App\Filament\Resources\FormSubmissions\Tables\FormSubmissionsTable;
use App\Forms\Models\FormSubmission;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables\Table;

class FormSubmissionResource extends Resource
{
    protected static ?string $model = FormSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-envelope';

    protected static ?int $navigationSort = 3;

    public static function getNavigationGroup(): ?string
    {
        return trans('forms.navigation.group');
    }

    public static function getModelLabel(): string
    {
        return trans('forms.resource.label');
    }

    public static function getPluralModelLabel(): string
    {
        return trans('forms.resource.plural');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = FormSubmission::query()->where('status', FormSubmission::STATUS_NEW)->count();
        return $count > 0 ? (string) $count : null;
    }

    public static function table(Table $table): Table
    {
        return FormSubmissionsTable::configure($table);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return FormSubmissionInfolist::configure($infolist);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFormSubmissions::route('/'),
            'view' => ViewFormSubmission::route('/{record}'),
        ];
    }
}
```

- [ ] **Step 2: Create `app/Filament/Resources/FormSubmissions/Tables/FormSubmissionsTable.php`**

```php
<?php

namespace App\Filament\Resources\FormSubmissions\Tables;

use App\Forms\Models\FormSubmission;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class FormSubmissionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('type')
                    ->label(trans('forms.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.types.{$state}"))
                    ->sortable(),

                TextColumn::make('status')
                    ->label(trans('forms.fields.status'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        FormSubmission::STATUS_NEW => 'warning',
                        FormSubmission::STATUS_READ => 'info',
                        FormSubmission::STATUS_PROCESSED => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state) => trans("forms.statuses.{$state}"))
                    ->sortable(),

                TextColumn::make('summary')
                    ->label(trans('forms.fields.summary'))
                    ->state(function (FormSubmission $record): string {
                        $name = $record->data['name'] ?? null;
                        $email = $record->data['email'] ?? null;
                        if ($name && $email) {
                            return "{$name} <{$email}>";
                        }
                        return $name
                            ?? $email
                            ?? Str::limit((string) ($record->data['message'] ?? ''), 60);
                    })
                    ->wrap(),

                TextColumn::make('subject')
                    ->label(trans('forms.fields.subject'))
                    ->state(function (FormSubmission $record): ?string {
                        $s = $record->subject;
                        if (! $s) {
                            return null;
                        }
                        $label = is_array($s->name ?? null) ? ($s->name[app()->getLocale()] ?? null) : ($s->name ?? null);
                        return $label ?? class_basename($s).'#'.$s->getKey();
                    })
                    ->placeholder('—'),

                TextColumn::make('created_at')
                    ->label(trans('forms.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label(trans('forms.fields.type'))
                    ->options(collect(config('forms.types'))
                        ->map(fn (string $cls) => app($cls))
                        ->mapWithKeys(fn ($t) => [$t->key() => $t->label()])
                        ->all()),

                SelectFilter::make('status')
                    ->label(trans('forms.fields.status'))
                    ->options(collect(FormSubmission::STATUSES)
                        ->mapWithKeys(fn (string $s) => [$s => trans("forms.statuses.{$s}")])
                        ->all()),

                Filter::make('created_at')
                    ->form([
                        \Filament\Forms\Components\DatePicker::make('from'),
                        \Filament\Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['from'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['until'] ?? null, fn (Builder $q, $d) => $q->whereDate('created_at', '<=', $d));
                    }),
            ])
            ->recordActions([
                ViewAction::make(),
                Action::make('mark_read')
                    ->label(trans('forms.actions.mark_read'))
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->visible(fn (FormSubmission $r) => $r->status === FormSubmission::STATUS_NEW)
                    ->action(fn (FormSubmission $r) => $r->markRead()),
                Action::make('mark_processed')
                    ->label(trans('forms.actions.mark_processed'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn (FormSubmission $r) => $r->status !== FormSubmission::STATUS_PROCESSED)
                    ->action(fn (FormSubmission $r) => $r->markProcessed()),
                Action::make('mark_new')
                    ->label(trans('forms.actions.mark_new'))
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->visible(fn (FormSubmission $r) => $r->status !== FormSubmission::STATUS_NEW)
                    ->action(fn (FormSubmission $r) => $r->markNew()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('bulk_mark_read')
                        ->label(trans('forms.actions.mark_read'))
                        ->action(fn (Collection $records) => $records->each->markRead()),
                    BulkAction::make('bulk_mark_processed')
                        ->label(trans('forms.actions.mark_processed'))
                        ->action(fn (Collection $records) => $records->each->markProcessed()),
                ]),
            ]);
    }
}
```

- [ ] **Step 3: Create `app/Filament/Resources/FormSubmissions/Schemas/FormSubmissionInfolist.php`**

```php
<?php

namespace App\Filament\Resources\FormSubmissions\Schemas;

use App\Forms\Models\FormSubmission;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;

class FormSubmissionInfolist
{
    public static function configure(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Section::make()->schema([
                TextEntry::make('type')
                    ->label(trans('forms.fields.type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.types.{$state}")),

                TextEntry::make('status')
                    ->label(trans('forms.fields.status'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => trans("forms.statuses.{$state}")),

                TextEntry::make('created_at')
                    ->label(trans('forms.fields.created_at'))
                    ->dateTime(),

                TextEntry::make('subject')
                    ->label(trans('forms.fields.subject'))
                    ->state(function (FormSubmission $record): ?string {
                        $s = $record->subject;
                        if (! $s) return null;
                        return class_basename($s).'#'.$s->getKey();
                    })
                    ->placeholder('—'),
            ])->columns(2),

            Section::make(trans('forms.fields.data'))->schema([
                TextEntry::make('data')
                    ->state(fn (FormSubmission $record): string => self::formatKeyValue($record->data ?? [])),
            ]),

            Section::make(trans('forms.fields.meta'))->schema([
                TextEntry::make('meta')
                    ->state(fn (FormSubmission $record): string => self::formatKeyValue($record->meta ?? [])),
                TextEntry::make('locale')->label(trans('forms.fields.locale')),
                TextEntry::make('handled_at')->label(trans('forms.fields.handled_at'))->dateTime()->placeholder('—'),
            ])->columns(2)->collapsed(),
        ]);
    }

    private static function formatKeyValue(array $data): string
    {
        if ($data === []) return '—';
        $lines = [];
        foreach ($data as $k => $v) {
            $value = is_scalar($v) ? (string) $v : json_encode($v, JSON_UNESCAPED_UNICODE);
            $lines[] = "**{$k}:** {$value}";
        }
        return implode("\n\n", $lines);
    }
}
```

- [ ] **Step 4: Create `app/Filament/Resources/FormSubmissions/Pages/ListFormSubmissions.php`**

```php
<?php

namespace App\Filament\Resources\FormSubmissions\Pages;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use Filament\Resources\Pages\ListRecords;

class ListFormSubmissions extends ListRecords
{
    protected static string $resource = FormSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
```

- [ ] **Step 5: Create `app/Filament/Resources/FormSubmissions/Pages/ViewFormSubmission.php`**

```php
<?php

namespace App\Filament\Resources\FormSubmissions\Pages;

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Forms\Models\FormSubmission;
use Filament\Actions\Action;
use Filament\Resources\Pages\ViewRecord;

class ViewFormSubmission extends ViewRecord
{
    protected static string $resource = FormSubmissionResource::class;

    protected function getHeaderActions(): array
    {
        /** @var FormSubmission $record */
        $record = $this->record;

        return [
            Action::make('mark_read')
                ->label(trans('forms.actions.mark_read'))
                ->visible(fn () => $record->status === FormSubmission::STATUS_NEW)
                ->action(fn () => $record->markRead()),

            Action::make('mark_processed')
                ->label(trans('forms.actions.mark_processed'))
                ->visible(fn () => $record->status !== FormSubmission::STATUS_PROCESSED)
                ->action(fn () => $record->markProcessed()),

            Action::make('mark_new')
                ->label(trans('forms.actions.mark_new'))
                ->visible(fn () => $record->status !== FormSubmission::STATUS_NEW)
                ->action(fn () => $record->markNew()),
        ];
    }
}
```

- [ ] **Step 6: Modify `app/Providers/Filament/AdminPanelProvider.php` — add the navigation group after `content`**

Read the current file first:

Run: `cat app/Providers/Filament/AdminPanelProvider.php`

Find the `navigationGroups([...])` block. Insert a new entry between the `content` group and the `attributes` group:

```php
\Filament\Navigation\NavigationGroup::make()
    ->label(fn () => trans('forms.navigation.group')),
```

The block should look like this after editing:

```php
->navigationGroups([
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('catalogue.navigation.catalogue')),
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('content.navigation.group')),
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('forms.navigation.group')),
    \Filament\Navigation\NavigationGroup::make()
        ->label(fn () => trans('catalogue.navigation.attributes'))
        ->collapsed(),
])
```

- [ ] **Step 7: Write the Filament resource tests**

`tests/Feature/Forms/Filament/FormSubmissionsResourceTest.php`:

```php
<?php

use App\Filament\Resources\FormSubmissions\FormSubmissionResource;
use App\Filament\Resources\FormSubmissions\Pages\ListFormSubmissions;
use App\Filament\Resources\FormSubmissions\Pages\ViewFormSubmission;
use App\Forms\Models\FormSubmission;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('list page renders', function () {
    FormSubmission::factory()->count(3)->create();

    Livewire::test(ListFormSubmissions::class)
        ->assertOk()
        ->assertCanSeeTableRecords(FormSubmission::all());
});

it('resource exposes only index and view pages', function () {
    $pages = FormSubmissionResource::getPages();
    expect(array_keys($pages))->toBe(['index', 'view']);
});

it('view page renders for a record', function () {
    $row = FormSubmission::factory()->create();

    Livewire::test(ViewFormSubmission::class, ['record' => $row->getKey()])
        ->assertOk();
});

it('mark_read row action transitions new -> read', function () {
    $row = FormSubmission::factory()->create(['status' => FormSubmission::STATUS_NEW]);

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_read', $row);

    expect($row->fresh()->status)->toBe(FormSubmission::STATUS_READ);
});

it('mark_processed sets status and handled_at', function () {
    $row = FormSubmission::factory()->create(['status' => FormSubmission::STATUS_READ, 'handled_at' => null]);

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_processed', $row);

    $fresh = $row->fresh();
    expect($fresh->status)->toBe(FormSubmission::STATUS_PROCESSED);
    expect($fresh->handled_at)->not->toBeNull();
});

it('mark_new reverts status and clears handled_at', function () {
    $row = FormSubmission::factory()->state(['status' => FormSubmission::STATUS_PROCESSED, 'handled_at' => now()])->create();

    Livewire::test(ListFormSubmissions::class)
        ->callTableAction('mark_new', $row);

    $fresh = $row->fresh();
    expect($fresh->status)->toBe(FormSubmission::STATUS_NEW);
    expect($fresh->handled_at)->toBeNull();
});

it('filter by type narrows the list', function () {
    FormSubmission::factory()->count(2)->create(['type' => 'contact']);
    FormSubmission::factory()->count(3)->order()->create(['type' => 'order']);

    Livewire::test(ListFormSubmissions::class)
        ->filterTable('type', 'order')
        ->assertCanSeeTableRecords(FormSubmission::query()->where('type', 'order')->get())
        ->assertCanNotSeeTableRecords(FormSubmission::query()->where('type', 'contact')->get());
});

it('navigation badge equals count of new submissions', function () {
    FormSubmission::factory()->count(2)->create(['status' => FormSubmission::STATUS_NEW]);
    FormSubmission::factory()->count(3)->create(['status' => FormSubmission::STATUS_READ]);

    expect(FormSubmissionResource::getNavigationBadge())->toBe('2');
});
```

- [ ] **Step 8: Run the Filament tests**

Run: `php artisan test --filter=FormSubmissionsResourceTest`
Expected: 8 passing tests.

If any test fails on Filament's `callTableAction` signature, the established style for this project is `Livewire::test($listPage)->callTableAction($name, $record)` — confirmed by `tests/Feature/Catalogue/Filament/ProductResourceTest.php` and `tests/Feature/Content/Filament/ArticleResourceTest.php`. If the signature has drifted, mirror whichever pattern those files use.

- [ ] **Step 9: Commit**

```bash
git add app/Filament/Resources/FormSubmissions app/Providers/Filament/AdminPanelProvider.php tests/Feature/Forms/Filament/FormSubmissionsResourceTest.php
git commit -m "forms: Filament inbox resource + read-only pages + tests"
```

---

## Task 13: Seeder + DatabaseSeeder integration

**Files:**
- Create: `database/seeders/Forms/FormSubmissionSeeder.php`
- Modify: `database/seeders/DatabaseSeeder.php`

- [ ] **Step 1: Read the existing `DatabaseSeeder.php` to learn the pattern**

Run: `cat database/seeders/DatabaseSeeder.php`

Note where the `Content` seeders are called — the new seeder goes after them.

- [ ] **Step 2: Create `database/seeders/Forms/FormSubmissionSeeder.php`**

```php
<?php

namespace Database\Seeders\Forms;

use App\Forms\Models\FormSubmission;
use App\Models\Catalogue\Product;
use Illuminate\Database\Seeder;

class FormSubmissionSeeder extends Seeder
{
    public function run(): void
    {
        // 5 contact submissions: mix of statuses
        FormSubmission::factory()->count(2)->create();
        FormSubmission::factory()->status(FormSubmission::STATUS_READ)->count(2)->create();
        FormSubmission::factory()->status(FormSubmission::STATUS_PROCESSED)->count(1)->create();

        // 5 order submissions tied to real products (half new, half processed)
        $products = Product::query()->inRandomOrder()->limit(5)->get();
        if ($products->isEmpty()) {
            return;
        }

        foreach ($products as $i => $product) {
            $status = $i < 3 ? FormSubmission::STATUS_NEW : FormSubmission::STATUS_PROCESSED;
            FormSubmission::factory()
                ->order()
                ->status($status)
                ->create([
                    'subject_type' => $product->getMorphClass(),
                    'subject_id' => $product->getKey(),
                ]);
        }
    }
}
```

- [ ] **Step 3: Register in `DatabaseSeeder`**

In `database/seeders/DatabaseSeeder.php`, after the existing `Content` seeder calls, append a `$this->call(\Database\Seeders\Forms\FormSubmissionSeeder::class);` line. Keep formatting consistent with existing calls.

- [ ] **Step 4: Run the seeder against a fresh DB**

Run: `php artisan migrate:fresh --seed`
Expected: clean run; no exceptions. Inspect:

Run: `php artisan tinker --execute='echo App\Forms\Models\FormSubmission::count();'`
Expected stdout: `10`

Run: `php artisan tinker --execute='echo App\Forms\Models\FormSubmission::where("type","order")->whereNotNull("subject_id")->count();'`
Expected stdout: `5`

- [ ] **Step 5: Commit**

```bash
git add database/seeders/Forms database/seeders/DatabaseSeeder.php
git commit -m "forms: seeder with demo submissions"
```

---

## Task 14: CLAUDE.md notes + final verification

**Files:**
- Modify: `CLAUDE.md`

- [ ] **Step 1: Read the current `CLAUDE.md`**

Run: `cat CLAUDE.md`

Locate the section «Architecture notes that span multiple files». You will append a new subsection after the existing «Media (Spatie MediaLibrary)» subsection.

- [ ] **Step 2: Append the new subsection**

Insert this block in `CLAUDE.md` directly after the «Media (Spatie MediaLibrary)» subsection:

```markdown
### Forms subsystem (`App\Forms\*`)

Public forms (contact, order, future) are handled by a single engine:

1. **One row per submission** — `form_submissions` table with JSON `data` + JSON `meta` + polymorphic `subject` + workflow `status` (`new` / `read` / `processed`). See `App\Forms\Models\FormSubmission`.
2. **One PHP class per form type** — extend `App\Forms\Types\FormType` (`key()`, `label()`, `rules()`, `attributes()`, `adminMailable()`, optional `clientMailable()` and `subjectClass()`). Register the class in `config('forms.types')`.
3. **One Livewire component per form** — extend `App\Forms\Livewire\FormComponent`, declare public properties for fields, override `formType()` and `render()`. The base owns: honeypot, rate-limit, validation, persistence, email dispatch, locale capture. Components live under `app/Forms/Livewire/`, so they must be registered with `Livewire::component()` in `AppServiceProvider::boot()` for `<livewire:my-form />` Blade syntax to work.
4. **One Blade view per form** under `resources/views/forms/{key}.blade.php` — the developer writes the markup; the only mandatory element is `<x-forms.honeypot wire:model="hp" />`.
5. **Per-type Mailable + Markdown template** under `App\Forms\Mail\*` and `resources/views/emails/forms/{key}-{admin|client}.blade.php`. Admin mail goes in `config('app.fallback_locale')`; client mail goes in the submission's captured locale.
6. **Admin sees everything in one inbox** — `FormSubmissionResource` (read-only: only List + View). New rows fan out Filament database notifications to all admin users via `FormSubmissionObserver` (wired in `AppServiceProvider::boot()`).

Anti-spam: silent honeypot (an empty `$hp` is the only acceptable value) + Laravel `RateLimiter` keyed on `forms:{type}:{ip}`. Rate-limit breach surfaces as a `ValidationException` on the `form` key so it shows up inline like any other field error.
```

- [ ] **Step 3: Run the entire test suite end-to-end**

Run: `composer test`
Expected: all tests pass, including the new ~30 Forms tests added in Tasks 3-12.

If anything fails: read the failing assertion message carefully. The most common failure mode here is a stale `bootstrap/cache/config.php` — run `php artisan config:clear` and retry. The project's `composer test` script does `config:clear` automatically, but in some setups a stale `bootstrap/cache/services.php` survives — `php artisan optimize:clear` is the bigger hammer.

- [ ] **Step 4: Manual smoke test (admin UI)**

Run: `php artisan migrate:fresh --seed`
Run: `composer dev` (this starts `artisan serve`, queue listener, pail, and vite)

In a browser, open `http://127.0.0.1:8000/admin`, log in as `admin@levantparfums.test` / `password`. Verify:
- The new «Заявки» / «Submissions» group appears in the sidebar.
- The badge on the «Заявки» resource matches the count of `status='new'` rows from the seeder.
- Clicking into a row opens the View page with sections for header, data, meta.
- Status row-actions transition the status as expected and update the badge.
- The bell-icon notification panel shows entries for the seeded submissions (because the observer fires on `created` — including factory-created rows).

Stop the dev server with Ctrl+C when done.

- [ ] **Step 5: Manual smoke test (Livewire submit via tinker, no browser needed)**

Run:

```bash
php artisan tinker --execute='
$type = app(App\Forms\Types\ContactFormType::class);
$row = App\Forms\Models\FormSubmission::create([
    "type" => $type->key(),
    "data" => ["name" => "Smoke", "email" => "smoke@x.test", "message" => "Hi"],
    "meta" => [],
    "locale" => "uk",
]);
echo "id=", $row->id, " status=", $row->status;
'
```

Expected stdout: `id=N status=new` (any non-zero N).

- [ ] **Step 6: Final commit**

```bash
git add CLAUDE.md
git commit -m "forms: CLAUDE.md notes — engine + add-new-form-type recipe"
```

---

## Verification checklist (end-to-end)

When all 14 tasks are complete, the following should all be true:

1. `composer test` — green.
2. `php artisan migrate:fresh --seed` — exits 0; admin DB shows ~10 submissions across both types.
3. Admin panel at `/admin` shows a «Заявки» group with the FormSubmissions resource; navigation badge reflects unhandled count.
4. Submitting a ContactForm via `Livewire::test(...)` enqueues exactly one `ContactAdminMail`; submitting OrderForm with a Product subject enqueues both `OrderAdminMail` and `OrderClientMail`.
5. Hitting the same IP 6 times within an hour triggers a `ValidationException` on the `form` error bag key.
6. Honeypot field non-empty → no row created, no mail queued, no error.
7. The View page lists `data`, `meta`, `locale`, `handled_at` and offers all three status actions according to the current state.
8. Filament bell-icon notifications appear for every admin user when a new submission lands.

If all eight pass, the engine is ready for the first storefront form to be wired up.
