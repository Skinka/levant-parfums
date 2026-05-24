<?php

use App\Forms\Livewire\OrderForm;
use App\Models\Catalogue\Note;
use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use Database\Seeders\Catalogue\SeriesSeeder;

beforeEach(function () {
    (new SeriesSeeder)->run();
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

it('renders pyramid section when product has notes', function () {
    $p = publishedProductInSeries('luxury');
    $note = Note::factory()->create(['name' => ['uk' => 'Бергамот', 'en' => 'Bergamot']]);
    $p->notes()->attach($note, ['level' => 'top', 'sort_order' => 0]);

    $this->get(route('products.show', $p->slug))
        ->assertSee(__('catalogue.public.product.pyramid.title'))
        ->assertSee('Бергамот');
});

it('hides pyramid when product has no notes', function () {
    $p = publishedProductInSeries('luxury');
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.pyramid.title'));
});

it('renders sillage bar when sillage_score set', function () {
    $p = publishedProductInSeries('luxury', ['sillage_score' => 4]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.character.sillage_label'));
    $r->assertSee(__('catalogue.public.product.character.sillage_words.4'));
});

it('renders longevity bar when longevity_hours set', function () {
    $p = publishedProductInSeries('luxury', ['longevity_hours' => 10]);
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSee(__('catalogue.public.product.character.longevity_label'));
});

it('hides character section when both sillage and longevity are null', function () {
    $p = publishedProductInSeries('luxury', ['sillage_score' => null, 'longevity_hours' => null]);
    $this->get(route('products.show', $p->slug))
        ->assertDontSee(__('catalogue.public.product.character.sillage_label'))
        ->assertDontSee(__('catalogue.public.product.character.longevity_label'));
});

it('mounts Livewire order-form with product as subject', function () {
    $p = publishedProductInSeries('luxury');
    $r = $this->get(route('products.show', $p->slug));
    $r->assertSeeLivewire(OrderForm::class);
});

it('order form section is anchorable via #order', function () {
    $p = publishedProductInSeries('luxury');
    $this->get(route('products.show', $p->slug))->assertSee('id="order"', false);
});

it('shows up to 6 related products from same series', function () {
    $main = publishedProductInSeries('luxury', ['slug' => 'lux-main']);
    for ($i = 1; $i <= 8; $i++) {
        publishedProductInSeries('luxury', ['slug' => "lux-related-{$i}"]);
    }
    $r = $this->get(route('products.show', $main->slug));
    $r->assertSee(__('catalogue.public.product.related.title'));
    $r->assertSee('lux-related-1');
});

it('fills with cross-series related when same-series count under 4', function () {
    $main = publishedProductInSeries('luxury', ['slug' => 'lux-main']);
    publishedProductInSeries('luxury', ['slug' => 'lux-only-buddy']);
    for ($i = 1; $i <= 5; $i++) {
        publishedProductInSeries('onyx', ['slug' => "onyx-fill-{$i}"]);
    }

    $r = $this->get(route('products.show', $main->slug));
    $r->assertSee('lux-only-buddy');
    $r->assertSee('onyx-fill-1');
});
