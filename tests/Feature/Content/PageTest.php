<?php

use App\Models\Content\Page;
use Illuminate\Database\QueryException;

it('stores translatable fields per locale', function () {
    $page = Page::factory()->create([
        'title' => ['uk' => 'Доставка', 'en' => 'Delivery'],
        'slug' => ['uk' => 'dostavka', 'en' => 'delivery'],
        'content' => ['uk' => 'Текст', 'en' => 'Text'],
    ]);

    expect($page->getTranslation('title', 'uk'))->toBe('Доставка');
    expect($page->getTranslation('title', 'en'))->toBe('Delivery');
});

it('published scope returns only is_published=true', function () {
    Page::factory()->create(['is_published' => true]);
    Page::factory()->create(['is_published' => false]);

    expect(Page::published()->count())->toBe(1);
});

it('DB rejects duplicate uk slug for two pages', function () {
    Page::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-1']]);

    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-2']]))
        ->toThrow(QueryException::class);
});

it('saving throws DomainException when uk slug is reserved', function () {
    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'blog', 'en' => 'blog-en']]))
        ->toThrow(DomainException::class);
});

it('saving throws DomainException when en slug is reserved', function () {
    expect(fn () => Page::factory()->create(['slug' => ['uk' => 'ok-uk', 'en' => 'admin']]))
        ->toThrow(DomainException::class);
});
