<?php

use App\Seo\StructuredData\OrganizationSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization' => [
            'name' => 'LEVANT Parfums',
            'logo' => '/images/og/logo.png',
            'phone' => '+380000000000',
            'email' => 'hi@example.test',
            'address' => ['country' => 'UA', 'locality' => 'Kyiv', 'street' => 'Some St 1'],
            'same_as' => ['https://instagram.com/levant', 'https://t.me/levant'],
        ],
    ]);
});

it('emits a fully populated Organization graph', function () {
    $data = OrganizationSchema::generate();

    expect($data['@context'])->toBe('https://schema.org')
        ->and($data['@type'])->toBe('Organization')
        ->and($data['name'])->toBe('LEVANT Parfums')
        ->and($data['url'])->toBe('https://example.test/')
        ->and($data['logo'])->toBe('https://example.test/images/og/logo.png')
        ->and($data['email'])->toBe('hi@example.test')
        ->and($data['telephone'])->toBe('+380000000000')
        ->and($data['address'])->toMatchArray([
            '@type' => 'PostalAddress',
            'addressCountry' => 'UA',
            'addressLocality' => 'Kyiv',
            'streetAddress' => 'Some St 1',
        ])
        ->and($data['sameAs'])->toBe(['https://instagram.com/levant', 'https://t.me/levant']);
});

it('omits optional fields that are empty', function () {
    config([
        'site.organization' => [
            'name' => 'LEVANT Parfums',
            'logo' => '/images/og/logo.png',
            'phone' => null,
            'email' => null,
            'address' => ['country' => null, 'locality' => null, 'street' => null],
            'same_as' => [],
        ],
    ]);

    $data = OrganizationSchema::generate();

    expect($data)->not->toHaveKey('email')
        ->and($data)->not->toHaveKey('telephone')
        ->and($data)->not->toHaveKey('address')
        ->and($data)->not->toHaveKey('sameAs');
});
