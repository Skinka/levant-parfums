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

it('gallery renders main image as data-lightbox-trigger button', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee('data-lightbox-trigger', false);
    $r->assertSee('data-lightbox-images', false);
});

it('renders product name, tagline, description', function () {
    $p = publishedProductInSeries('luxury', [
        'name' => ['uk' => 'Luxury № 01', 'en' => 'Luxury № 01'],
        'tagline' => ['uk' => 'Тиха ясність', 'en' => 'Quiet clarity'],
        'description' => ['uk' => 'Опис українською', 'en' => 'Description in English'],
    ]);

    $this->get(route('products.show', $p->slug))
        ->assertSee('Luxury № 01')
        ->assertSee('Тиха ясність')
        ->assertSee('Опис українською');
});

it('shows order CTA when in_stock=true', function () {
    $p = publishedProductInSeries('luxury', ['in_stock' => true]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.order_cta'));
    $r->assertDontSee(__('catalogue.public.product.preorder_cta'));
});

it('shows preorder CTA + btn-secondary when in_stock=false', function () {
    $p = publishedProductInSeries('luxury', ['in_stock' => false]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.preorder_cta'));
    $r->assertSee('btn-secondary', false);
});

it('hides why-block when why is null', function () {
    $p = publishedProductInSeries('luxury', ['why' => null]);
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.why_label'));
});
