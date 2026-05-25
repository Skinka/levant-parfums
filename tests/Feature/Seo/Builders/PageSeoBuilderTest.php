<?php

use App\Models\Content\Page;
use App\Seo\Builders\PageSeoBuilder;
use App\Seo\SeoData;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
    ]);
    $this->builder = app(PageSeoBuilder::class);
});

it('returns SeoData with title fallback chain', function () {
    $page = Page::factory()->create([
        'title' => ['uk' => 'Про нас', 'en' => 'About us'],
        'seo_title' => ['uk' => null, 'en' => null],
        'slug' => ['uk' => 'pro-nas', 'en' => 'about'],
        'is_homepage' => false,
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo)->toBeInstanceOf(SeoData::class)
        ->and($seo->title)->toBe('Про нас · LEVANT Parfums');
});

it('prefers seo_title when present and does not double-append suffix', function () {
    $page = Page::factory()->create([
        'title' => ['uk' => 'Про нас'],
        'seo_title' => ['uk' => 'Про нас · LEVANT Parfums'],
        'slug' => ['uk' => 'pro-nas'],
    ]);

    expect($this->builder->build($page, 'uk')->title)->toBe('Про нас · LEVANT Parfums');
});

it('builds canonical and both-locale alternates for a fully translated page', function () {
    $page = Page::factory()->create([
        'slug' => ['uk' => 'pro-nas', 'en' => 'about'],
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo->canonical)->toBe('https://example.test/pro-nas')
        ->and($seo->alternates)->toBe([
            'uk' => 'https://example.test/pro-nas',
            'en' => 'https://example.test/en/about',
            'x-default' => 'https://example.test/pro-nas',
        ]);
});

it('omits en alternate when only uk translation exists', function () {
    $page = Page::factory()->create([
        'slug' => ['uk' => 'pro-nas', 'en' => null],
    ]);

    $alternates = $this->builder->build($page, 'uk')->alternates;

    expect($alternates)->toHaveKeys(['uk', 'x-default'])
        ->and($alternates)->not->toHaveKey('en');
});

it('uses static-route alternates and "/" canonical for the homepage', function () {
    $page = Page::factory()->create([
        'is_homepage' => true,
        'title' => ['uk' => 'Головна'],
        'slug' => ['uk' => 'holovna', 'en' => 'main'],
    ]);

    $seo = $this->builder->build($page, 'uk');

    expect($seo->canonical)->toBe('https://example.test/')
        ->and($seo->alternates['uk'])->toBe('https://example.test/');
});

it('falls back to default og image when page has no media', function () {
    $page = Page::factory()->create(['slug' => ['uk' => 'x']]);

    expect($this->builder->build($page, 'uk')->ogImage)
        ->toBe('https://example.test/images/og/default.jpg');
});

it('marks ogType as website and robots as index,follow', function () {
    $page = Page::factory()->create(['slug' => ['uk' => 'x']]);
    $seo = $this->builder->build($page, 'uk');

    expect($seo->ogType)->toBe('website')->and($seo->robots)->toBe('index,follow');
});
