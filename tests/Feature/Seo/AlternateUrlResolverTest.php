<?php

use App\Seo\AlternateUrlResolver;

beforeEach(function () {
    config(['app.url' => 'https://example.test']);
    $this->resolver = new AlternateUrlResolver;
});

it('forSharedSlug emits both locales and x-default for a product', function () {
    $alternates = $this->resolver->forSharedSlug('/products/parfum-noir');

    expect($alternates)->toBe([
        'uk' => 'https://example.test/products/parfum-noir',
        'en' => 'https://example.test/en/products/parfum-noir',
        'x-default' => 'https://example.test/products/parfum-noir',
    ]);
});

it('forStaticRoute emits both locales for a path without query params', function () {
    expect($this->resolver->forStaticRoute('/products'))->toBe([
        'uk' => 'https://example.test/products',
        'en' => 'https://example.test/en/products',
        'x-default' => 'https://example.test/products',
    ]);
});

it('forStaticRoute appends query params verbatim to both locales', function () {
    expect($this->resolver->forStaticRoute('/products', ['page' => 2]))->toBe([
        'uk' => 'https://example.test/products?page=2',
        'en' => 'https://example.test/en/products?page=2',
        'x-default' => 'https://example.test/products?page=2',
    ]);
});

it('forStaticRoute treats / as the home path', function () {
    expect($this->resolver->forStaticRoute('/'))->toBe([
        'uk' => 'https://example.test/',
        'en' => 'https://example.test/en',
        'x-default' => 'https://example.test/',
    ]);
});

it('forTranslatedSlug emits both locales when both translations exist', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => 'pro-nas', 'en' => 'about']);

    expect($alternates)->toBe([
        'uk' => 'https://example.test/pro-nas',
        'en' => 'https://example.test/en/about',
        'x-default' => 'https://example.test/pro-nas',
    ]);
});

it('forTranslatedSlug omits a locale that has no translation', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => 'pro-nas', 'en' => null]);

    expect($alternates)->toBe([
        'uk' => 'https://example.test/pro-nas',
        'x-default' => 'https://example.test/pro-nas',
    ]);
});

it('forTranslatedSlug omits x-default when uk translation is missing', function () {
    $alternates = $this->resolver->forTranslatedSlug('/', ['uk' => null, 'en' => 'about']);

    expect($alternates)->toBe([
        'en' => 'https://example.test/en/about',
    ]);
});

it('forTranslatedSlug supports a nested path prefix', function () {
    $alternates = $this->resolver->forTranslatedSlug('/articles/', ['uk' => 'novyna', 'en' => 'news']);

    expect($alternates)->toBe([
        'uk' => 'https://example.test/articles/novyna',
        'en' => 'https://example.test/en/articles/news',
        'x-default' => 'https://example.test/articles/novyna',
    ]);
});
