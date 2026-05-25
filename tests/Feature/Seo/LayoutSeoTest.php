<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Testing\TestResponse;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->withSession(['locale' => 'uk']);
});

function assertSeoBaseline(TestResponse $response): void
{
    $response->assertOk();
    $body = $response->getContent();
    expect($body)
        ->toContain('<title>')
        ->toContain('<link rel="canonical"')
        ->toContain('rel="alternate" hreflang="uk"')
        ->toContain('rel="alternate" hreflang="x-default"')
        ->toContain('property="og:type"')
        ->toContain('property="og:title"')
        ->toContain('property="og:image"')
        ->toContain('name="twitter:card"')
        ->toContain('"@type":"Organization"')
        ->toContain('"@type":"WebSite"');
}

it('emits SEO baseline on the homepage', function () {
    Page::factory()->create([
        'is_published' => true,
        'is_homepage' => true,
        'title' => ['uk' => 'Головна', 'en' => 'Home'],
        'slug' => ['uk' => 'holovna', 'en' => 'main'],
    ]);

    assertSeoBaseline($this->get('/'));
});

it('emits SEO baseline on /products and is index,follow', function () {
    $response = $this->get('/products');
    assertSeoBaseline($response);
    expect($response->getContent())->toContain('content="index,follow"');
});

it('emits SEO baseline plus Product json-ld on a product page', function () {
    $product = Product::factory()
        ->for(Series::factory(), 'series')
        ->for(PerfumeFamily::factory(), 'perfumeFamily')
        ->create(['is_published' => true, 'slug' => 'parfum-noir']);

    $response = $this->get('/products/'.$product->slug);
    assertSeoBaseline($response);
    expect($response->getContent())
        ->toContain('"@type":"Product"')
        ->toContain('property="og:type" content="product"');
});

it('emits SEO baseline plus Article json-ld on an article page', function () {
    $article = Article::factory()->create([
        'is_published' => true,
        'slug' => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/articles/'.$article->getTranslation('slug', 'uk'));
    assertSeoBaseline($response);
    expect($response->getContent())
        ->toContain('"@type":"Article"')
        ->toContain('property="og:type" content="article"');
});

it('emits SEO baseline on a CMS page', function () {
    $page = Page::factory()->create([
        'is_published' => true,
        'slug' => ['uk' => 'pro-nas', 'en' => 'about'],
        'title' => ['uk' => 'Про нас', 'en' => 'About'],
    ]);

    assertSeoBaseline($this->get('/'.$page->getTranslation('slug', 'uk')));
});
