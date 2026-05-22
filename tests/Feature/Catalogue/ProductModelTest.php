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

use App\Enums\NoteLevel;
use App\Models\Catalogue\Note;

it('attaches notes at different levels', function () {
    $p = Product::factory()->create();
    $jasmine = Note::factory()->create(['name' => ['uk' => 'Жасмін', 'en' => 'Jasmine'], 'slug' => 'jasmine']);
    $musk = Note::factory()->create(['name' => ['uk' => 'Мускус', 'en' => 'Musk'], 'slug' => 'musk']);

    $p->notes()->attach($jasmine->id, ['level' => NoteLevel::Heart->value, 'sort_order' => 0]);
    $p->notes()->attach($jasmine->id, ['level' => NoteLevel::Base->value, 'sort_order' => 0]); // same note, different level — allowed
    $p->notes()->attach($musk->id, ['level' => NoteLevel::Base->value, 'sort_order' => 1]);

    expect($p->notes()->count())->toBe(3);
    expect($p->notesByLevel(NoteLevel::Base)->count())->toBe(2);
    expect($p->notesByLevel(NoteLevel::Heart)->first()->slug)->toBe('jasmine');
});

it('rejects duplicate note at same level', function () {
    $p = Product::factory()->create();
    $n = Note::factory()->create();

    $p->notes()->attach($n->id, ['level' => NoteLevel::Top->value, 'sort_order' => 0]);

    expect(fn () => $p->notes()->attach($n->id, ['level' => NoteLevel::Top->value, 'sort_order' => 1]))
        ->toThrow(Illuminate\Database\QueryException::class);
});
