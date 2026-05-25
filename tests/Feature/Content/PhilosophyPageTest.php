<?php

use App\Enums\PageTemplate;
use App\Models\Content\Page;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

function makePhilosophyPage(array $overrides = []): Page
{
    $blocks = $overrides['blocks'] ?? [
        [
            'type' => 'about_hero',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Про дім', 'en' => 'About the house'],
                'title' => [
                    'uk' => 'Парфумерний дім на перетині трьох світів',
                    'en' => 'A perfume house at the crossing of three worlds',
                ],
                'lead' => ['uk' => 'Коротко про нас.', 'en' => 'About us in short.'],
                'body' => ['uk' => 'Levant — давня назва регіону.', 'en' => 'Levant is the ancient name of a region.'],
                'image_path' => null,
                'stats' => [
                    ['num' => '22', 'meta_label' => ['uk' => 'композиції',  'en' => 'compositions']],
                    ['num' => '2',  'meta_label' => ['uk' => 'колекції',    'en' => 'collections']],
                    ['num' => '3',  'meta_label' => ['uk' => 'країни',      'en' => 'countries']],
                    ['num' => '20', 'meta_label' => ['uk' => 'років школи', 'en' => 'years of school']],
                ],
            ],
        ],
        [
            'type' => 'text',
            'data' => [
                'is_visible' => true,
                'anchor' => 'manifesto',
                'eyebrow' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                'title' => ['uk' => 'Манифест-заголовок', 'en' => 'Manifesto title'],
                'body' => ['uk' => 'Текст манифеста.', 'en' => 'Manifesto body.'],
            ],
        ],
        [
            'type' => 'brand_story',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Три точки', 'en' => 'Three points'],
                'title' => ['uk' => 'Перетин трьох світів', 'en' => 'Crossing of three worlds'],
                'pillars' => [
                    ['pillar_label' => ['uk' => 'Іспанія',   'en' => 'Spain'],   'pillar_caption' => ['uk' => 'Ідея',  'en' => 'Idea']],
                    ['pillar_label' => ['uk' => 'Туреччина', 'en' => 'Turkey'],  'pillar_caption' => ['uk' => 'Розлив', 'en' => 'Bottling']],
                    ['pillar_label' => ['uk' => 'Україна',   'en' => 'Ukraine'], 'pillar_caption' => ['uk' => 'Душа',  'en' => 'Soul']],
                ],
            ],
        ],
    ];

    return Page::factory()->create(array_merge([
        'template' => PageTemplate::Landing,
        'is_homepage' => false,
        'is_published' => true,
        'slug' => ['uk' => 'filosofiia', 'en' => 'philosophy'],
        'title' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
        'content' => null,
        'blocks' => $blocks,
    ], array_diff_key($overrides, ['blocks' => true])));
}

it('renders the Philosophy page at the uk slug', function () {
    makePhilosophyPage();

    $this->get('/filosofiia')
        ->assertOk()
        ->assertSee('Парфумерний дім на перетині трьох світів')
        ->assertSee('Іспанія')
        ->assertSee('Манифест-заголовок');
});

it('renders the Philosophy page at the en slug', function () {
    makePhilosophyPage();

    $this->withHeaders(['Accept-Language' => 'en'])
        ->get('/en/philosophy')
        ->assertOk()
        ->assertSee('A perfume house at the crossing of three worlds')
        ->assertSee('Spain')
        ->assertSee('Manifesto title');
});

it('hides the about_hero block when is_visible is false', function () {
    makePhilosophyPage([
        'blocks' => [
            [
                'type' => 'about_hero',
                'data' => [
                    'is_visible' => false,
                    'title' => ['uk' => 'HIDDEN-HERO-TITLE', 'en' => 'HIDDEN-HERO-TITLE'],
                ],
            ],
            [
                'type' => 'text',
                'data' => [
                    'is_visible' => true,
                    'body' => ['uk' => 'VISIBLE-MANIFESTO', 'en' => 'VISIBLE-MANIFESTO'],
                ],
            ],
        ],
    ]);

    $this->get('/filosofiia')
        ->assertOk()
        ->assertDontSee('HIDDEN-HERO-TITLE')
        ->assertSee('VISIBLE-MANIFESTO');
});

it('renders all four stats from the about_hero block', function () {
    makePhilosophyPage();

    $response = $this->get('/filosofiia');
    $response->assertOk();

    foreach (['22', '2', '3', '20'] as $num) {
        $response->assertSee($num);
    }
    $response->assertSee('композиції');
});

it('exposes a Philosophy link in the header nav', function () {
    makePhilosophyPage();

    $expectedUrl = route('page.show', ['slug' => config('content.philosophy_slug')['uk']]);

    $this->get('/')
        ->assertOk()
        ->assertSee($expectedUrl, escape: false)
        ->assertSee('Філософія');
});
