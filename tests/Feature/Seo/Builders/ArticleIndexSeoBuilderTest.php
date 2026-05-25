<?php

use App\Seo\Builders\ArticleIndexSeoBuilder;

beforeEach(function () {
    config(['app.url' => 'https://example.test', 'site.seo.title_suffix' => 'LEVANT Parfums']);
    $this->builder = app(ArticleIndexSeoBuilder::class);
});

it('builds canonical /articles for page 1', function () {
    $seo = $this->builder->build('uk', page: 1);

    expect($seo->canonical)->toBe('https://example.test/articles')
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->alternates['uk'])->toBe('https://example.test/articles')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/articles')
        ->and($seo->alternates['x-default'])->toBe('https://example.test/articles');
});

it('self-canonicalises page > 1 with ?page= in the URL', function () {
    $seo = $this->builder->build('uk', page: 3);

    expect($seo->canonical)->toBe('https://example.test/articles?page=3')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/articles?page=3')
        ->and($seo->robots)->toBe('index,follow');
});
