<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    Cache::forget('sitemap.xml');
});

it('serves /sitemap.xml as application/xml with all seeded entities', function () {
    Page::factory()->create([
        'is_published' => true,
        'is_homepage' => true,
        'title' => ['uk' => 'Головна', 'en' => 'Home'],
        'slug' => ['uk' => 'holovna', 'en' => 'main'],
    ]);
    $about = Page::factory()->create([
        'is_published' => true,
        'slug' => ['uk' => 'pro-nas', 'en' => 'about'],
    ]);
    $product = Product::factory()
        ->for(Series::factory(), 'series')
        ->for(PerfumeFamily::factory(), 'perfumeFamily')
        ->create(['is_published' => true, 'slug' => 'parfum-noir']);
    $article = Article::factory()->create([
        'is_published' => true,
        'slug' => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => now()->subDay(),
    ]);

    $response = $this->get('/sitemap.xml');

    $response->assertOk();
    expect($response->headers->get('content-type'))->toStartWith('application/xml');

    $body = $response->getContent();
    expect($body)
        ->toContain('<?xml')
        ->toContain('https://example.test/')
        ->toContain('https://example.test/en')
        ->toContain('https://example.test/products')
        ->toContain('https://example.test/articles')
        ->toContain('https://example.test/pro-nas')
        ->toContain('https://example.test/en/about')
        ->toContain('https://example.test/products/parfum-noir')
        ->toContain('https://example.test/en/products/parfum-noir')
        ->toContain('https://example.test/articles/novyna')
        ->toContain('https://example.test/en/articles/news')
        ->toContain('xhtml:link')
        ->toContain('hreflang="x-default"');
});

it('omits hreflang for missing translations on a single-locale page', function () {
    Page::factory()->create([
        'is_published' => true,
        'slug' => ['uk' => 'tilki-uk', 'en' => null],
    ]);

    $body = $this->get('/sitemap.xml')->getContent();

    // The uk URL appears, with x-default sibling; no /en/tilki-uk link.
    expect($body)
        ->toContain('https://example.test/tilki-uk')
        ->not->toContain('https://example.test/en/tilki-uk');
});
