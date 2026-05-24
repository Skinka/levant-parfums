<?php

use App\Filament\Resources\Series\Pages\EditSeries;
use App\Models\Catalogue\Series;
use App\Models\User;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('series form has theme_class select with values from config', function () {
    $s = Series::create(['slug' => 'demo', 'name' => ['uk' => 'Demo', 'en' => 'Demo']]);

    Livewire::test(EditSeries::class, ['record' => $s->getRouteKey()])
        ->assertFormFieldExists('theme_class')
        ->assertFormSet(['theme_class' => 'theme-cream'])
        ->fillForm(['theme_class' => 'theme-onyx'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($s->fresh()->theme_class)->toBe('theme-onyx');
});
