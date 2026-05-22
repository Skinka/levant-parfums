<?php

use App\Filament\Resources\PerfumeFamilies\Pages\CreatePerfumeFamily;
use App\Filament\Resources\PerfumeFamilies\Pages\ListPerfumeFamilies;
use App\Models\Catalogue\PerfumeFamily;
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
