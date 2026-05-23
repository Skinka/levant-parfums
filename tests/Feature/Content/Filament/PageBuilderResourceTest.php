<?php

use App\Enums\PageTemplate;
use App\Filament\Resources\Pages\Pages\CreatePage;
use App\Filament\Resources\Pages\Pages\EditPage;
use App\Models\Content\Page;
use App\Models\User;
use Illuminate\Database\QueryException;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('creates a simple page (content required, blocks ignored)', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Доставка',
            'slug' => 'dostavka',
            'content' => 'Текст доставки.',
            'is_published' => true,
            'template' => PageTemplate::Simple->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::firstWhere('id', 1);
    expect($page->template)->toBe(PageTemplate::Simple)
        ->and($page->getTranslation('content', 'uk'))->toBe('Текст доставки.')
        ->and($page->blocks)->toBeNull();
});

it('creates a landing page with two blocks preserving order', function () {
    Livewire::test(CreatePage::class)
        ->fillForm([
            'title' => 'Головна',
            'slug' => 'main',
            'is_published' => true,
            'template' => PageTemplate::Landing->value,
            'blocks' => [
                [
                    'type' => 'hero',
                    'data' => [
                        'is_visible' => true,
                        'title' => ['uk' => 'Привіт', 'en' => 'Hi'],
                    ],
                ],
                [
                    'type' => 'text',
                    'data' => [
                        'is_visible' => true,
                        'body' => ['uk' => 'Текст', 'en' => 'Body'],
                    ],
                ],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $page = Page::firstWhere('id', 1);
    expect($page->blocks)->toHaveCount(2)
        ->and($page->blocks[0]['type'])->toBe('hero')
        ->and($page->blocks[1]['type'])->toBe('text')
        ->and($page->blocks[0]['data']['title']['uk'])->toBe('Привіт')
        ->and($page->blocks[1]['data']['body']['en'])->toBe('Body')
        ->and($page->content)->toBeNull();
});

it('rejects a second is_homepage page at DB level', function () {
    Page::factory()->homepage()->create();
    $other = Page::factory()->create();

    expect(fn () => Livewire::test(EditPage::class, ['record' => $other->getRouteKey()])
        ->fillForm(['is_homepage' => true])
        ->call('save'))
        ->toThrow(QueryException::class);
});
