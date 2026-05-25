<?php

use App\Seo\StructuredData\WebSiteSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
});

it('emits WebSite graph for uk locale with uk-UA inLanguage', function () {
    $data = WebSiteSchema::generate('uk');

    expect($data['@type'])->toBe('WebSite')
        ->and($data['url'])->toBe('https://example.test/')
        ->and($data['name'])->toBe('LEVANT Parfums')
        ->and($data['inLanguage'])->toBe('uk-UA');
});

it('emits en-GB inLanguage for en locale', function () {
    expect(WebSiteSchema::generate('en')['inLanguage'])->toBe('en-GB');
});
