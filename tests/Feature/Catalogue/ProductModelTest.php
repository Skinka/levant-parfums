<?php

use App\Enums\Gender;
use App\Models\Catalogue\Product;

it('creates a product with all belongsTo relations', function () {
    $p = Product::factory()->create();

    expect($p->exists)->toBeTrue();
    expect($p->perfumeFamily)->not->toBeNull();
    expect($p->concentration)->not->toBeNull();
    expect($p->series)->not->toBeNull();
    expect($p->inspiredBrand)->not->toBeNull();
});

it('casts gender to Gender enum', function () {
    $p = Product::factory()->create(['gender' => Gender::Unisex->value]);
    expect($p->fresh()->gender)->toBe(Gender::Unisex);
});

it('keeps name translatable across locales', function () {
    $p = Product::factory()->create(['name' => ['uk' => 'Лакшері', 'en' => 'Luxury']]);

    app()->setLocale('uk');
    expect($p->fresh()->name)->toBe('Лакшері');

    app()->setLocale('en');
    expect($p->fresh()->name)->toBe('Luxury');
});

it('enforces unique sku', function () {
    Product::factory()->create(['sku' => 'DUP-1']);
    expect(fn () => Product::factory()->create(['sku' => 'DUP-1']))
        ->toThrow(Illuminate\Database\QueryException::class);
});
