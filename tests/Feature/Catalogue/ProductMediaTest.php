<?php

use App\Models\Catalogue\Product;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    Storage::fake('public');
});

it('adds a primary image and replaces it', function () {
    $p = Product::factory()->create();

    $p->addMedia(UploadedFile::fake()->image('a.jpg'))->toMediaCollection('primary');
    expect($p->getMedia('primary'))->toHaveCount(1);

    $p->addMedia(UploadedFile::fake()->image('b.jpg'))->toMediaCollection('primary');
    expect($p->getMedia('primary'))->toHaveCount(1); // singleFile replaces
});

it('adds multiple gallery images preserving order', function () {
    $p = Product::factory()->create();

    $p->addMedia(UploadedFile::fake()->image('1.jpg'))->toMediaCollection('gallery');
    $p->addMedia(UploadedFile::fake()->image('2.jpg'))->toMediaCollection('gallery');
    $p->addMedia(UploadedFile::fake()->image('3.jpg'))->toMediaCollection('gallery');

    expect($p->getMedia('gallery'))->toHaveCount(3);
});

it('stores translatable alt text in custom properties', function () {
    $p = Product::factory()->create();
    $media = $p->addMedia(UploadedFile::fake()->image('a.jpg'))
        ->withCustomProperties(['alt' => ['uk' => 'Флакон', 'en' => 'Bottle']])
        ->toMediaCollection('primary');

    expect($media->getCustomProperty('alt.uk'))->toBe('Флакон');
    expect($media->getCustomProperty('alt.en'))->toBe('Bottle');
});
