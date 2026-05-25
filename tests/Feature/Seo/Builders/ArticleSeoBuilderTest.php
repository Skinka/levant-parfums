<?php

use App\Models\Content\Article;
use App\Seo\Builders\ArticleSeoBuilder;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
        'site.organization.name' => 'LEVANT Parfums',
        'site.organization.logo' => '/images/og/logo.png',
    ]);
    $this->builder = app(ArticleSeoBuilder::class);
});

it('builds canonical, alternates and article json-ld', function () {
    $article = Article::factory()->create([
        'title' => ['uk' => 'Стаття', 'en' => 'Article'],
        'seo_title' => ['uk' => null, 'en' => null],
        'intro' => ['uk' => 'Вступ', 'en' => 'Intro'],
        'slug' => ['uk' => 'novyna', 'en' => 'news'],
        'published_at' => '2026-05-20 10:00:00',
    ]);

    $seo = $this->builder->build($article, 'uk');

    expect($seo->title)->toBe('Стаття · LEVANT Parfums')
        ->and($seo->canonical)->toBe('https://example.test/articles/novyna')
        ->and($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default'])
        ->and($seo->ogType)->toBe('article')
        ->and($seo->publishedTime)->toStartWith('2026-05-20T10:00:00')
        ->and($seo->modifiedTime)->not->toBeNull()
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('Article')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('BreadcrumbList');
});

it('drops the en alternate when only uk slug exists', function () {
    $article = Article::factory()->create(['slug' => ['uk' => 'novyna', 'en' => null]]);

    $alternates = $this->builder->build($article, 'uk')->alternates;

    expect($alternates)->toHaveKeys(['uk', 'x-default'])
        ->and($alternates)->not->toHaveKey('en');
});
