<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Catalogue\Tag;

beforeEach(function () {
    $this->onyx = Series::factory()->create(['slug' => 'onyx',   'name' => ['uk' => 'Onyx',   'en' => 'Onyx']]);
    $this->luxury = Series::factory()->create(['slug' => 'luxury', 'name' => ['uk' => 'Luxury', 'en' => 'Luxury']]);
    $this->tagNew = Tag::factory()->create(['slug' => 'new',        'name' => ['uk' => 'Новинка',   'en' => 'New']]);
    $this->tagBest = Tag::factory()->create(['slug' => 'bestseller', 'name' => ['uk' => 'Бестселер', 'en' => 'Bestseller']]);

    // LaravelLocalization runs LanguageNegotiator from the request's Accept-Language
    // header when no session locale is set. PHP test runs on macOS pick up the OS
    // locale and resolve to 'en', causing a redirect away from the default-locale
    // (uk) URL. Lock the test session to the locale we are exercising.
    $this->withSession(['locale' => 'uk']);
});

it('renders the catalog index with seeded products', function () {
    Product::factory()
        ->count(12)
        ->for($this->luxury, 'series')
        ->create();

    $this->get('/products')
        ->assertOk()
        ->assertSee('LEVANT')
        ->assertViewIs('products.index');
});

it('paginates 8 products per page', function () {
    Product::factory()
        ->count(20)
        ->for($this->luxury, 'series')
        ->create();

    $page1 = $this->get('/products');
    $page1->assertOk();
    expect($page1->viewData('products')->count())->toBe(8);
    expect($page1->viewData('products')->lastPage())->toBe(3);

    $page2 = $this->get('/products?page=2');
    expect($page2->viewData('products')->count())->toBe(8);

    $page3 = $this->get('/products?page=3');
    expect($page3->viewData('products')->count())->toBe(4);
});

it('filters products by series slug', function () {
    Product::factory()->count(6)->for($this->onyx, 'series')->create();
    Product::factory()->count(5)->for($this->luxury, 'series')->create();

    $response = $this->get('/products?series=onyx');

    $response->assertOk();
    $products = $response->viewData('products');
    expect($products->total())->toBe(6);
    foreach ($products as $p) {
        expect($p->series->slug)->toBe('onyx');
    }

    expect($response->viewData('total'))->toBe(6);
    expect($response->viewData('totalAll'))->toBe(11);
});

it('ignores unknown series filter and shows all', function () {
    Product::factory()->count(3)->for($this->onyx, 'series')->create();
    Product::factory()->count(2)->for($this->luxury, 'series')->create();

    $response = $this->get('/products?series=ghost');

    $response->assertOk();
    expect($response->viewData('products')->total())->toBe(5);
    expect($response->viewData('series'))->toBeNull();
});

it('shows empty state when filtered series has no published products', function () {
    Product::factory()->count(3)->for($this->onyx, 'series')->create();
    // luxury intentionally empty

    $response = $this->get('/products?series=luxury');

    $response->assertOk()
        ->assertSee(__('catalogue.public.empty'));

    expect($response->viewData('products')->total())->toBe(0);
});

it('sorts by price ascending', function () {
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 3000, 'sku' => 'A']);
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 1000, 'sku' => 'B']);
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 2000, 'sku' => 'C']);

    $skus = $this->get('/products?sort=priceA')
        ->viewData('products')
        ->pluck('sku')
        ->all();

    expect($skus)->toBe(['B', 'C', 'A']);
});

it('sorts by price descending', function () {
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 3000, 'sku' => 'A']);
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 1000, 'sku' => 'B']);
    Product::factory()->for($this->luxury, 'series')->create(['price_uah' => 2000, 'sku' => 'C']);

    $skus = $this->get('/products?sort=priceB')
        ->viewData('products')
        ->pluck('sku')
        ->all();

    expect($skus)->toBe(['A', 'C', 'B']);
});

it('places bestseller-tagged products first under the default popular sort', function () {
    $plain = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'PLAIN-1']);
    $plain2 = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'PLAIN-2']);
    $best = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'BEST-1']);
    $best->tags()->attach($this->tagBest);
    $best2 = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'BEST-2']);
    $best2->tags()->attach($this->tagBest);

    $skus = $this->get('/products')
        ->viewData('products')
        ->pluck('sku')
        ->all();

    expect($skus[0])->toStartWith('BEST-');
    expect($skus[1])->toStartWith('BEST-');
    expect($skus[2])->toStartWith('PLAIN-');
});

it('places new-tagged products first under the newest sort', function () {
    $plain = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'PLAIN-1']);
    $newProd = Product::factory()->for($this->luxury, 'series')->create(['sku' => 'NEW-1']);
    $newProd->tags()->attach($this->tagNew);

    $skus = $this->get('/products?sort=new')
        ->viewData('products')
        ->pluck('sku')
        ->all();

    expect($skus[0])->toBe('NEW-1');
});

it('skips unpublished products', function () {
    Product::factory()->count(2)->for($this->luxury, 'series')->create();
    Product::factory()->count(3)->draft()->for($this->luxury, 'series')->create();

    $response = $this->get('/products');

    expect($response->viewData('products')->total())->toBe(2);
    expect($response->viewData('totalAll'))->toBe(2);
});

it('preserves filter and sort across pagination links', function () {
    Product::factory()->count(20)->for($this->luxury, 'series')->create();

    $response = $this->get('/products?series=luxury&sort=priceB');

    $response->assertOk();
    $next = $response->viewData('products')->nextPageUrl();
    expect($next)
        ->toContain('series=luxury')
        ->toContain('sort=priceB');
});

it('exposes catalog public translations for both locales', function () {
    // LaravelLocalization re-registers prefixed routes per request in dev/prod,
    // but in the test runner the route table is built once at boot under the
    // default locale; hitting `/en/products` therefore 404s. Verify the
    // localized strings themselves instead — those are what the page renders.
    app()->setLocale('uk');
    expect(__('catalogue.public.title'))->toBe('Каталог');
    expect(__('catalogue.public.filter_onyx'))->toBe('Onyx Series');
    expect(__('catalogue.public.sort.priceA'))->toBe('Ціна ↑');
    expect(__('catalogue.public.badge_best'))->toBe('Бестселер');

    app()->setLocale('en');
    expect(__('catalogue.public.title'))->toBe('Catalogue');
    expect(__('catalogue.public.filter_onyx'))->toBe('Onyx Series');
    expect(__('catalogue.public.sort.priceA'))->toBe('Price ↑');
    expect(__('catalogue.public.badge_best'))->toBe('Bestseller');
});
