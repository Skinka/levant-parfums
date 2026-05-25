<?php

use App\Enums\PageTemplate;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Mcamara\LaravelLocalization\LaravelLocalization;

// Pin the locale negotiation to `uk` so the LocaleSessionRedirect middleware
// does not 302 us off to /en. With hideDefaultLocaleInURL=true, the homepage
// for the default locale (uk) is at `/`, and `/en` for the secondary locale.
beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

afterEach(function () {
    putenv(LaravelLocalization::ENV_ROUTE_KEY);
});

function makeHomepage(array $blocks): Page
{
    return Page::query()->updateOrCreate(
        ['is_homepage' => true],
        [
            'slug' => ['uk' => 'home-uk', 'en' => 'home-en'],
            'title' => ['uk' => 'Головна', 'en' => 'Home'],
            'intro' => ['uk' => '', 'en' => ''],
            'content' => null,
            'is_published' => true,
            'template' => PageTemplate::Landing,
            'blocks' => $blocks,
        ],
    );
}

it('renders the hero block with editorial split markup', function () {
    makeHomepage([
        [
            'type' => 'hero',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Колекція 2026', 'en' => 'Collection 2026'],
                'title_top' => ['uk' => 'Нішевий аромат.', 'en' => 'Niche fragrance.'],
                'title_bottom' => ['uk' => 'Чесна ціна.', 'en' => 'Honest price.'],
                'lead' => ['uk' => 'Lead UK', 'en' => 'Lead EN'],
                'meta' => [
                    ['num' => '22', 'meta_label' => ['uk' => 'Композиції', 'en' => 'Compositions']],
                    ['num' => '2', 'meta_label' => ['uk' => 'Серії', 'en' => 'Series']],
                    ['num' => '3', 'meta_label' => ['uk' => 'Країни', 'en' => 'Countries']],
                ],
            ],
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('class="hero', false)
        ->assertSee('Колекція 2026')
        ->assertSee('Нішевий аромат.')
        ->assertSee('Чесна ціна.')
        ->assertSee('class="num">22<', false);
});

it('renders the manifesto (text) block', function () {
    makeHomepage([
        [
            'type' => 'text',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Філософія', 'en' => 'Philosophy'],
                'title' => ['uk' => 'Заголовок маніфесту', 'en' => 'Manifesto title'],
                'body' => ['uk' => "Перший абзац.\n\nДругий абзац.", 'en' => "First.\n\nSecond."],
                'signature' => ['uk' => '— Команда', 'en' => '— Team'],
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="manifesto', false);
    $response->assertSee('Філософія');
    $response->assertSee('Заголовок маніфесту');
    $response->assertSee('Перший абзац.');
    $response->assertSee('Другий абзац.');
    $response->assertSee('— Команда');
});

it('renders the brand_story block with three pillars', function () {
    makeHomepage([
        [
            'type' => 'brand_story',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Три точки', 'en' => 'Three points'],
                'title' => ['uk' => 'Перетин трьох світів', 'en' => 'Three worlds'],
                'body' => ['uk' => 'Опис', 'en' => 'Body'],
                'pillars' => [
                    ['pillar_label' => ['uk' => 'Іспанія', 'en' => 'Spain'], 'pillar_caption' => ['uk' => 'Ідея', 'en' => 'Idea']],
                    ['pillar_label' => ['uk' => 'Туреччина', 'en' => 'Turkey'], 'pillar_caption' => ['uk' => 'Розлив', 'en' => 'Bottling']],
                    ['pillar_label' => ['uk' => 'Україна', 'en' => 'Ukraine'], 'pillar_caption' => ['uk' => 'Ринок', 'en' => 'Market']],
                ],
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="threepoints', false);
    $response->assertSee('Іспанія');
    $response->assertSee('Туреччина');
    $response->assertSee('Україна');
    expect(substr_count($response->getContent(), 'class="conn"'))->toBe(2);
});

it('renders the series_duo block with safe series lookup', function () {
    Series::query()->updateOrCreate(['slug' => 'luxury'], ['name' => ['uk' => 'Luxury', 'en' => 'Luxury'], 'theme_class' => 'theme-cream']);

    makeHomepage([
        [
            'type' => 'series_duo',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Колекції', 'en' => 'Collections'],
                'title' => ['uk' => 'Дві серії', 'en' => 'Two series'],
                'items' => [
                    [
                        'series_id' => Series::where('slug', 'luxury')->value('id'),
                        'kicker' => ['uk' => '17 ароматів', 'en' => '17 fragrances'],
                        'title' => ['uk' => 'Luxury Series', 'en' => 'Luxury Series'],
                        'description' => ['uk' => 'Опис', 'en' => 'Desc'],
                        'cta_label' => ['uk' => 'Перейти', 'en' => 'Open'],
                    ],
                    [
                        'series_id' => 999999,
                        'kicker' => ['uk' => 'X', 'en' => 'X'],
                        'title' => ['uk' => 'Onyx', 'en' => 'Onyx'],
                        'description' => ['uk' => 'D', 'en' => 'D'],
                        'cta_label' => ['uk' => 'Перейти', 'en' => 'Open'],
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="collections', false);
    $response->assertSee('Luxury Series');
    $response->assertSee('Onyx');
});

it('renders the pillars block tinted when surface is tinted', function () {
    makeHomepage([
        [
            'type' => 'pillars',
            'data' => [
                'is_visible' => true,
                'surface' => 'tinted',
                'eyebrow' => ['uk' => 'Гід', 'en' => 'Guide'],
                'title' => ['uk' => 'Три кроки', 'en' => 'Three steps'],
                'items' => [
                    ['eyebrow' => ['uk' => '01', 'en' => '01'], 'title' => ['uk' => 'A', 'en' => 'A'], 'body' => ['uk' => 'a', 'en' => 'a']],
                    ['eyebrow' => ['uk' => '02', 'en' => '02'], 'title' => ['uk' => 'B', 'en' => 'B'], 'body' => ['uk' => 'b', 'en' => 'b']],
                    ['eyebrow' => ['uk' => '03', 'en' => '03'], 'title' => ['uk' => 'C', 'en' => 'C'], 'body' => ['uk' => 'c', 'en' => 'c']],
                ],
            ],
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('class="pillars reveal is-tinted"', false)
        ->assertSee('data-count="3"', false);
});

it('renders the pillars block as advantages with data-count 4 and auto-numbers blank eyebrows', function () {
    makeHomepage([
        [
            'type' => 'pillars',
            'data' => [
                'is_visible' => true,
                'surface' => 'default',
                'eyebrow' => ['uk' => 'Чому ми', 'en' => 'Why us'],
                'title' => ['uk' => 'Чотири причини', 'en' => 'Four reasons'],
                'items' => [
                    ['title' => ['uk' => 'A', 'en' => 'A'], 'body' => ['uk' => 'a', 'en' => 'a']],
                    ['title' => ['uk' => 'B', 'en' => 'B'], 'body' => ['uk' => 'b', 'en' => 'b']],
                    ['title' => ['uk' => 'C', 'en' => 'C'], 'body' => ['uk' => 'c', 'en' => 'c']],
                    ['title' => ['uk' => 'D', 'en' => 'D'], 'body' => ['uk' => 'd', 'en' => 'd']],
                ],
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('data-count="4"', false);
    $response->assertSee('>01<', false);
    $response->assertSee('>04<', false);
});

it('renders the testimonials block as horizontal slider', function () {
    makeHomepage([
        [
            'type' => 'testimonials',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Відгуки', 'en' => 'Reviews'],
                'title' => ['uk' => 'Що пишуть', 'en' => 'What they say'],
                'items' => [
                    [
                        'quote' => ['uk' => 'Чудовий аромат', 'en' => 'Wonderful scent'],
                        'author' => 'Софія К.',
                        'city' => ['uk' => 'Київ', 'en' => 'Kyiv'],
                        'rating' => 5,
                    ],
                    [
                        'quote' => ['uk' => 'Подобається', 'en' => 'I like it'],
                        'author' => 'Тарас М.',
                        'city' => ['uk' => 'Львів', 'en' => 'Lviv'],
                        'rating' => 4,
                    ],
                ],
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="testimonials', false);
    $response->assertSee('class="track"', false);
    $response->assertSee('Чудовий аромат');
    $response->assertSee('Софія К.');
    $response->assertSee('· Київ');
});

it('renders the articles block as 3-card grid and limits to 3 even with more items', function () {
    $articles = Article::factory()->count(4)->create();
    makeHomepage([
        [
            'type' => 'articles',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Журнал', 'en' => 'Journal'],
                'title' => ['uk' => 'Свіже', 'en' => 'Fresh'],
                'items' => $articles->map(fn ($a) => ['article_id' => $a->id])->all(),
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="blog', false);
    expect(substr_count($response->getContent(), 'class="article-card"'))->toBe(3);
});

it('renders the products block as slider when items exist', function () {
    $products = Product::factory()->count(3)->create();
    makeHomepage([
        [
            'type' => 'products',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Бестселери', 'en' => 'Bestsellers'],
                'title' => ['uk' => 'Найкращі', 'en' => 'Top'],
                'items' => $products->map(fn ($p) => ['product_id' => $p->id])->all(),
            ],
        ],
    ]);

    $response = $this->get('/')->assertOk();
    $response->assertSee('class="product-slider', false);
    $response->assertSee('Бестселери');
});

it('hides blocks with is_visible false', function () {
    makeHomepage([
        [
            'type' => 'hero',
            'data' => [
                'is_visible' => false,
                'eyebrow' => ['uk' => 'INVISIBLE', 'en' => 'INVISIBLE'],
                'title_top' => ['uk' => 'Hidden', 'en' => 'Hidden'],
            ],
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('INVISIBLE')
        ->assertDontSee('Hidden');
});

it('falls back to uk when active locale string is empty (not null)', function () {
    refreshApplicationWithLocale('en');

    makeHomepage([
        [
            'type' => 'hero',
            'data' => [
                'is_visible' => true,
                'eyebrow' => ['uk' => 'Колекція UK', 'en' => ''],
                'title_top' => ['uk' => 'Тайтл UK', 'en' => 'Title EN'],
            ],
        ],
    ]);

    $this->get('/en')
        ->assertOk()
        ->assertSee('Колекція UK')
        ->assertSee('Title EN');
});
