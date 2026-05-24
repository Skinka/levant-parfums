<?php

use App\Models\Content\Article;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

it('GET /articles/{slug} returns 200 for a published article (uk slug)', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tri-tochki', 'en' => 'three-points'],
        'title' => ['uk' => 'Три точки', 'en' => 'Three points'],
        'intro' => ['uk' => 'Коротко про дім.', 'en' => 'About the house.'],
    ]);

    $this->get('/articles/tri-tochki')
        ->assertOk()
        ->assertSee('Три точки')
        ->assertSee('Коротко про дім.');
});

it('GET /articles/{slug} returns 404 when the slug belongs to another locale', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tri-tochki', 'en' => 'three-points'],
    ]);

    $this->get('/articles/three-points')->assertNotFound();
});

it('GET /articles/{slug} returns 404 for unpublished articles', function () {
    Article::factory()->draft()->create([
        'slug' => ['uk' => 'chernetka', 'en' => 'draft'],
    ]);

    $this->get('/articles/chernetka')->assertNotFound();
});

it('GET /articles/{slug} returns 404 for scheduled articles', function () {
    Article::factory()->scheduled(now()->addDay())->create([
        'slug' => ['uk' => 'mayb', 'en' => 'future'],
    ]);

    $this->get('/articles/mayb')->assertNotFound();
});

it('renders the metadata strip and the article body as HTML', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'tilo', 'en' => 'body'],
        'title' => ['uk' => 'Тіло', 'en' => 'Body'],
        'intro' => ['uk' => 'Лід.', 'en' => 'Lead.'],
        'content' => [
            'uk' => "Перший абзац.\n\n## Підзаголовок\n\nДругий абзац.",
            'en' => "First paragraph.\n\n## Subheading\n\nSecond paragraph.",
        ],
        'category' => ['uk' => 'Освіта', 'en' => 'Education'],
        'read_time_minutes' => 5,
        'published_at' => Carbon::create(2026, 5, 12, 9),
    ]);

    $this->get('/articles/tilo')
        ->assertOk()
        ->assertSee('Освіта')
        ->assertSee('12 травня 2026')
        ->assertSee('5 хв')
        ->assertSee('Лід.')
        ->assertSee('<h2>Підзаголовок</h2>', escape: false)
        ->assertSee('<p>Перший абзац.</p>', escape: false);
});

it('shows breadcrumbs that link back to the articles index', function () {
    Article::factory()->create([
        'slug' => ['uk' => 'krohty', 'en' => 'crumbs'],
        'title' => ['uk' => 'Крихти', 'en' => 'Crumbs'],
    ]);

    $this->get('/articles/krohty')
        ->assertOk()
        ->assertSee('href="/articles"', escape: false)
        ->assertSee('Крихти');
});
