<?php

use App\Filament\Resources\Products\Pages\EditProduct;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('ProductForm has character + why + sillage + longevity fields', function () {
    $p = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $p->getRouteKey()])
        ->assertFormFieldExists('character')
        ->assertFormFieldExists('why')
        ->assertFormFieldExists('sillage_score')
        ->assertFormFieldExists('longevity_hours');
});

it('saving Character & strength values persists to product', function () {
    $p = Product::factory()->create();

    Livewire::test(EditProduct::class, ['record' => $p->getRouteKey()])
        ->fillForm([
            'character' => 'Темний шкіра і ладан',
            'why' => 'Для зими',
            'sillage_score' => 4,
            'longevity_hours' => 10,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $p = $p->fresh();
    expect($p->sillage_score)->toBe(4);
    expect($p->longevity_hours)->toBe(10);
});
