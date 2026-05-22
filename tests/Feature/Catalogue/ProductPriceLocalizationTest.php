<?php

use App\Models\Catalogue\Product;

it('returns UAH price for uk locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    app()->setLocale('uk');
    $price = $p->displayPrice();

    expect($price['amount'])->toBe('1290.00');
    expect($price['currency'])->toBe('UAH');
});

it('returns EUR price for en locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    app()->setLocale('en');
    $price = $p->displayPrice();

    expect($price['amount'])->toBe('35.00');
    expect($price['currency'])->toBe('EUR');
});

it('falls back to UAH for unknown locale', function () {
    $p = Product::factory()->create(['price_uah' => 1290.00, 'price_eur' => 35.00]);

    $price = $p->displayPrice('xx');

    expect($price['currency'])->toBe('UAH');
});
