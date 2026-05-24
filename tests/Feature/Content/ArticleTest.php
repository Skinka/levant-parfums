<?php

use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use Illuminate\Database\QueryException;
use Illuminate\Support\Carbon;

it('casts published_at to datetime', function () {
    $a = Article::factory()->create(['published_at' => '2026-06-01 12:00:00']);
    expect($a->published_at)->toBeInstanceOf(Carbon::class);
});

it('stores translatable fields per locale', function () {
    $a = Article::factory()->create([
        'title' => ['uk' => 'Огляд', 'en' => 'Overview'],
    ]);
    expect($a->getTranslation('title', 'uk'))->toBe('Огляд');
    expect($a->getTranslation('title', 'en'))->toBe('Overview');
});

it('published scope hides is_published=false', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => null]);
    Article::factory()->create(['is_published' => false, 'published_at' => null]);

    expect(Article::published()->count())->toBe(1);
});

it('published scope hides future published_at', function () {
    Article::factory()->create(['is_published' => true, 'published_at' => now()->subDay()]);
    Article::factory()->create(['is_published' => true, 'published_at' => now()->addDay()]);

    expect(Article::published()->count())->toBe(1);
});

it('products relation orders by pivot sort_order', function () {
    $article = Article::factory()->create();
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $p3 = Product::factory()->create();

    $article->products()->attach([
        $p3->id => ['sort_order' => 0],
        $p1->id => ['sort_order' => 1],
        $p2->id => ['sort_order' => 2],
    ]);

    expect($article->products->pluck('id')->all())->toBe([$p3->id, $p1->id, $p2->id]);
});

it('DB rejects duplicate uk slug for two articles', function () {
    Article::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-1']]);

    expect(fn () => Article::factory()->create(['slug' => ['uk' => 'foo', 'en' => 'foo-en-2']]))
        ->toThrow(QueryException::class);
});

it('DB rejects duplicate en slug for two articles', function () {
    Article::factory()->create(['slug' => ['uk' => 'foo-uk-1', 'en' => 'bar']]);

    expect(fn () => Article::factory()->create(['slug' => ['uk' => 'foo-uk-2', 'en' => 'bar']]))
        ->toThrow(QueryException::class);
});
