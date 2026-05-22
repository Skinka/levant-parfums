<?php

use App\Filament\Resources\Products\Pages\CreateProduct;
use App\Filament\Resources\Products\Pages\ListProducts;
use App\Models\Catalogue\Product;
use App\Models\User;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

beforeEach(fn () => actingAs(User::factory()->create()));

it('renders the product list page', function () {
    Product::factory()->count(3)->create();
    Livewire::test(ListProducts::class)
        ->assertOk()
        ->assertCanSeeTableRecords(Product::all());
});

it('renders the create product page with the main tab', function () {
    Livewire::test(CreateProduct::class)->assertOk();
});

it('creates a product with description tab fields', function () {
    Livewire::test(CreateProduct::class)
        ->fillForm([
            'name' => ['uk' => 'LUXURY 4', 'en' => 'LUXURY 4'],
            'slug' => 'luxury-4',
            'sku' => 'LV-001',
            'gender' => 'unisex',
            'volume_ml' => 50,
            'tagline' => ['uk' => 'Флоральний наркотик', 'en' => 'Floral narcotic'],
            'description' => ['uk' => '<p>Опис</p>', 'en' => '<p>Description</p>'],
            'price_uah' => 1290,
            'price_eur' => 35,
            'in_stock' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('products', ['slug' => 'luxury-4', 'sku' => 'LV-001']);
});
