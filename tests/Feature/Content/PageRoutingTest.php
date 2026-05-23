<?php

use App\Enums\PageTemplate;
use App\Models\Catalogue\Product;
use App\Models\Content\Page;

// Pin the locale negotiation to `uk` so the LocaleSessionRedirect middleware
// does not 302 us off to /en. Accept-Language defaults to whatever Symfony's
// test client uses (often `en`), which is a non-default locale and triggers
// a redirect under hideDefaultLocaleInURL=true.
beforeEach(function () {
    $this->withHeaders(['Accept-Language' => 'uk']);
});

it('GET / returns 200 for the published homepage', function () {
    Page::factory()->homepage()->create([
        'title' => ['uk' => 'Головна', 'en' => 'Home'],
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => 'Привіт', 'en' => 'Hi']]],
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('Привіт');
});

it('GET / returns 404 if no homepage is configured', function () {
    Page::factory()->create(['is_homepage' => false]);

    $this->get('/')->assertNotFound();
});

it('GET / returns 404 if homepage is unpublished', function () {
    Page::factory()->homepage()->draft()->create();

    $this->get('/')->assertNotFound();
});

it('GET /{slug} returns 200 for a published simple page', function () {
    Page::factory()->create([
        'template' => PageTemplate::Simple,
        'slug' => ['uk' => 'pro-nas', 'en' => 'about-us'],
        'title' => ['uk' => 'Про нас', 'en' => 'About us'],
        'content' => ['uk' => 'Опис компанії.', 'en' => 'Company description.'],
        'is_published' => true,
    ]);

    $this->get('/pro-nas')
        ->assertOk()
        ->assertSee('Опис компанії.', escape: false);
});

it('GET /{slug} returns 200 for a published landing page (not homepage)', function () {
    Page::factory()->create([
        'template' => PageTemplate::Landing,
        'is_homepage' => false,
        'content' => null,
        'slug' => ['uk' => 'aktsii', 'en' => 'promo'],
        'blocks' => [
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => 'Акції зараз', 'en' => 'Promo now']]],
        ],
        'is_published' => true,
    ]);

    $this->get('/aktsii')
        ->assertOk()
        ->assertSee('Акції зараз');
});

it('does not render blocks with is_visible=false', function () {
    Page::factory()->homepage()->create([
        'blocks' => [
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => 'VISIBLE-BLOCK', 'en' => 'V']]],
            ['type' => 'text', 'data' => ['is_visible' => false, 'body' => ['uk' => 'HIDDEN-BLOCK', 'en' => 'H']]],
        ],
    ]);

    $response = $this->get('/');
    $response->assertOk()
        ->assertSee('VISIBLE-BLOCK')
        ->assertDontSee('HIDDEN-BLOCK');
});

it('renders product list block with selected products', function () {
    $p1 = Product::factory()->create(['slug' => 'aaa-001']);
    $p2 = Product::factory()->create(['slug' => 'bbb-002']);

    Page::factory()->homepage()->create([
        'blocks' => [
            ['type' => 'products', 'data' => ['is_visible' => true, 'items' => [
                ['product_id' => $p2->id],
                ['product_id' => $p1->id],
            ]]],
        ],
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee($p1->name)
        ->assertSee($p2->name);
});
