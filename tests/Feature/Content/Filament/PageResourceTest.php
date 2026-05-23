<?php

use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\ListPages;
use App\Models\Content\Page;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the page list', function () {
    Page::factory()->count(2)->create();
    Livewire::test(ListPages::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Page::all());
});

it('creates a page with translatable title and slug', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка',
            'slug' => 'dostavka',
            'content' => 'Текст про доставку.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('pages', []);
    $page = Page::firstWhere('id', 1);
    expect($page->getTranslation('slug', 'uk'))->toBe('dostavka');
});

it('rejects a duplicate uk slug on create', function () {
    Page::factory()->create(['slug' => ['uk' => 'dostavka', 'en' => 'delivery']]);

    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка 2',
            'slug' => 'dostavka',
            'content' => 'Текст.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('rejects a reserved slug on create', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Blog page',
            'slug' => 'blog',
            'content' => 'Body.',
            'is_published' => true,
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});
