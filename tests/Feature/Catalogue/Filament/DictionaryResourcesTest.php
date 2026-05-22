<?php

use App\Filament\Resources\Audiences\Pages\CreateAudience;
use App\Filament\Resources\Brands\Pages\CreateBrand;
use App\Filament\Resources\Concentrations\Pages\CreateConcentration;
use App\Filament\Resources\Tags\Pages\CreateTag;
use App\Filament\Resources\Occasions\Pages\CreateOccasion;
use App\Filament\Resources\PerfumeFamilies\Pages\CreatePerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\ListPerfumeFamilies;
use App\Filament\Resources\Seasons\Pages\CreateSeason;
use App\Filament\Resources\Series\Pages\CreateSeries;
use App\Models\Catalogue\Audience;
use App\Models\Catalogue\Brand;
use App\Models\Catalogue\Concentration;
use App\Models\Catalogue\Tag;
use App\Models\Catalogue\Occasion;
use App\Models\Catalogue\PerfumeFamily;
use App\Models\Catalogue\Season;
use App\Models\Catalogue\Series as SeriesModel;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(fn () => actingAs(User::factory()->create()));

it('lists PerfumeFamily records', function () {
    $records = PerfumeFamily::factory()->count(3)->create();
    Livewire::test(ListPerfumeFamilies::class)
        ->assertOk()
        ->assertCanSeeTableRecords($records);
});

it('creates a PerfumeFamily', function () {
    Livewire::test(CreatePerfumeFamily::class)
        ->fillForm([
            'name' => ['uk' => 'Цитрусове', 'en' => 'Citrus'],
            'slug' => 'citrus',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('perfume_families', ['slug' => 'citrus']);
});

it('creates a base-shape dictionary record via Filament', function (string $createPage, string $model, string $slug) {
    Livewire::test($createPage)
        ->fillForm([
            'name' => ['uk' => 'Тест', 'en' => 'Test'],
            'slug' => $slug,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas((new $model)->getTable(), ['slug' => $slug]);
})->with([
    'series'   => [CreateSeries::class, SeriesModel::class, 'series-x'],
    'season'   => [CreateSeason::class, Season::class, 'season-x'],
    'occasion' => [CreateOccasion::class, Occasion::class, 'occasion-x'],
    'audience' => [CreateAudience::class, Audience::class, 'audience-x'],
]);

it('creates a Concentration with abbreviation via Filament', function () {
    Livewire::test(CreateConcentration::class)
        ->fillForm([
            'name' => ['uk' => 'Парфум', 'en' => 'Parfum'],
            'slug' => 'parfum',
            'abbreviation' => 'PARF',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('concentrations', ['slug' => 'parfum', 'abbreviation' => 'PARF']);
});

it('creates a Brand with country via Filament', function () {
    Livewire::test(CreateBrand::class)
        ->fillForm([
            'name' => ['uk' => 'Бренд', 'en' => 'Brand'],
            'slug' => 'brand-x',
            'country' => 'FR',
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('brands', ['slug' => 'brand-x', 'country' => 'FR']);
});

it('creates a Tag with color and featured flag via Filament', function () {
    Livewire::test(CreateTag::class)
        ->fillForm([
            'name' => ['uk' => 'Бестселер', 'en' => 'Bestseller'],
            'slug' => 'bestseller',
            'color' => '#C77B7B',
            'is_featured' => true,
            'sort_order' => 0,
            'is_active' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('tags', ['slug' => 'bestseller', 'color' => '#C77B7B', 'is_featured' => true]);
});
