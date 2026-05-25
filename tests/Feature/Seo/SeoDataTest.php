<?php

use App\Seo\SeoData;

it('constructs with required fields and sensible defaults', function () {
    $seo = new SeoData(
        title: 'Example',
        description: 'desc',
        canonical: 'https://example.test/foo',
    );

    expect($seo->title)->toBe('Example')
        ->and($seo->description)->toBe('desc')
        ->and($seo->canonical)->toBe('https://example.test/foo')
        ->and($seo->ogType)->toBe('website')
        ->and($seo->ogImage)->toBeNull()
        ->and($seo->alternates)->toBe([])
        ->and($seo->robots)->toBe('index,follow')
        ->and($seo->jsonLd)->toBe([])
        ->and($seo->publishedTime)->toBeNull()
        ->and($seo->modifiedTime)->toBeNull();
});

it('accepts full set of fields including alternates and jsonLd', function () {
    $seo = new SeoData(
        title: 'T',
        description: 'D',
        canonical: 'https://x.test/a',
        ogType: 'article',
        ogImage: 'https://x.test/og.jpg',
        ogImageWidth: 1200,
        ogImageHeight: 630,
        alternates: ['uk' => 'https://x.test/a', 'en' => 'https://x.test/en/a', 'x-default' => 'https://x.test/a'],
        robots: 'noindex,follow',
        jsonLd: [['@type' => 'Article']],
        publishedTime: '2026-05-25T10:00:00+00:00',
        modifiedTime: '2026-05-25T11:00:00+00:00',
    );

    expect($seo->ogType)->toBe('article')
        ->and($seo->ogImage)->toBe('https://x.test/og.jpg')
        ->and($seo->alternates)->toHaveKeys(['uk', 'en', 'x-default'])
        ->and($seo->robots)->toBe('noindex,follow')
        ->and($seo->jsonLd[0]['@type'])->toBe('Article')
        ->and($seo->publishedTime)->toBe('2026-05-25T10:00:00+00:00');
});

it('is immutable (readonly)', function () {
    $seo = new SeoData(title: 'T', description: null, canonical: 'https://x.test/');

    expect(fn () => $seo->title = 'Other')->toThrow(Error::class);
});
