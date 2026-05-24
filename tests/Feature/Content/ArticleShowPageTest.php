<?php

use App\Models\Content\Article;

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
