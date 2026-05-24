<?php

use App\Http\Controllers\PageController;
use App\Http\Controllers\ProductCatalogController;
use Illuminate\Support\Facades\Route;
use Mcamara\LaravelLocalization\Facades\LaravelLocalization;

Route::group([
    'prefix' => LaravelLocalization::setLocale(),
    'middleware' => ['localeSessionRedirect', 'localizationRedirect', 'localeViewPath'],
], function () {
    Route::get('/', [PageController::class, 'home'])->name('home');

    Route::get('/products', [ProductCatalogController::class, 'index'])->name('products.index');
    Route::get('/products/{product:slug}', [ProductCatalogController::class, 'show'])
        ->where('product', '[A-Za-z0-9\-_]+')
        ->name('products.show');

    Route::get('/{slug}', [PageController::class, 'show'])
        ->where('slug', '[A-Za-z0-9\-_]+')
        ->name('page.show');
});
