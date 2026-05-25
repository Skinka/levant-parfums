<?php

use App\Models\Catalogue\Product;
use App\Models\Catalogue\Series;
use App\Models\Content\Article;
use App\Models\Content\Page;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
    $this->withSession(['locale' => 'uk']);
    $this->image = UploadedFile::fake()->image('hero.jpg', 1600, 1200);
});

it('generates an og conversion for Product primary media', function () {
    $product = Product::factory()->for(Series::factory(), 'series')->create();
    $product->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    $media = $product->getFirstMedia('primary');
    expect($media->hasGeneratedConversion('og'))->toBeTrue();
});

it('generates an og conversion for Article primary media', function () {
    $article = Article::factory()->create();
    $article->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    expect($article->getFirstMedia('primary')->hasGeneratedConversion('og'))->toBeTrue();
});

it('generates an og conversion for Page primary media', function () {
    $page = Page::factory()->create();
    $page->addMedia($this->image->getRealPath())->preservingOriginal()->toMediaCollection('primary');

    expect($page->getFirstMedia('primary')->hasGeneratedConversion('og'))->toBeTrue();
});
