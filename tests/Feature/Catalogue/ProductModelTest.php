<?php

use App\Enums\Gender;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;

it('series has theme_class column with default theme-cream', function () {
    $s = Series::create(['name' => ['uk' => 'Test', 'en' => 'Test'], 'slug' => 'test-series']);
    expect($s->fresh()->theme_class)->toBe('theme-cream');
});

it('series accepts custom theme_class', function () {
    $s = Series::create([
        'name' => ['uk' => 'Test', 'en' => 'Test'],
        'slug' => 'test-series',
        'theme_class' => 'theme-onyx',
    ]);
    expect($s->fresh()->theme_class)->toBe('theme-onyx');
});

it('product persists translatable character + why and integer sillage + longevity', function () {
    $p = Product::factory()->create([
        'character' => ['uk' => 'Прохолодний шкіра', 'en' => 'Cool skin'],
        'why' => ['uk' => 'Бо нерви', 'en' => 'Because nerves'],
        'sillage_score' => 4,
        'longevity_hours' => 8,
    ]);

    $p = $p->fresh();
    expect($p->getTranslation('character', 'uk'))->toBe('Прохолодний шкіра');
    expect($p->getTranslation('character', 'en'))->toBe('Cool skin');
    expect($p->getTranslation('why', 'uk'))->toBe('Бо нерви');
    expect($p->sillage_score)->toBe(4);
    expect($p->longevity_hours)->toBe(8);
});

it('product allows null character + why + sillage + longevity', function () {
    $p = Product::factory()->create([
        'character' => null,
        'why' => null,
        'sillage_score' => null,
        'longevity_hours' => null,
    ]);

    $p = $p->fresh();
    expect($p->character)->toBeNull();
    expect($p->why)->toBeNull();
    expect($p->sillage_score)->toBeNull();
    expect($p->longevity_hours)->toBeNull();
});

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

use App\Models\Catalogue\Audience;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Tag;

it('attaches simple many-to-many relations', function () {
    $p = Product::factory()->create();

    $tag = Tag::factory()->create();
    $season = Season::factory()->create();
    $occasion = Occasion::factory()->create();
    $audience = Audience::factory()->create();

    $p->tags()->attach($tag);
    $p->seasons()->attach($season);
    $p->occasions()->attach($occasion);
    $p->audiences()->attach($audience);

    expect($p->tags)->toHaveCount(1);
    expect($p->seasons)->toHaveCount(1);
    expect($p->occasions)->toHaveCount(1);
    expect($p->audiences)->toHaveCount(1);
});
