<?php

use App\Filament\Resources\Articles\Pages\CreateArticle;
use App\Filament\Resources\Articles\Pages\EditArticle;
use App\Filament\Resources\Articles\Pages\ListArticles;
use App\Models\Catalogue\Product;
use App\Models\Content\Article;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the article list', function () {
    Article::factory()->count(2)->create();
    Livewire::test(ListArticles::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Article::all());
});

it('creates an article with title and slug', function () {
    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Огляд ароматів',
            'slug' => 'oglyad-aromaniv',
            'content' => 'Текст огляду.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::firstWhere('id', 1);
    expect($article->getTranslation('slug', 'uk'))->toBe('oglyad-aromaniv');
});

it('rejects a duplicate uk slug on create', function () {
    Article::factory()->create(['slug' => ['uk' => 'oglyad', 'en' => 'overview-en-1']]);

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Інша назва',
            'slug' => 'oglyad',
            'content' => 'Body.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasFormErrors(['slug']);
});

it('attaches products with sort_order matching repeater order', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $p3 = Product::factory()->create();

    Livewire::test(CreateArticle::class)
        ->fillForm([
            'title' => 'Topic A',
            'slug' => 'topic-a',
            'content' => 'Body.',
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
            'products' => [
                ['product_id' => $p2->id],
                ['product_id' => $p3->id],
                ['product_id' => $p1->id],
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = Article::firstWhere('id', 1);
    expect($article->products->pluck('id')->all())->toBe([$p2->id, $p3->id, $p1->id]);
});

it('persists reorder of products on edit', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $article = Article::factory()->create();
    $article->products()->attach([
        $p1->id => ['sort_order' => 0],
        $p2->id => ['sort_order' => 1],
    ]);

    Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm([
            'products' => [
                ['product_id' => $p2->id],
                ['product_id' => $p1->id],
            ],
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->products->pluck('id')->all())->toBe([$p2->id, $p1->id]);
});

it('persists product changes across two consecutive saves', function () {
    $p1 = Product::factory()->create();
    $p2 = Product::factory()->create();
    $p3 = Product::factory()->create();
    $article = Article::factory()->create();
    $article->products()->attach([$p1->id => ['sort_order' => 0]]);

    $component = Livewire::test(EditArticle::class, ['record' => $article->getRouteKey()])
        ->fillForm(['products' => [['product_id' => $p2->id]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->products->pluck('id')->all())->toBe([$p2->id]);

    $component
        ->fillForm(['products' => [['product_id' => $p3->id]]])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($article->fresh()->products->pluck('id')->all())->toBe([$p3->id]);
});

it('creates an article with category and read_time_minutes', function () {
    \Livewire\Livewire::test(\App\Filament\Resources\Articles\Pages\CreateArticle::class)
        ->fillForm([
            'title' => 'З категорією',
            'slug' => 'z-kategoriyeyu',
            'content' => 'Body.',
            'category' => 'Філософія',
            'read_time_minutes' => 6,
            'is_published' => true,
            'published_at' => now()->toDateTimeString(),
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $article = \App\Models\Content\Article::firstWhere('slug->uk', 'z-kategoriyeyu');
    expect($article->read_time_minutes)->toBe(6);
    expect($article->getTranslation('category', 'uk'))->toBe('Філософія');
});
