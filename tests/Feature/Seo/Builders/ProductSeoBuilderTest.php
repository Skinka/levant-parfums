<?php

use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Seo\Builders\ProductSeoBuilder;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.seo.title_suffix' => 'LEVANT Parfums',
        'site.seo.default_og_image' => '/images/og/default.jpg',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
    $this->builder = app(ProductSeoBuilder::class);
    $this->series = Series::factory()->create();
    $this->family = PerfumeFamily::factory()->create();
});

it('builds canonical with shared slug and product json-ld', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create([
            'slug' => 'parfum-noir',
            'name' => ['uk' => 'Парфум Нуар', 'en' => 'Parfum Noir'],
            'seo_title' => ['uk' => null, 'en' => null],
            'price_uah' => '2400.00',
            'price_eur' => '60.00',
        ]);

    $seo = $this->builder->build($product, 'uk');

    expect($seo->canonical)->toBe('https://example.test/products/parfum-noir')
        ->and($seo->alternates['en'])->toBe('https://example.test/en/products/parfum-noir')
        ->and($seo->ogType)->toBe('product')
        ->and($seo->title)->toBe('Парфум Нуар · LEVANT Parfums')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('Product')
        ->and(collect($seo->jsonLd)->pluck('@type'))->toContain('BreadcrumbList');
});

it('uses EUR currency in Product json-ld when locale is en', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['slug' => 'p', 'price_uah' => '2400.00', 'price_eur' => '60.00']);

    $seo = $this->builder->build($product, 'en');
    $offer = collect($seo->jsonLd)->firstWhere('@type', 'Product')['offers'];

    expect($offer['priceCurrency'])->toBe('EUR')->and($offer['price'])->toBe('60.00');
});
