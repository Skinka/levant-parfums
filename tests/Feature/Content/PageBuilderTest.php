<?php

use App\Enums\PageTemplate;
use App\Models\Content\Page;
use Illuminate\Database\QueryException;

it('casts template to PageTemplate enum', function () {
    $page = Page::factory()->create(['template' => 'landing']);

    expect($page->refresh()->template)->toBe(PageTemplate::Landing);
});

it('casts blocks to array', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => 'A', 'en' => 'A']]],
        ],
    ]);

    expect($page->refresh()->blocks)->toBeArray()->toHaveCount(1)
        ->and($page->blocks[0]['type'])->toBe('hero');
});

it('visibleBlocks filters is_visible=false and preserves order', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'blocks' => [
            ['type' => 'hero', 'data' => ['is_visible' => true, 'title' => ['uk' => '1', 'en' => '1']]],
            ['type' => 'text', 'data' => ['is_visible' => false, 'body' => ['uk' => '2', 'en' => '2']]],
            ['type' => 'text', 'data' => ['is_visible' => true, 'body' => ['uk' => '3', 'en' => '3']]],
        ],
    ]);

    $visible = $page->visibleBlocks();

    expect($visible)->toHaveCount(2)
        ->and($visible[0]['data']['title']['uk'])->toBe('1')
        ->and($visible[1]['data']['body']['uk'])->toBe('3');
});

it('visibleBlocks returns empty array when blocks is null', function () {
    $page = Page::factory()->create(['template' => 'simple', 'blocks' => null]);

    expect($page->visibleBlocks())->toBe([]);
});

it('homepage scope returns only is_homepage=true', function () {
    Page::factory()->create(['is_homepage' => false]);
    Page::factory()->homepage()->create();

    expect(Page::query()->homepage()->count())->toBe(1);
});

it('DB rejects a second is_homepage=true page', function () {
    Page::factory()->homepage()->create();

    expect(fn () => Page::factory()->homepage()->create())
        ->toThrow(QueryException::class);
});

it('allows landing page with null content', function () {
    $page = Page::factory()->create([
        'template' => 'landing',
        'content' => null,
        'blocks' => [],
    ]);

    expect($page->refresh()->content)->toBeNull()
        ->and($page->template)->toBe(PageTemplate::Landing);
});
