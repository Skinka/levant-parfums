<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Database\Seeders\Catalogue\SeriesSeeder;

it('seeds luxury with theme-cream and onyx with theme-onyx', function () {
    (new SeriesSeeder())->run();
    expect(Series::where('slug', 'luxury')->first()->theme_class)->toBe('theme-cream');
    expect(Series::where('slug', 'onyx')->first()->theme_class)->toBe('theme-onyx');
});

it('seeds character/sillage data on first 2 luxury + 2 onyx products', function () {
    $this->seed(\Database\Seeders\DatabaseSeeder::class);

    $sampleLux = Product::where('slug', 'luxury-1')->first();
    $sampleOnyx = Product::where('slug', 'onyx-1')->first();

    expect($sampleLux->sillage_score)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5);
    expect($sampleLux->longevity_hours)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(12);
    expect($sampleLux->getTranslation('character', 'uk'))->not->toBeEmpty();
    expect($sampleLux->getTranslation('why', 'uk'))->not->toBeEmpty();

    expect($sampleOnyx->sillage_score)->toBeGreaterThanOrEqual(1)->toBeLessThanOrEqual(5);
    expect($sampleOnyx->longevity_hours)->toBeGreaterThanOrEqual(2)->toBeLessThanOrEqual(12);
});
