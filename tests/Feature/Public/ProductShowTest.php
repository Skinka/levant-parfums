<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Database\Seeders\Catalogue\SeriesSeeder;

beforeEach(function () {
    (new SeriesSeeder())->run();
    $this->withSession(['locale' => 'uk']);
});

function publishedProductInSeries(string $seriesSlug, array $attrs = []): Product
{
    $s = Series::where('slug', $seriesSlug)->first();

    return Product::factory()->create(array_merge([
        'series_id' => $s->id, 'is_published' => true, 'published_at' => now()->subDay(),
    ], $attrs));
}

it('luxury product page returns 200 with theme-cream body class', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertOk()->assertSee('class="theme-cream"', false);
});

it('onyx product page returns 200 with theme-onyx body class', function () {
    $p = publishedProductInSeries('onyx');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertOk()->assertSee('class="theme-onyx"', false);
});

it('unpublished product returns 404', function () {
    $p = publishedProductInSeries('luxury', ['is_published' => false]);
    $this->get(route('products.show', $p->slug))->assertNotFound();
});

it('missing slug returns 404', function () {
    $this->get(route('products.show', 'nope-not-real'))->assertNotFound();
});
