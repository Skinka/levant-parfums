<?php

use App\Models\Catalogue\Brand;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Seo\StructuredData\ProductSchema;

beforeEach(function () {
    config([
        'app.url' => 'https://example.test',
        'site.organization.name' => 'LEVANT Parfums',
    ]);
    $this->series = Series::factory()->create();
    $this->family = PerfumeFamily::factory()->create([
        'name' => ['uk' => 'Шипрові', 'en' => 'Chypre'],
    ]);
});

it('emits Product graph with UAH for uk locale', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create([
            'name' => ['uk' => 'Парфум Нуар', 'en' => 'Parfum Noir'],
            'description' => ['uk' => 'Опис парфуму', 'en' => 'Description'],
            'price_uah' => '2400.00',
            'price_eur' => '60.00',
            'in_stock' => true,
        ]);

    $data = ProductSchema::generate(
        $product,
        locale: 'uk',
        canonical: 'https://example.test/products/'.$product->slug,
        ogImage: 'https://example.test/images/og/default.jpg',
    );

    expect($data['@type'])->toBe('Product')
        ->and($data['name'])->toBe('Парфум Нуар')
        ->and($data['description'])->toBe('Опис парфуму')
        ->and($data['image'])->toBe(['https://example.test/images/og/default.jpg'])
        ->and($data['sku'])->toBe((string) $product->id)
        ->and($data['category'])->toBe('Шипрові')
        ->and($data['offers']['priceCurrency'])->toBe('UAH')
        ->and($data['offers']['price'])->toBe('2400.00')
        ->and($data['offers']['availability'])->toBe('https://schema.org/InStock')
        ->and($data['offers']['itemCondition'])->toBe('https://schema.org/NewCondition')
        ->and($data['offers']['url'])->toBe('https://example.test/products/'.$product->slug);
});

it('emits EUR for en locale', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['price_uah' => '2400.00', 'price_eur' => '60.00']);

    $data = ProductSchema::generate($product, 'en', 'https://example.test/en/products/x', null);

    expect($data['offers']['priceCurrency'])->toBe('EUR')
        ->and($data['offers']['price'])->toBe('60.00');
});

it('emits OutOfStock when in_stock is false', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create(['in_stock' => false]);

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data['offers']['availability'])->toBe('https://schema.org/OutOfStock');
});

it('always uses organization name as brand, never inspired brand', function () {
    $inspired = Brand::factory()->create(['name' => 'Tom Ford']);
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->for($inspired, 'inspiredBrand')
        ->create();

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data['brand'])->toBe(['@type' => 'Brand', 'name' => 'LEVANT Parfums']);
});

it('omits image key when no ogImage is provided', function () {
    $product = Product::factory()
        ->for($this->series, 'series')
        ->for($this->family, 'perfumeFamily')
        ->create();

    $data = ProductSchema::generate($product, 'uk', 'https://example.test/products/x', null);

    expect($data)->not->toHaveKey('image');
});
