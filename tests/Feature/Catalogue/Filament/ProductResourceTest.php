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
