<?php

use App\Filament\Resources\Pages\Schemas\Blocks\ArticlesBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\BrandStoryBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\HeroBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\PillarsBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\ProductsBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\SeriesDuoBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\TestimonialsBlock;
use App\Filament\Resources\Pages\Schemas\Blocks\TextBlock;
use App\Models\User;

beforeEach(function () {
    $admin = User::factory()->create();
    $this->actingAs($admin);
});

it('exposes 8 block types on the Builder field', function () {
    $blockClasses = [
        HeroBlock::class,
        TextBlock::class,
        ProductsBlock::class,
        BrandStoryBlock::class,
        SeriesDuoBlock::class,
        PillarsBlock::class,
        TestimonialsBlock::class,
        ArticlesBlock::class,
    ];

    $blockNames = collect($blockClasses)->map(fn ($cls) => $cls::make()->getName())->all();

    expect($blockNames)->toEqualCanonicalizing([
        'hero', 'text', 'products', 'brand_story', 'series_duo', 'pillars', 'testimonials', 'articles',
    ]);
});

it('enforces brand_story pillars exactly 3 via repeater minItems/maxItems', function () {
    $block = BrandStoryBlock::make();
    // getChildComponents() requires an initialized container in Filament v5;
    // getDefaultChildComponents() returns the raw schema array directly.
    $schema = $block->getDefaultChildComponents();
    $pillarsRepeater = collect($schema)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'pillars');

    expect($pillarsRepeater)->not->toBeNull()
        ->and($pillarsRepeater->getMinItems())->toBe(3)
        ->and($pillarsRepeater->getMaxItems())->toBe(3);
});

it('enforces series_duo items exactly 2', function () {
    $block = SeriesDuoBlock::make();
    // getChildComponents() requires an initialized container in Filament v5;
    // getDefaultChildComponents() returns the raw schema array directly.
    $schema = $block->getDefaultChildComponents();
    $itemsRepeater = collect($schema)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'items');

    expect($itemsRepeater)->not->toBeNull()
        ->and($itemsRepeater->getMinItems())->toBe(2)
        ->and($itemsRepeater->getMaxItems())->toBe(2);
});

it('enforces pillars items min 3 and max 4', function () {
    $block = PillarsBlock::make();
    // getChildComponents() requires an initialized container in Filament v5;
    // getDefaultChildComponents() returns the raw schema array directly.
    $schema = $block->getDefaultChildComponents();
    $itemsRepeater = collect($schema)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'items');

    expect($itemsRepeater)->not->toBeNull()
        ->and($itemsRepeater->getMinItems())->toBe(3)
        ->and($itemsRepeater->getMaxItems())->toBe(4);
});

it('enforces hero meta exactly 3', function () {
    $block = HeroBlock::make();
    // getChildComponents() requires an initialized container in Filament v5;
    // getDefaultChildComponents() returns the raw schema array directly.
    $schema = $block->getDefaultChildComponents();
    $metaRepeater = collect($schema)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'meta');

    expect($metaRepeater)->not->toBeNull()
        ->and($metaRepeater->getMinItems())->toBe(3)
        ->and($metaRepeater->getMaxItems())->toBe(3);
});

it('enforces articles items exactly 3', function () {
    $block = ArticlesBlock::make();
    // getChildComponents() requires an initialized container in Filament v5;
    // getDefaultChildComponents() returns the raw schema array directly.
    $schema = $block->getDefaultChildComponents();
    $itemsRepeater = collect($schema)->first(fn ($c) => method_exists($c, 'getName') && $c->getName() === 'items');

    expect($itemsRepeater)->not->toBeNull()
        ->and($itemsRepeater->getMinItems())->toBe(3)
        ->and($itemsRepeater->getMaxItems())->toBe(3);
});
