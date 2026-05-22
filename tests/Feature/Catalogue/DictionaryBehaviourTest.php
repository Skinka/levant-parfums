<?php

use App\Models\Catalogue\PerfumeFamily;
use Illuminate\Validation\ValidationException;

it('creates a base-shape dictionary record with translatable name', function () {
    $family = PerfumeFamily::create([
        'name' => ['uk' => 'Квіткове', 'en' => 'Floral'],
        'slug' => 'kvitkove',
    ]);

    app()->setLocale('uk');
    expect($family->fresh()->name)->toBe('Квіткове');

    app()->setLocale('en');
    expect($family->fresh()->name)->toBe('Floral');
});

it('enforces unique slug on base dictionaries', function () {
    PerfumeFamily::create(['name' => ['uk' => 'A'], 'slug' => 'dup']);

    expect(fn () => PerfumeFamily::create(['name' => ['uk' => 'B'], 'slug' => 'dup']))
        ->toThrow(Illuminate\Database\QueryException::class);
});

it('boots through a factory', function () {
    $f = PerfumeFamily::factory()->create();
    expect($f->exists)->toBeTrue();
    expect($f->slug)->toBeString();
});

it('creates a concentration with abbreviation', function () {
    $c = App\Models\Catalogue\Concentration::factory()->create(['abbreviation' => 'EDP']);
    expect($c->abbreviation)->toBe('EDP');
});

it('creates a brand with country', function () {
    $b = App\Models\Catalogue\Brand::factory()->create(['country' => 'FR']);
    expect($b->country)->toBe('FR');
});

it('creates a tag with color and is_featured', function () {
    $t = App\Models\Catalogue\Tag::factory()->create(['color' => '#C77B7B', 'is_featured' => true]);
    expect($t->color)->toBe('#C77B7B');
    expect($t->is_featured)->toBeTrue();
});
