<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->withSession(['locale' => 'uk']);
    $this->series = Series::factory()->create(['slug' => 'onyx']);
    $this->family = PerfumeFamily::factory()->create();
    Product::factory()
        ->count(12)
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['is_published' => true]);
});

it('clean /products is index,follow with canonical /products', function () {
    $body = $this->get('/products')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?page=2 is index,follow with self-canonical', function () {
    $body = $this->get('/products?page=2')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="index,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products?page=2">');
});

it('?sort=priceA is noindex with canonical /products', function () {
    $body = $this->get('/products?sort=priceA')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?sort=pop (explicit default) is still noindex', function () {
    $body = $this->get('/products?sort=pop')->getContent();

    expect($body)->toContain('<meta name="robots" content="noindex,follow">');
});

it('?sort=bad (invalid value) is still noindex', function () {
    $body = $this->get('/products?sort=bad')->getContent();

    expect($body)->toContain('<meta name="robots" content="noindex,follow">');
});

it('?series=onyx is noindex with canonical /products', function () {
    $body = $this->get('/products?series=onyx')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products">');
});

it('?page=2&sort=priceA is noindex with canonical /products?page=2', function () {
    $body = $this->get('/products?page=2&sort=priceA')->getContent();

    expect($body)
        ->toContain('<meta name="robots" content="noindex,follow">')
        ->toContain('<link rel="canonical" href="https://example.test/products?page=2">');
});
