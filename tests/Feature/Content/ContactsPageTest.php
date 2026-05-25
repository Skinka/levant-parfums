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
