<?php

use App\Models\Content\Article;
use App\Seo\StructuredData\ArticleSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
        'site.organization.logo' => '/images/og/logo.png',
    ]);
});

it('emits Article graph for uk locale', function () {
    $article = Article::factory()->create([
        'title' => ['uk' => 'Заголовок', 'en' => 'Headline'],
        'intro' => ['uk' => 'Інтро', 'en' => 'Intro'],
        'published_at' => '2026-05-20 10:00:00',
    ]);

    $data = ArticleSchema::generate(
        $article,
        locale: 'uk',
        canonical: 'https://example.test/articles/zagolovok',
        ogImage: 'https://example.test/og.jpg',
    );

    expect($data['@type'])->toBe('Article')
        ->and($data['headline'])->toBe('Заголовок')
        ->and($data['description'])->toBe('Інтро')
        ->and($data['image'])->toBe('https://example.test/og.jpg')
        ->and($data['datePublished'])->toStartWith('2026-05-20T10:00:00')
        ->and($data['dateModified'])->not->toBeNull()
        ->and($data['author'])->toBe(['@type' => 'Organization', 'name' => 'LEVANT Parfums'])
        ->and($data['publisher']['@type'])->toBe('Organization')
        ->and($data['publisher']['logo']['url'])->toBe('https://example.test/images/og/logo.png')
        ->and($data['mainEntityOfPage'])->toBe('https://example.test/articles/zagolovok')
        ->and($data['inLanguage'])->toBe('uk-UA');
});

it('emits en-GB inLanguage for en locale', function () {
    $article = Article::factory()->create();

    $data = ArticleSchema::generate($article, 'en', 'https://example.test/en/articles/x', null);

    expect($data['inLanguage'])->toBe('en-GB');
});

it('omits image when no ogImage is provided', function () {
    $article = Article::factory()->create();

    $data = ArticleSchema::generate($article, 'uk', 'https://example.test/articles/x', null);

    expect($data)->not->toHaveKey('image');
});
