<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Catalogue\Note;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the product list page', function () {
    Product::factory()->count(3)->create();
    Livewire::test(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Product::all());
});

it('renders the create product page with the main tab', function () {
    Livewire::test(CreateProduct::class)->assertOk();
});

it('creates a product with description tab fields', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 4', 'en' => 'LUXURY 4'],
            'slug' => 'luxury-4',
            'sku' => 'LV-001',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'tagline' => ['uk' => 'Флоральний наркотик', 'en' => 'Floral narcotic'],
            'description' => ['uk' => '<p>Опис</p>', 'en' => '<p>Description</p>'],
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('products', ['slug' => 'luxury-4', 'sku' => 'LV-001']);
});

it('creates a product with top/heart/base notes', function () {
    $lychee = Note::factory()->create(['slug' => 'lychee']);
    $jasmine = Note::factory()->create(['slug' => 'jasmine']);
    $musk = Note::factory()->create(['slug' => 'musk']);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 5', 'en' => 'LUXURY 5'],
            'slug' => 'luxury-5',
            'sku' => 'LV-002',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'notes_top' => [['note_id' => $lychee->id]],
            'notes_heart' => [['note_id' => $jasmine->id]],
            'notes_base' => [['note_id' => $musk->id]],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $product = Product::firstWhere('slug', 'luxury-5');
    expect($product->notesByLevel(\App\Enums\NoteLevel::Top)->pluck('id')->all())->toContain($lychee->id);
    expect($product->notesByLevel(\App\Enums\NoteLevel::Heart)->pluck('id')->all())->toContain($jasmine->id);
    expect($product->notesByLevel(\App\Enums\NoteLevel::Base)->pluck('id')->all())->toContain($musk->id);
});

it('saves inspired-by brand and perfume name', function () {
    $brand = \App\Models\Catalogue\Brand::factory()->create(['slug' => 'ex-nihilo']);

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 6', 'en' => 'LUXURY 6'],
            'slug' => 'luxury-6',
            'sku' => 'LV-003',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'inspired_brand_id' => $brand->id,
            'inspired_perfume_name' => 'Fleur Narcotique',
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('products', [
        'slug' => 'luxury-6',
        'inspired_brand_id' => $brand->id,
        'inspired_perfume_name' => 'Fleur Narcotique',
    ]);
});

it('attaches tags / seasons / occasions / audiences', function () {
    $tag = \App\Models\Catalogue\Tag::factory()->create();
    $season = \App\Models\Catalogue\Season::factory()->create();
    $occasion = \App\Models\Catalogue\Occasion::factory()->create();
    $audience = \App\Models\Catalogue\Audience::factory()->create();

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 7', 'en' => 'LUXURY 7'],
            'slug' => 'luxury-7',
            'sku' => 'LV-004',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'tags' => [$tag->id],
            'seasons' => [$season->id],
            'occasions' => [$occasion->id],
            'audiences' => [$audience->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-7');
    expect($p->tags->pluck('id')->all())->toContain($tag->id);
    expect($p->seasons->pluck('id')->all())->toContain($season->id);
    expect($p->occasions->pluck('id')->all())->toContain($occasion->id);
    expect($p->audiences->pluck('id')->all())->toContain($audience->id);
});

it('saves SEO fields translatable per locale', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 8', 'en' => 'LUXURY 8'],
            'slug' => 'luxury-8',
            'sku' => 'LV-005',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'seo_title' => ['uk' => 'Купити LUXURY 8', 'en' => 'Buy LUXURY 8'],
            'seo_description' => ['uk' => 'Нотатки', 'en' => 'Notes'],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-8');
    app()->setLocale('en');
    expect($p->fresh()->seo_title)->toBe('Buy LUXURY 8');
});

it('uploads primary and gallery images via Filament', function () {
    \Illuminate\Support\Facades\Storage::fake('public');

    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 9', 'en' => 'LUXURY 9'],
            'slug' => 'luxury-9',
            'sku' => 'LV-006',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
            'primary' => [\Illuminate\Http\UploadedFile::fake()->image('main.jpg')],
            'gallery' => [\Illuminate\Http\UploadedFile::fake()->image('g1.jpg'), \Illuminate\Http\UploadedFile::fake()->image('g2.jpg')],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $p = Product::firstWhere('slug', 'luxury-9');
    expect($p->getMedia('primary'))->toHaveCount(1);
    expect($p->getMedia('gallery'))->toHaveCount(2);
});
