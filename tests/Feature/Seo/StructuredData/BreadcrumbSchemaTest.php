<?php

use App\Seo\StructuredData\BreadcrumbSchema;

it('produces a BreadcrumbList with 1-indexed items', function () {
    $data = BreadcrumbSchema::generate([
        ['name' => 'Home', 'url' => 'https://example.test/'],
        ['name' => 'Catalogue', 'url' => 'https://example.test/products'],
        ['name' => 'Parfum Noir', 'url' => 'https://example.test/products/parfum-noir'],
    ]);

    expect($data['@type'])->toBe('BreadcrumbList')
        ->and($data['itemListElement'])->toHaveCount(3)
        ->and($data['itemListElement'][0])->toMatchArray([
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => 'https://example.test/',
        ])
        ->and($data['itemListElement'][2]['position'])->toBe(3)
        ->and($data['itemListElement'][2]['name'])->toBe('Parfum Noir');
});

it('returns an empty graph (no schema) when given no crumbs', function () {
    expect(BreadcrumbSchema::generate([]))->toBe([]);
});
