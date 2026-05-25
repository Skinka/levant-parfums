<?php

use App\Seo\Builders\CatalogSeoBuilder;
use App\Seo\Builders\CatalogSeoInput;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
    ]);
    $this->builder = app(CatalogSeoBuilder::class);
});

it('clean /products is index,follow with canonical /products', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 1), 'uk');

    expect($seo->canonical)->toBe('https://example.test/products')
        ->and($seo->robots)->toBe('index,follow');
});

it('?page=2 alone is self-canonical and index,follow', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 2), 'uk');

    expect($seo->canonical)->toBe('https://example.test/products?page=2')
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/products?page=2');
});

it('?sort=* (any value, including pop) is noindex with canonical /products', function () {
    foreach (['pop', 'priceA', 'priceB', 'bad'] as $sort) {
        $seo = $this->builder->build(new CatalogSeoInput(hasSortParam: true, hasSeriesParam: false, page: 1), 'uk');

        expect($seo->robots)->toBe('noindex,follow')
            ->and($seo->canonical)->toBe('https://example.test/products');
    }
});

it('?series=* is noindex with canonical /products', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, true, 1), 'uk');

    expect($seo->robots)->toBe('noindex,follow')
        ->and($seo->canonical)->toBe('https://example.test/products');
});

it('?page=2&sort=priceA is noindex with canonical /products?page=2', function () {
    $seo = $this->builder->build(new CatalogSeoInput(true, false, 2), 'uk');

    expect($seo->robots)->toBe('noindex,follow')
        ->and($seo->canonical)->toBe('https://example.test/products?page=2');
});

it('always provides both-locale alternates for catalog', function () {
    $seo = $this->builder->build(new CatalogSeoInput(false, false, 1), 'uk');

    expect($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default']);
});
